<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('key_contact_name')
                            ->label('Contact name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('key_contact_email')
                            ->label('Contact email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('key_contact_phone')
                            ->label('Contact phone')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(2),
                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\TextInput::make('address_line_1')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address_line_2')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\TextInput::make('city')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\TextInput::make('state_province')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\TextInput::make('postal_code')
                            ->maxLength(50)
                            ->nullable(),
                        Forms\Components\TextInput::make('country')
                            ->maxLength(255)
                            ->nullable(),
                    ])->columns(3),
                Forms\Components\Textarea::make('notes')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('key_contact_name')
                    ->label('Contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('key_contact_email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
