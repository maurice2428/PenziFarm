<?php

namespace App\Livewire\Filament;

use App\Models\User;
use Livewire\Component;

class TopbarSearch extends Component
{
    public string $query = '';
    public array $results = [];
    public array $recent = [];

    public function mount(): void
    {
        $this->recent = session('recent_global_searches', []);
    }

    public function updatedQuery(): void
    {
        $this->query = trim($this->query);

        if ($this->query === '') {
            $this->results = [];
            return;
        }

        $this->results = collect()
            ->merge(
                User::query()
                    ->when(
                        $this->query,
                        fn ($q) => $q->where('name', 'like', "%{$this->query}%")
                            ->orWhere('email', 'like', "%{$this->query}%")
                    )
                    ->limit(5)
                    ->get()
                    ->map(fn ($user) => [
                        'title' => $user->name,
                        'subtitle' => $user->email,
                        'type' => 'User',
                        'url' => \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $user]),
                    ])
            )
            ->take(12)
            ->values()
            ->all();
    }

 public function openResult(string $url, string $title)
{
    $recent = session('recent_global_searches', []);
    array_unshift($recent, $title);
    $recent = array_slice(array_values(array_unique($recent)), 0, 6);

    session(['recent_global_searches' => $recent]);

    return $this->redirect($url, navigate: true);
}

    public function render()
    {
        return view('livewire.filament.topbar-search', [
            'query' => $this->query,
            'results' => $this->results,
            'recent' => $this->recent,
        ]);
    }
}
