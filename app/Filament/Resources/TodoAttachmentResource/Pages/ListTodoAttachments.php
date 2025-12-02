<?php

namespace App\Filament\Resources\TodoAttachmentResource\Pages;

use App\Filament\Resources\TodoAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTodoAttachments extends ListRecords
{
    protected static string $resource = TodoAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
