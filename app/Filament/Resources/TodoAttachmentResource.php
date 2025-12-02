<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TodoAttachmentResource\Pages;
use App\Filament\Resources\TodoAttachmentResource\RelationManagers;
use App\Models\TodoAttachment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TodoAttachmentResource extends Resource
{
    protected static ?string $model = TodoAttachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('todo_id')
                    ->relationship('todo', 'title')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('filename')
                    ->required(),
                Forms\Components\TextInput::make('original_filename')
                    ->required(),
                Forms\Components\TextInput::make('mime_type')
                    ->required(),
                Forms\Components\TextInput::make('file_path')
                    ->required(),
                Forms\Components\TextInput::make('file_size')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('todo.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('filename')
                    ->searchable(),
                Tables\Columns\TextColumn::make('original_filename')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mime_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_size')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTodoAttachments::route('/'),
            'create' => Pages\CreateTodoAttachment::route('/create'),
            'edit' => Pages\EditTodoAttachment::route('/{record}/edit'),
        ];
    }
}
