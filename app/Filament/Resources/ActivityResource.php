<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\RelationManagers;
use App\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Activity')
                    ->schema([
                        Forms\Components\TextInput::make('type')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('actor_name')
                            ->label('Actor name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('actor_email')
                            ->label('Actor email')
                            ->email()
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\TextInput::make('target_name')
                            ->label('Target')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Links')
                    ->schema([
                        Forms\Components\TextInput::make('actor_id')
                            ->numeric()
                            ->label('Actor ID'),
                        Forms\Components\TextInput::make('target_id')
                            ->numeric()
                            ->label('Target ID')
                            ->nullable(),
                        Forms\Components\TextInput::make('project_id')
                            ->numeric()
                            ->label('Project ID')
                            ->nullable(),
                        Forms\Components\TextInput::make('company_id')
                            ->numeric()
                            ->label('Company ID')
                            ->nullable(),
                    ])->columns(4),
                Forms\Components\KeyValue::make('metadata')
                    ->label('Metadata')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
