<?php

namespace App\Filament\Resources\TodoAttachmentResource\Pages;

use App\Filament\Resources\TodoAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTodoAttachment extends CreateRecord
{
    protected static string $resource = TodoAttachmentResource::class;
}
