<?php

namespace App\Filament\Resources\TodoCommentResource\Pages;

use App\Filament\Resources\TodoCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTodoComment extends CreateRecord
{
    protected static string $resource = TodoCommentResource::class;
}
