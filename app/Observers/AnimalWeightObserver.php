<?php

namespace App\Observers;

use App\Models\AnimalWeight;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnimalWeightObserver
{
    public function created(AnimalWeight $weight): void
    {
        $animal = $weight->animal;

        if (! $animal) {
            return;
        }

        $actorName = auth()->user()?->name ?? 'System';

        $previousWeight = AnimalWeight::query()
            ->where('animal_id', $weight->animal_id)
            ->where('id', '!=', $weight->id)
            ->whereNull('deleted_at')
            ->where('recorded_at', '<', $weight->recorded_at)
            ->latest('recorded_at')
            ->first();

        $title = 'Animal Weight Recorded';
        $body = "{$actorName} recorded {$animal->tag_number} weight as " . number_format((float) $weight->weight_kg, 2) . " KG.";

        $iconColor = 'success';
        $color = 'success';

        if ($previousWeight) {
            $difference = (float) $weight->weight_kg - (float) $previousWeight->weight_kg;

            if ($difference > 0) {
                $title = 'Animal Gained Weight';
                $body = "{$actorName} recorded {$animal->tag_number} at " . number_format((float) $weight->weight_kg, 2) .
                    " KG. Gained " . number_format(abs($difference), 2) . " KG from previous record.";

                $iconColor = 'success';
                $color = 'success';
            } elseif ($difference < 0) {
                $title = 'Animal Losing Weight';
                $body = "{$actorName} recorded {$animal->tag_number} at " . number_format((float) $weight->weight_kg, 2) .
                    " KG. Lost " . number_format(abs($difference), 2) . " KG from previous record.";

                $iconColor = 'danger';
                $color = 'danger';
            } else {
                $title = 'Animal Weight Stable';
                $body = "{$actorName} recorded {$animal->tag_number} at " . number_format((float) $weight->weight_kg, 2) .
                    " KG. No weight change from previous record.";

                $iconColor = 'warning';
                $color = 'warning';
            }
        }

        $this->sendNotification(
            title: $title,
            body: $body,
            icon: 'heroicon-o-scale',
            iconColor: $iconColor,
            color: $color
        );
    }

    public function updated(AnimalWeight $weight): void
    {
        $changes = $weight->getChanges();

        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $animal = $weight->animal;

        if (! $animal) {
            return;
        }

        $actorName = auth()->user()?->name ?? 'System';

        $title = 'Animal Weight Updated';
        $body = "{$actorName} updated weight record for {$animal->tag_number}.";

        $iconColor = 'info';
        $color = 'info';

        if ($weight->wasChanged('weight_kg')) {
            $old = $weight->getOriginal('weight_kg');
            $new = $weight->weight_kg;

            $title = 'Animal Weight Corrected';
            $body = "{$actorName} changed {$animal->tag_number} weight from " .
                number_format((float) $old, 2) . " KG to " .
                number_format((float) $new, 2) . " KG.";
        }

        if ($weight->wasChanged('recorded_at')) {
            $title = 'Animal Weight Date Updated';
            $body = "{$actorName} updated the recorded date/time for {$animal->tag_number}.";
        }

        $this->sendNotification(
            title: $title,
            body: $body,
            icon: 'heroicon-o-pencil-square',
            iconColor: $iconColor,
            color: $color
        );
    }

    public function deleted(AnimalWeight $weight): void
    {
        $animal = $weight->animal;

        if (! $animal) {
            return;
        }

        $actorName = auth()->user()?->name ?? 'System';

        $this->sendNotification(
            title: 'Animal Weight Deleted',
            body: "{$actorName} moved weight record for {$animal->tag_number} to trash.",
            icon: 'heroicon-o-trash',
            iconColor: 'danger',
            color: 'danger'
        );
    }

    public function restored(AnimalWeight $weight): void
    {
        $animal = $weight->animal;

        if (! $animal) {
            return;
        }

        $actorName = auth()->user()?->name ?? 'System';

        $this->sendNotification(
            title: 'Animal Weight Restored',
            body: "{$actorName} restored weight record for {$animal->tag_number}.",
            icon: 'heroicon-o-arrow-path',
            iconColor: 'success',
            color: 'success'
        );
    }

    protected function sendNotification(string $title, string $body, string $icon, string $iconColor, string $color): void
    {
        $users = User::whereHas('roles', fn ($query) => $query->whereIn('name', [
            'Admin',
            'Manager',
            'Vet',
        ]))->get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'Filament\Notifications\DatabaseNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => $title,
                    'body' => $body,
                    'icon' => $icon,
                    'iconColor' => $iconColor,
                    'color' => $color,
                    'status' => $color,
                    'duration' => 'persistent',
                    'actions' => [],
                    'view' => 'filament-notifications::notification',
                    'viewData' => [],
                    'format' => 'filament',
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
