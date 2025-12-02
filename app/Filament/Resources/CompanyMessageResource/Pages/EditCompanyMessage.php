<?php

namespace App\Filament\Resources\CompanyMessageResource\Pages;

use App\Filament\Resources\CompanyMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompanyMessage extends EditRecord
{
    protected static string $resource = CompanyMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
