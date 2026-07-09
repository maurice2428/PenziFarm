<?php

namespace App\Support;

class SafeReturnUrl
{
    public static function make(?string $fallback = null): string
    {
        $fallback ??= url('/admin');

        $candidate = request()->headers->get('referer')
            ?: request()->query('return_url')
            ?: url()->previous()
            ?: $fallback;

        return self::clean($candidate, $fallback);
    }

    public static function fromQuery(?string $fallback = null): string
    {
        $fallback ??= url('/admin');

        return self::clean(request()->query('return_url'), $fallback);
    }

    public static function clean(?string $url, ?string $fallback = null): string
    {
        $fallback ??= url('/admin');

        if (! is_string($url) || trim($url) === '') {
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
