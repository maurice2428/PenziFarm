<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    protected string $cacheKey = 'system_settings';

    public function all(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Cache::rememberForever($this->cacheKey, function () {
            return Setting::pluck('value', 'key')->toArray();
        });
    }

    public function get(string $key, $default = null)
    {
        return $this->all()[$key] ?? $default;
    }

    public function setMany(array $data, $userId = null): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        foreach ($data as $key => $value) {
            $normalizedValue = $this->normalizeValue($value);

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $normalizedValue,
                    'updated_by' => $userId,
                ]
            );
        }

        Cache::forget($this->cacheKey);
    }

    protected function normalizeValue($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            if (empty($value)) {
                return null;
            }

            $first = reset($value);

            if (is_string($first) && filled($first)) {
                return $first;
            }

            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return filled($value) ? (string) $value : null;
    }
}
