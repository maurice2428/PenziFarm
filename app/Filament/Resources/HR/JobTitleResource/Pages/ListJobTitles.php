<?php

namespace App\Filament\Resources\HR\JobTitleResource\Pages;

use App\Filament\Resources\HR\JobTitleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJobTitles extends ListRecords
{
    protected static string $resource = JobTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
