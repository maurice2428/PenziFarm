<?php

namespace App\Observers;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnimalObserver
{
    public function updated(Animal $animal): void
    {
        $changes = $animal->getChanges();

        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $actorName = auth()->user()?->name ?? 'System';

        $users = User::whereHas('roles', fn ($query) => $query->whereIn('name', [
            'Admin',
            'Manager',
            'Vet',
        ]))->get();

        if ($users->isEmpty()) {
            return;
        }

        $title = 'Animal Record Updated';
        $body = "{$actorName} updated animal {$animal->tag_number}.";

        if ($animal->wasChanged('status')) {
            $title = match ($animal->status) {
                'Sold' => 'Animal Sold',
                'Dead' => 'Animal Marked Dead',
                'Culled' => 'Animal Culled',
                default => 'Animal Status Changed',
            };

            $body = "{$actorName} changed animal {$animal->tag_number} status to {$animal->status}.";
        }

        if ($animal->wasChanged('sale_ready')) {
            $title = 'Animal Sale Status Updated';

            $body = $animal->sale_ready
                ? "{$actorName} marked animal {$animal->tag_number} as sale ready."
                : "{$actorName} removed sale-ready status from animal {$animal->tag_number}.";
        }

        if ($animal->wasChanged('is_archived')) {
            $title = 'Animal Archive Updated';

            $body = $animal->is_archived
                ? "{$actorName} archived animal {$animal->tag_number}."
                : "{$actorName} restored animal {$animal->tag_number} from archive.";
        }

        if ($animal->wasChanged('valuation_price')) {
            $title = 'Animal Valuation Updated';
            $body = "{$actorName} updated valuation for animal {$animal->tag_number} to KES " . number_format((float) $animal->valuation_price, 2) . ".";
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
                    'icon' => 'heroicon-o-bell-alert',
                    'iconColor' => 'info',
                    'color' => 'info',
                    'status' => 'info',
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
