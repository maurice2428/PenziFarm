<?php

namespace App\View\Components;

use App\Models\AnimalBreedingReview;
use App\Services\ProgenyAnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BreedingPerformanceDashboard extends Component
{
    public function render(): View
    {
        $analytics = app(ProgenyAnalyticsService::class);

        $latestReviews = AnimalBreedingReview::query()
            ->with(['animal.breed'])
            ->latest('reviewed_at')
            ->latest('id')
            ->get()
            ->unique('animal_id')
            ->values();

        return view('components.breeding-performance-dashboard', [
            'topSires' => $analytics->topSires(4),
            'topDams' => $analytics->topDams(4),
            'sellRecommendations' => $latestReviews->where('recommendation', 'sell')->values(),
            'cullRecommendations' => $latestReviews->where('recommendation', 'cull')->values(),
        ]);
    }
}
