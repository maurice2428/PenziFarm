<?php

namespace App\Support\GlobalSearch;

use BackedEnum;
use Filament\Pages\Page as FilamentPage;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Resources\Resource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;
use UnitEnum;

class UniversalSearchManager
{
    /**
     * @var array<int, class-string<Resource>>|null
     */
    protected static ?array $discoveredResources = null;

    /**
     * @var array<int, class-string<FilamentPage>>|null
     */
    protected static ?array $discoveredPages = null;

    /**
     * @var array<string, array<int, string>>
     */
    protected array $columnCache = [];

    /**
     * Search the complete Filament application.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $search): array
    {
        if (! config('universal-search.enabled', true)) {
            return [];
        }

        $search = trim(preg_replace('/\s+/', ' ', $search) ?? '');

        if (mb_strlen($search) < (int) config('universal-search.minimum_query_length', 2)) {
            return [];
        }

        $results = collect()
            ->merge($this->searchResourceNavigation($search))
            ->merge($this->searchResourceRecords($search))
            ->merge($this->searchCustomPages($search))
            ->filter(fn (array $result): bool => filled($result['url'] ?? null))
            ->unique('url')
            ->sortByDesc('score')
            ->take((int) config('universal-search.total_result_limit', 40))
            ->values()
            ->map(function (array $result): array {
                unset($result['score']);

                return $result;
            });

        return $results->all();
    }

    /**
     * Search records from every authorized Filament Resource.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function searchResourceRecords(string $search): Collection
    {
        return collect($this->discoverResources())
            ->flatMap(function (string $resource) use ($search): array {
                try {
                    if (! $this->resourceIsAllowed($resource)) {
                        return [];
                    }

                    if (! $resource::canViewAny()) {
                        return [];
                    }

                    $modelClass = $resource::getModel();

                    if (! class_exists($modelClass)) {
                        return [];
                    }

                    /** @var Model $model */
                    $model = new $modelClass();

                    $override = $this->resourceOverride($resource);
                    $attributes = $this->searchableAttributes(
                        $resource,
                        $model,
                        $override
                    );

                    if ($attributes === []) {
                        return [];
                    }

                    /** @var Builder $query */
                    $query = $resource::getGlobalSearchEloquentQuery();

                    $relations = collect($override['with'] ?? [])
                        ->merge($this->relationsFromAttributes($attributes))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    if ($relations !== []) {
                        $query->with($relations);
                    }

                    $this->applySearchConstraints(
                        $query,
                        $model,
                        $attributes,
                        $search
                    );

                    $records = $query
                        ->limit((int) config('universal-search.per_resource_limit', 5))
                        ->get();

                    return $records
                        ->filter(
                            fn (Model $record): bool =>
                                $this->canOpenRecord($resource, $record)
                        )
                        ->map(
                            fn (Model $record): array =>
                                $this->recordResult(
                                    $resource,
                                    $record,
                                    $search,
                                    $override
                                )
                        )
                        ->all();
                } catch (Throwable $exception) {
                    report($exception);

                    return [];
                }
            });
    }

    /**
     * Add one navigation result per Resource, allowing searches such as
     * "employees", "animals", "sales invoices", and "purchase orders".
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function searchResourceNavigation(string $search): Collection
    {
        return collect($this->discoverResources())
            ->map(function (string $resource) use ($search): ?array {
                try {
                    if (! $this->resourceIsAllowed($resource)) {
                        return null;
                    }

                    if (! $resource::canViewAny()) {
                        return null;
                    }

                    $label = $this->stringValue(
                        $resource::getNavigationLabel()
                            ?: $resource::getPluralModelLabel()
                    );

                    $group = $this->stringValue(
                        $resource::getNavigationGroup()
                    ) ?: 'Modules';

                    $haystack = trim($label . ' ' . $group);

                    if (! $this->matches($search, $haystack)) {
                        return null;
                    }

                    $url = $resource::hasPage('index')
                        ? $resource::getUrl('index')
                        : null;

                    if (blank($url)) {
                        return null;
                    }

                    return [
                        'type' => 'module',
                        'group' => 'Modules & Pages',
                        'title' => $label,
                        'subtitle' => $group,
                        'details' => [],
                        'url' => $url,
                        'icon' => $this->iconValue(
                            $resource::getNavigationIcon()
                        ) ?: 'heroicon-o-squares-2x2',
                        'score' => $this->score(
                            $search,
                            $label,
                            $group,
                            40
                        ),
                    ];
                } catch (Throwable $exception) {
                    report($exception);

                    return null;
                }
            })
            ->filter()
            ->values();
    }

    /**
     * Search custom Filament pages such as dashboards and reports.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function searchCustomPages(string $search): Collection
    {
        return collect($this->discoverPages())
            ->map(function (string $page) use ($search): ?array {
                try {
                    if (! $this->pageIsAllowed($page)) {
                        return null;
                    }

                    if (method_exists($page, 'canAccess') && ! $page::canAccess()) {
                        return null;
                    }

                    $label = $this->stringValue(
                        $page::getNavigationLabel()
                            ?: $page::getTitle()
                    );

                    $group = $this->stringValue(
                        $page::getNavigationGroup()
                    ) ?: 'Pages';

                    $aliases = config(
                        "universal-search.page_aliases.{$page}",
                        []
                    );

                    $haystack = trim(
                        $label
                        . ' '
                        . $group
                        . ' '
                        . implode(' ', is_array($aliases) ? $aliases : [])
                    );

                    if (! $this->matches($search, $haystack)) {
                        return null;
                    }

                    $url = $page::getNavigationUrl();

                    if (blank($url)) {
                        return null;
                    }

                    return [
                        'type' => 'page',
                        'group' => 'Modules & Pages',
                        'title' => $label,
                        'subtitle' => $group,
                        'details' => [],
                        'url' => $url,
                        'icon' => $this->iconValue(
                            $page::getNavigationIcon()
                        ) ?: 'heroicon-o-document-text',
                        'score' => $this->score(
                            $search,
                            $label,
                            $haystack,
                            45
                        ),
                    ];
                } catch (Throwable $exception) {
                    report($exception);

                    return null;
                }
            })
            ->filter()
            ->values();
    }

    /**
     * @param class-string<Resource> $resource
     * @param array<string, mixed> $override
     * @return array<int, string>
     */
    protected function searchableAttributes(
        string $resource,
        Model $model,
        array $override
    ): array {
        $attributes = $override['search'] ?? null;

        if (! is_array($attributes) || $attributes === []) {
            try {
                $attributes = $resource::getGloballySearchableAttributes();
            } catch (Throwable) {
                $attributes = [];
            }
        }

        if (! is_array($attributes) || $attributes === []) {
            $attributes = $this->inferSearchableColumns($model);
        }

        return collect($attributes)
            ->filter(fn (mixed $attribute): bool => is_string($attribute))
            ->map(fn (string $attribute): string => trim($attribute))
            ->filter(
                fn (string $attribute): bool =>
                    $attribute !== ''
                    && preg_match(
                        '/^[A-Za-z_][A-Za-z0-9_.]*$/',
                        $attribute
                    ) === 1
            )
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function inferSearchableColumns(Model $model): array
    {
        $table = $model->getTable();

        if (! array_key_exists($table, $this->columnCache)) {
            try {
                $this->columnCache[$table] = Schema::getColumnListing($table);
            } catch (Throwable) {
                $this->columnCache[$table] = [];
            }
        }

        $available = $this->columnCache[$table];
        $preferred = config('universal-search.common_search_columns', []);

        return collect($preferred)
            ->filter(fn (string $column): bool => in_array($column, $available, true))
            ->values()
            ->all();
    }

    /**
     * Apply a token-aware search.
     *
     * Every word must match at least one configured attribute. Therefore,
     * "Maurice Nzioki" may match first_name and last_name separately.
     *
     * @param array<int, string> $attributes
     */
    protected function applySearchConstraints(
        Builder $query,
        Model $model,
        array $attributes,
        string $search
    ): void {
        $tokens = collect(
            preg_split('/\s+/', trim($search)) ?: []
        )
            ->filter()
            ->take(6)
            ->values();

        foreach ($tokens as $token) {
            $query->where(function (Builder $tokenQuery) use (
                $attributes,
                $model,
                $token
            ): void {
                foreach ($attributes as $attribute) {
                    if (str_contains($attribute, '.')) {
                        $segments = explode('.', $attribute);
                        $column = array_pop($segments);
                        $relationship = implode('.', $segments);

                        if (
                            blank($relationship)
                            || ! $this->safeIdentifier($column)
                        ) {
                            continue;
                        }

                        $tokenQuery->orWhereHas(
                            $relationship,
                            function (Builder $relationQuery) use (
                                $column,
                                $token
                            ): void {
                                $qualified = $relationQuery
                                    ->getModel()
                                    ->qualifyColumn($column);

                                $relationQuery->where(
                                    $qualified,
                                    'like',
                                    '%' . $this->escapeLike($token) . '%'
                                );
                            }
                        );

                        continue;
                    }

                    if (! $this->safeIdentifier($attribute)) {
                        continue;
                    }

                    $tokenQuery->orWhere(
                        $model->qualifyColumn($attribute),
                        'like',
                        '%' . $this->escapeLike($token) . '%'
                    );
                }
            });
        }
    }

    /**
     * @param class-string<Resource> $resource
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    protected function recordResult(
        string $resource,
        Model $record,
        string $search,
        array $override
    ): array {
        $title = $this->recordTitle(
            $resource,
            $record,
            $override
        );

        $details = $this->recordDetails(
            $resource,
            $record,
            $override
        );

        $subtitle = collect($details)
            ->filter(fn (mixed $value): bool => filled($value))
            ->take(3)
            ->implode(' • ');

        $group = $this->stringValue(
            $resource::getPluralModelLabel()
                ?: $resource::getNavigationLabel()
        );

        return [
            'type' => 'record',
            'group' => $group ?: 'Records',
            'title' => $title,
            'subtitle' => $subtitle,
            'details' => $details,
            'url' => $this->recordUrl($resource, $record),
            'icon' => $this->iconValue(
                $resource::getNavigationIcon()
            ) ?: 'heroicon-o-magnifying-glass',
            'score' => $this->score(
                $search,
                $title,
                $subtitle,
                60
            ),
        ];
    }

    /**
     * @param class-string<Resource> $resource
     * @param array<string, mixed> $override
     */
    protected function recordTitle(
        string $resource,
        Model $record,
        array $override
    ): string {
        $overrideTitle = $override['title'] ?? null;

        if (is_string($overrideTitle) && filled(data_get($record, $overrideTitle))) {
            $primary = (string) data_get($record, $overrideTitle);

            if (
                $overrideTitle !== 'employee_number'
                && filled($record->getAttribute('employee_number'))
            ) {
                return $record->getAttribute('employee_number')
                    . ' — '
                    . $primary;
            }

            return $primary;
        }

        try {
            $title = $resource::getGlobalSearchResultTitle($record);
            $title = $this->plainText($title);

            if (filled($title)) {
                return $title;
            }
        } catch (Throwable) {
            //
        }

        foreach (config('universal-search.title_candidates', []) as $attribute) {
            $value = data_get($record, $attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return class_basename($record) . ' #' . $record->getKey();
    }

    /**
     * @param class-string<Resource> $resource
     * @param array<string, mixed> $override
     * @return array<string, string>
     */
    protected function recordDetails(
        string $resource,
        Model $record,
        array $override
    ): array {
        $definition = $override['details'] ?? null;

        if (is_array($definition) && $definition !== []) {
            return collect($definition)
                ->mapWithKeys(function (string $path, string $label) use ($record): array {
                    $value = data_get($record, $path);

                    return filled($value)
                        ? [$label => $this->formatDetailValue($value)]
                        : [];
                })
                ->take(5)
                ->all();
        }

        try {
            $details = $resource::getGlobalSearchResultDetails($record);

            if (is_array($details) && $details !== []) {
                return collect($details)
                    ->map(
                        fn (mixed $value): string =>
                            $this->formatDetailValue($value)
                    )
                    ->filter()
                    ->take(5)
                    ->all();
            }
        } catch (Throwable) {
            //
        }

        return collect(config('universal-search.detail_candidates', []))
            ->mapWithKeys(function (string $label, string $attribute) use ($record): array {
                $value = data_get($record, $attribute);

                return filled($value)
                    ? [$label => $this->formatDetailValue($value)]
                    : [];
            })
            ->take(4)
            ->all();
    }

    /**
     * @param class-string<Resource> $resource
     */
    protected function recordUrl(string $resource, Model $record): ?string
    {
        try {
            $url = $resource::getGlobalSearchResultUrl($record);

            if (filled($url)) {
                return $url;
            }
        } catch (Throwable) {
            //
        }

        try {
            if ($resource::hasPage('view') && $resource::canView($record)) {
                return $resource::getUrl(
                    'view',
                    ['record' => $record]
                );
            }

            if ($resource::hasPage('edit') && $resource::canEdit($record)) {
                return $resource::getUrl(
                    'edit',
                    ['record' => $record]
                );
            }

            if ($resource::hasPage('index')) {
                return $resource::getUrl('index');
            }
        } catch (Throwable) {
            //
        }

        return null;
    }

    /**
     * @param class-string<Resource> $resource
     */
    protected function canOpenRecord(string $resource, Model $record): bool
    {
        try {
            return $resource::canView($record)
                || $resource::canEdit($record);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param class-string<Resource> $resource
     * @return array<string, mixed>
     */
    protected function resourceOverride(string $resource): array
    {
        $overrides = config('universal-search.resource_overrides', []);

        return is_array($overrides[$resource] ?? null)
            ? $overrides[$resource]
            : [];
    }

    /**
     * @param array<int, string> $attributes
     * @return array<int, string>
     */
    protected function relationsFromAttributes(array $attributes): array
    {
        return collect($attributes)
            ->filter(fn (string $attribute): bool => str_contains($attribute, '.'))
            ->map(function (string $attribute): string {
                $segments = explode('.', $attribute);
                array_pop($segments);

                return implode('.', $segments);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, class-string<Resource>>
     */
    protected function discoverResources(): array
    {
        if (self::$discoveredResources !== null) {
            return self::$discoveredResources;
        }

        self::$discoveredResources = collect(
            config('universal-search.resource_directories', [])
        )
            ->flatMap(
                fn (string $directory): array =>
                    $this->discoverClassesInDirectory($directory)
            )
            ->filter(function (string $class): bool {
                try {
                    if (
                        ! class_exists($class)
                        || ! is_subclass_of($class, Resource::class)
                    ) {
                        return false;
                    }

                    return ! (new ReflectionClass($class))->isAbstract();
                } catch (Throwable) {
                    return false;
                }
            })
            ->unique()
            ->values()
            ->all();

        return self::$discoveredResources;
    }

    /**
     * @return array<int, class-string<FilamentPage>>
     */
    protected function discoverPages(): array
    {
        if (self::$discoveredPages !== null) {
            return self::$discoveredPages;
        }

        self::$discoveredPages = collect(
            config('universal-search.page_directories', [])
        )
            ->flatMap(
                fn (string $directory): array =>
                    $this->discoverClassesInDirectory($directory)
            )
            ->filter(function (string $class): bool {
                try {
                    if (
                        ! class_exists($class)
                        || ! is_subclass_of($class, FilamentPage::class)
                        || is_subclass_of($class, ResourcePage::class)
                    ) {
                        return false;
                    }

                    return ! (new ReflectionClass($class))->isAbstract();
                } catch (Throwable) {
                    return false;
                }
            })
            ->unique()
            ->values()
            ->all();

        return self::$discoveredPages;
    }

    /**
     * @return array<int, string>
     */
    protected function discoverClassesInDirectory(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        return collect(File::allFiles($directory))
            ->filter(
                fn ($file): bool =>
                    $file->getExtension() === 'php'
            )
            ->map(function ($file): string {
                $relative = Str::after(
                    $file->getPathname(),
                    app_path() . DIRECTORY_SEPARATOR
                );

                return 'App\\'
                    . str_replace(
                        ['/', '\\', '.php'],
                        ['\\', '\\', ''],
                        $relative
                    );
            })
            ->all();
    }

    /**
     * @param class-string<Resource> $resource
     */
    protected function resourceIsAllowed(string $resource): bool
    {
        return ! in_array(
            $resource,
            config('universal-search.excluded_resources', []),
            true
        );
    }

    /**
     * @param class-string<FilamentPage> $page
     */
    protected function pageIsAllowed(string $page): bool
    {
        return ! in_array(
            $page,
            config('universal-search.excluded_pages', []),
            true
        );
    }

    protected function matches(string $search, string $haystack): bool
    {
        $normalizedHaystack = Str::lower(
            Str::ascii($haystack)
        );

        return collect(
            preg_split('/\s+/', Str::lower(Str::ascii($search))) ?: []
        )
            ->filter()
            ->every(
                fn (string $token): bool =>
                    str_contains($normalizedHaystack, $token)
            );
    }

    protected function score(
        string $search,
        string $title,
        string $context = '',
        int $base = 0
    ): int {
        $needle = Str::lower(Str::ascii(trim($search)));
        $titleValue = Str::lower(Str::ascii(trim($title)));
        $contextValue = Str::lower(Str::ascii(trim($context)));

        if ($titleValue === $needle) {
            return $base + 100;
        }

        if (str_starts_with($titleValue, $needle)) {
            return $base + 80;
        }

        if (str_contains($titleValue, $needle)) {
            return $base + 60;
        }

        if (str_contains($contextValue, $needle)) {
            return $base + 30;
        }

        return $base + 10;
    }

    protected function plainText(mixed $value): string
    {
        if ($value instanceof Htmlable) {
            $value = $value->toHtml();
        }

        return trim(
            html_entity_decode(
                strip_tags((string) $value)
            )
        );
    }

    protected function formatDetailValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        } elseif ($value instanceof UnitEnum) {
            $value = $value->name;
        } elseif ($value instanceof Htmlable) {
            $value = $value->toHtml();
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (! is_scalar($value) && $value !== null) {
            return '';
        }

        return Str::headline(
            trim(
                html_entity_decode(
                    strip_tags((string) $value)
                )
            )
        );
    }

    protected function stringValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof Htmlable) {
            return $this->plainText($value);
        }

        return is_scalar($value)
            ? trim((string) $value)
            : '';
    }

    protected function iconValue(mixed $value): ?string
    {
        $value = $this->stringValue($value);

        return filled($value) ? $value : null;
    }

    protected function safeIdentifier(string $identifier): bool
    {
        return preg_match(
            '/^[A-Za-z_][A-Za-z0-9_]*$/',
            $identifier
        ) === 1;
    }

    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
