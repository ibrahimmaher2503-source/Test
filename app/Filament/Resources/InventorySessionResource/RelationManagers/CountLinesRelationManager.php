<?php

namespace App\Filament\Resources\InventorySessionResource\RelationManagers;

use App\Models\InventoryCountLine;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CountLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'countLines';

    protected static ?string $title = 'Count lines';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name_en')
            ->columns([
                Tables\Columns\TextColumn::make('product.sku')
                    ->label('SKU'),
                Tables\Columns\TextColumn::make('product.name_en')
                    ->label('Product')
                    ->description(fn (InventoryCountLine $record): string => $record->product->name_ar),
                Tables\Columns\TextColumn::make('expected_quantity_snapshot')
                    ->label('Expected'),
                Tables\Columns\TextColumn::make('counted_quantity')
                    ->label('Counted'),
                Tables\Columns\TextColumn::make('variance')
                    ->label('Variance')
                    ->badge()
                    ->color(fn (InventoryCountLine $record): string => match (true) {
                        $record->variance === 0 => 'success',
                        $record->variance > 0 => 'info',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('lastScannedBy.name')
                    ->label('Last scanned by')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('last_scanned_at')
                    ->label('Last scanned')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_variance')
                    ->label('Variance ≠ 0')
                    ->query(fn ($query) => $query->whereColumn('counted_quantity', '!=', 'expected_quantity_snapshot')),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
