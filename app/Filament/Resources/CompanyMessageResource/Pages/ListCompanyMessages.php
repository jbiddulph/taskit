<?php

namespace App\Filament\Resources\CompanyMessageResource\Pages;

use App\Filament\Resources\CompanyMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanyMessages extends ListRecords
{
    protected static string $resource = CompanyMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
