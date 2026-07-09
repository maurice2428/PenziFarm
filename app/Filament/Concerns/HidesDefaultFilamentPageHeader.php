<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;

trait HidesDefaultFilamentPageHeader
{
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }
}
