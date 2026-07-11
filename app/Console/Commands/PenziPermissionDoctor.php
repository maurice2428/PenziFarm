<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PenziPermissionDoctor extends Command
{
    protected $signature = 'penzi-permissions:doctor {--user= : User email to inspect}';
    protected $description = 'Audit the menu permissions for health, gestation, inventory, accounting controls and Kenya tax.';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $required = [
            'view health products', 'view gestation rules', 'view inventory items',
            'view stock movements', 'view stock adjustments',
            'view accounting reconciliations', 'view accounting source postings',
            'view accounting posting failures', 'view kenya tax compliance',
            'view accounting tax settings', 'view accounting tax transactions',
        ];

        $rows = collect($required)->map(function (string $permission): array {
            return [
                $permission,
                Permission::query()->where('name', $permission)->where('guard_name', 'web')->exists() ? 'YES' : 'NO',
                Role::query()->whereIn('name', ['Administrator', 'Admin'])
                    ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
                    ->exists() ? 'YES' : 'NO',
            ];
        })->all();

        $this->table(['Permission', 'Exists', 'Admin Role Has It'], $rows);

        if ($email = $this->option('user')) {
            $user = \App\Models\User::query()->where('email', $email)->first();

            if (! $user) {
                $this->error("User {$email} was not found.");
                return self::FAILURE;
            }

            $this->newLine();
            $this->line('User: ' . $user->name . ' <' . $user->email . '>');
            $this->line('Roles: ' . $user->getRoleNames()->implode(', '));
            $this->table(
                ['Permission', 'Allowed'],
                collect($required)->map(fn (string $permission): array => [
                    $permission,
                    $user->can($permission) ? 'YES' : 'NO',
                ])->all()
            );
        }

        $healthy = collect($rows)->every(
            fn (array $row): bool => $row[1] === 'YES' && $row[2] === 'YES'
        );

        $this->newLine();
        $this->line('Overall status: ' . ($healthy ? 'HEALTHY' : 'REVIEW REQUIRED'));

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
