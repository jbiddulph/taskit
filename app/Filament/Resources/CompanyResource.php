<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->maxLength(50)
                            ->disabled() // generated
                            ->dehydrated(false),
                        Forms\Components\Select::make('subscription_type')
                            ->label('Subscription plan')
                            ->options([
                                'FREE' => 'Free',
                                'MIDI' => 'Midi',
                                'MAXI' => 'Maxi',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_public')
                            ->label('Public company'),
                    ])->columns(2),
                Forms\Components\Section::make('Branding')
                    ->schema([
                        Forms\Components\TextInput::make('logo_url')
                            ->label('Logo URL')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\TextInput::make('subdomain')
                            ->maxLength(255)
                            ->helperText('Subdomain part only, e.g. "acme" for acme.zaptask.co.uk')
                            ->nullable(),
                        Forms\Components\TextInput::make('subdomain_url')
                            ->maxLength(255)
                            ->nullable()
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(3),
                Forms\Components\Section::make('Pruning & Limits')
                    ->schema([
                        Forms\Components\Toggle::make('prune_completed_tasks')
                            ->label('Prune completed tasks automatically'),
                    ]),
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
                Tables\Columns\TextColumn::make('code')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subscription_type')
                    ->label('Plan')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->label('Public')
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
