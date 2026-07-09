<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseNotificationService
{
    public static function send(array $roles, string $title, string $body, string $icon = 'heroicon-o-bell-alert', string $color = 'info'): void
    {
        $users = User::whereHas('roles', fn ($query) => $query->whereIn('name', $roles))->get();

        foreach ($users as $user) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'Filament\\Notifications\\DatabaseNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => $title,
                    'body' => $body,
                    'icon' => $icon,
                    'iconColor' => $color,
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
