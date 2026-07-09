<?php

namespace App\Filament\Resources\HR\JobTitleResource\Pages;

use App\Filament\Resources\HR\JobTitleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobTitle extends EditRecord
{
    protected static string $resource = JobTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
