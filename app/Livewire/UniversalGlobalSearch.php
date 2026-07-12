<?php

namespace App\Livewire;

use App\Support\GlobalSearch\UniversalSearchManager;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UniversalGlobalSearch extends Component
{
    public string $query = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $results = [];

    public bool $open = false;

    public function updatedQuery(): void
    {
        $this->runSearch();
    }

    public function openSearch(): void
    {
        $this->open = true;

        if (filled($this->query)) {
            $this->runSearch();
        }
    }

    public function closeSearch(): void
    {
        $this->open = false;
    }

    public function clearSearch(): void
    {
        $this->reset([
            'query',
            'results',
            'open',
        ]);
    }

    public function goToFirstResult(): mixed
    {
        $url = data_get($this->results, '0.url');

        if (blank($url)) {
            return null;
        }

        return $this->redirect(
            $url,
            navigate: false
        );
    }

    protected function runSearch(): void
    {
        $minimum = (int) config(
            'universal-search.minimum_query_length',
            2
        );

        if (mb_strlen(trim($this->query)) < $minimum) {
            $this->results = [];
            $this->open = filled($this->query);

            return;
        }

        $this->results = app(
            UniversalSearchManager::class
        )->search($this->query);

        $this->open = true;
    }

    public function render(): View
    {
        return view(
            'livewire.universal-global-search'
        );
    }
}
