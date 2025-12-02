<?php

namespace App\Filament\Resources\TodoCommentResource\Pages;

use App\Filament\Resources\TodoCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTodoComments extends ListRecords
{
    protected static string $resource = TodoCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
