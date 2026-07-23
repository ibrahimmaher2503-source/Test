<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.branch.nav_label');
    }

    public static function getModelLabel(): string
    {
        return __('app.branch.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.branch.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name_en')
                    ->label(__('app.branch.fields.name_en'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_ar')
                    ->label(__('app.branch.fields.name_ar'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->label(__('app.branch.fields.code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->label(__('app.branch.fields.address'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('phone')
                    ->label(__('app.branch.fields.phone'))
                    ->tel()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('app.branch.fields.is_active'))
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('app.branch.fields.name_en'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('app.branch.fields.name_ar'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('app.branch.fields.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label(__('app.branch.fields.address'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('app.branch.fields.phone'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('app.branch.fields.is_active'))
                    ->boolean(),
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
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
