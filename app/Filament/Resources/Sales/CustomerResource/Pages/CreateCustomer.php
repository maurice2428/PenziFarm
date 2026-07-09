<?php

/*
 * namespace App\Filament\Resources\Sales\CustomerResource\Pages;
 *
 * use App\Filament\Resources\Sales\CustomerResource;
 * use Filament\Resources\Pages\CreateRecord;
 *
 * class CreateCustomer extends CreateRecord
 * {
 *     protected static string $resource = CustomerResource::class;
 *
 *     public ?string $returnUrl = null;
 *
 *     public function mount(): void
 *     {
 *         parent::mount();
 *
 *         $this->returnUrl = request()->query('return_url');
 *     }
 *
 *     protected function getRedirectUrl(): string
 *     {
 *         return $this->returnUrl ?: static::getResource()::getUrl('index');
 *     }
 * }
 */

namespace App\Filament\Resources\Sales\CustomerResource\Pages;

use App\Filament\Resources\Sales\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    public ?string $returnUrl = null;

    public function mount(): void
    {
        parent::mount();

        $this->returnUrl = $this->cleanReturnUrl(
            request()->query('return_url'),
            static::getResource()::getUrl('index')
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->returnUrl ?: static::getResource()::getUrl('index');
    }

    protected function cleanReturnUrl(?string $url, string $fallback): string
    {
        if (!is_string($url) || trim($url) === '') {
            return $fallback;
        }

        $url = urldecode(trim($url));

        if (
            str_contains($url, '/livewire/update') ||
            str_contains($url, '/livewire/') ||
            str_contains($url, 'livewire%2Fupdate')
        ) {
            return $fallback;
        }

        if (str_starts_with($url, '//')) {
            return $fallback;
        }

        if (str_starts_with($url, '/')) {
            return url($url);
        }

        $urlHost = parse_url($url, PHP_URL_HOST);
        $requestHost = request()->getHost();

        if ($urlHost && $urlHost !== $requestHost) {
            return $fallback;
        }

        return $url;
    }
}
