<?php

namespace App\Filament\Pages\Sales;

use App\Models\Sales\Customer;
use App\Models\Sales\SalesInvoice;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class CustomerGeoIntelligence extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Geo Intelligence';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Customer Geo Intelligence';

    protected static ?string $slug = 'sales/customer-geo-intelligence';

    protected static string $view = 'filament.pages.sales.customer-geo-intelligence';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public ?string $country = null;

    public bool $africaOnly = true;

    public function mount(): void
    {
        $this->fromDate = now('Africa/Nairobi')->subYear()->toDateString();
        $this->toDate = now('Africa/Nairobi')->toDateString();
        $this->country = null;
        $this->africaOnly = true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view customer geo intelligence') ?? false;
    }

    public function updated(): void
    {
        $payload = $this->geoPayload();

        $this->dispatch('customer-geo-map-updated', points: $payload['points']);
    }

    public function getViewData(): array
    {
        return $this->geoPayload();
    }

    private function geoPayload(): array
    {
        $from = $this->safeDate($this->fromDate, now('Africa/Nairobi')->subYear())->toDateString();
        $to = $this->safeDate($this->toDate, now('Africa/Nairobi'))->toDateString();

        $invoices = SalesInvoice::query()
            ->with([
                'customer',
                'items',
            ])
            ->whereNotIn('status', ['cancelled', 'voided'])
            ->whereBetween('invoice_date', [$from, $to])
            ->whereHas('customer', function (Builder $query): void {
                $query
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude');

                if (filled($this->country)) {
                    $query->where('country', $this->country);
                }
            })
            ->get();

        $groups = [];

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;

            if (! $customer) {
                continue;
            }

            if (! is_numeric($customer->latitude) || ! is_numeric($customer->longitude)) {
                continue;
            }

            $lat = (float) $customer->latitude;
            $lng = (float) $customer->longitude;

            if ($this->africaOnly && ! $this->isWithinAfrica($lat, $lng)) {
                continue;
            }

            $key = $customer->getKey() . '|' . round($lat, 5) . '|' . round($lng, 5);

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'customer_id' => $customer->getKey(),
                    'customer_number' => $customer->customer_number,
                    'customer' => $customer->name,
                    'phone' => $customer->phone,
                    'country' => $customer->country ?: 'Unknown',
                    'county' => $customer->county ?: 'Unknown County',
                    'town' => $customer->town ?: 'Unknown Town',
                    'address' => $customer->address,
                    'place_label' => $customer->place_label,
                    'location' => $this->locationLabel($customer),
                    'lat' => $lat,
                    'lng' => $lng,
                    'animals' => 0,
                    'invoices' => 0,
                    'revenue' => 0,
                    'tags' => [],
                    'invoice_numbers' => [],
                    'latest_invoice_date' => null,
                ];
            }

            $tags = $invoice->items
                ->pluck('tag_number')
                ->filter()
                ->values()
                ->all();

            $animalCount = count($tags) > 0
                ? count($tags)
                : (int) ($invoice->total_animals ?? 0);

            $groups[$key]['animals'] += $animalCount;
            $groups[$key]['invoices']++;
            $groups[$key]['revenue'] += (float) ($invoice->grand_total ?? 0);
            $groups[$key]['tags'] = array_merge($groups[$key]['tags'], $tags);
            $groups[$key]['invoice_numbers'][] = $invoice->invoice_number;

            $invoiceDate = optional($invoice->invoice_date)->format('Y-m-d');

            if (! $groups[$key]['latest_invoice_date'] || $invoiceDate > $groups[$key]['latest_invoice_date']) {
                $groups[$key]['latest_invoice_date'] = $invoiceDate;
            }
        }

        $points = collect($groups)
            ->map(function (array $group): array {
                $group['tags'] = collect($group['tags'])
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $group['invoice_numbers'] = collect($group['invoice_numbers'])
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $group['revenue_formatted'] = 'KES ' . number_format($group['revenue'], 2);
                $group['heat_weight'] = min(1, max(0.25, $group['animals'] / 8));

                return $group;
            })
            ->sortByDesc('animals')
            ->values();

        $totalCustomers = $points->count();
        $totalAnimals = $points->sum('animals');
        $totalRevenue = $points->sum('revenue');
        $totalInvoices = $points->sum('invoices');

        $topLocations = $points
            ->groupBy(fn (array $point): string => trim(($point['town'] ?: 'Unknown Town') . ', ' . ($point['country'] ?: 'Unknown Country')))
            ->map(function ($items, string $location): array {
                return [
                    'location' => $location,
                    'customers' => $items->count(),
                    'animals' => $items->sum('animals'),
                    'revenue' => $items->sum('revenue'),
                    'revenue_formatted' => 'KES ' . number_format($items->sum('revenue'), 2),
                ];
            })
            ->sortByDesc('animals')
            ->take(8)
            ->values();

        $topCountries = $points
            ->groupBy(fn (array $point): string => $point['country'] ?: 'Unknown Country')
            ->map(function ($items, string $country): array {
                return [
                    'country' => $country,
                    'customers' => $items->count(),
                    'animals' => $items->sum('animals'),
                    'revenue' => $items->sum('revenue'),
                    'revenue_formatted' => 'KES ' . number_format($items->sum('revenue'), 2),
                ];
            })
            ->sortByDesc('animals')
            ->take(6)
            ->values();

        $topCustomers = $points
            ->sortByDesc('animals')
            ->take(6)
            ->values();

        $unmappedCustomers = Customer::query()
            ->when(filled($this->country), fn (Builder $query) => $query->where('country', $this->country))
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('latitude')
                    ->orWhereNull('longitude');
            })
            ->count();

        $mappedCustomerDatabaseCount = Customer::query()
            ->when(filled($this->country), fn (Builder $query) => $query->where('country', $this->country))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->count();

        $totalCustomerDatabaseCount = $mappedCustomerDatabaseCount + $unmappedCustomers;

        $mapCoverage = $totalCustomerDatabaseCount > 0
            ? round(($mappedCustomerDatabaseCount / $totalCustomerDatabaseCount) * 100, 1)
            : 0;

        $averageAnimalsPerCustomer = $totalCustomers > 0
            ? round($totalAnimals / $totalCustomers, 1)
            : 0;

        $averageRevenuePerCustomer = $totalCustomers > 0
            ? $totalRevenue / $totalCustomers
            : 0;

        $bestLocation = $topLocations->first();
        $bestCountry = $topCountries->first();
        $bestCustomer = $topCustomers->first();

        $insights = [
            [
                'title' => 'Strongest buyer zone',
                'value' => $bestLocation['location'] ?? 'Not enough mapped sales yet',
                'body' => $bestLocation
                    ? number_format($bestLocation['animals']) . ' animal(s) from ' . number_format($bestLocation['customers']) . ' customer(s).'
                    : 'Add more mapped customer sales to identify the leading hotspot.',
            ],
            [
                'title' => 'Best country signal',
                'value' => $bestCountry['country'] ?? 'No country signal yet',
                'body' => $bestCountry
                    ? number_format($bestCountry['animals']) . ' animal(s), ' . $bestCountry['revenue_formatted'] . ' revenue.'
                    : 'Country intelligence will improve as customers get coordinates.',
            ],
            [
                'title' => 'Top customer movement',
                'value' => $bestCustomer['customer'] ?? 'No top customer yet',
                'body' => $bestCustomer
                    ? number_format($bestCustomer['animals']) . ' animal(s) bought. Latest sale: ' . ($bestCustomer['latest_invoice_date'] ?: '-')
                    : 'No mapped buyer movement is available in this period.',
            ],
            [
                'title' => 'Map data quality',
                'value' => $mapCoverage . '% mapped',
                'body' => number_format($unmappedCustomers) . ' customer(s) still need latitude and longitude.',
            ],
        ];

        return [
            'points' => $points->all(),
            'countries' => $this->countryOptions(),
            'fromDate' => $from,
            'toDate' => $to,
            'totalCustomers' => $totalCustomers,
            'totalAnimals' => $totalAnimals,
            'totalInvoices' => $totalInvoices,
            'totalRevenue' => $totalRevenue,
            'totalRevenueFormatted' => 'KES ' . number_format($totalRevenue, 2),
            'topLocations' => $topLocations->all(),
            'topCountries' => $topCountries->all(),
            'topCustomers' => $topCustomers->all(),
            'insights' => $insights,
            'unmappedCustomers' => $unmappedCustomers,
            'mappedCustomerDatabaseCount' => $mappedCustomerDatabaseCount,
            'mapCoverage' => $mapCoverage,
            'averageAnimalsPerCustomer' => $averageAnimalsPerCustomer,
            'averageRevenuePerCustomerFormatted' => 'KES ' . number_format($averageRevenuePerCustomer, 2),
        ];
    }

    private function countryOptions(): array
    {
        return Customer::query()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->orderBy('country')
            ->pluck('country', 'country')
            ->toArray();
    }

    private function safeDate(?string $date, Carbon $fallback): Carbon
    {
        try {
            return filled($date)
                ? Carbon::parse($date, 'Africa/Nairobi')
                : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function isWithinAfrica(float $lat, float $lng): bool
    {
        return $lat >= -35.5
            && $lat <= 38.5
            && $lng >= -20.5
            && $lng <= 55.5;
    }

    private function locationLabel($customer): string
    {
        $parts = collect([
            $customer->town,
            $customer->county,
            $customer->country,
        ])
            ->filter()
            ->values()
            ->all();

        return ! empty($parts)
            ? implode(', ', $parts)
            : ($customer->place_label ?: $customer->address ?: 'Location not labelled');
    }
}
