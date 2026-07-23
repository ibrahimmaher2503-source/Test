<?php

namespace App\Filament\Resources\InventorySessionResource\RelationManagers;

use App\Models\InventoryCountLine;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CountLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'countLines';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('app.count_lines.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name_en')
            ->emptyStateHeading(__('app.count_lines.title'))
            ->columns([
                Tables\Columns\TextColumn::make('product.sku')
                    ->label(__('app.count_lines.fields.sku')),
                Tables\Columns\TextColumn::make('product.name_en')
                    ->label(__('app.count_lines.fields.product'))
                    ->description(fn (InventoryCountLine $record): string => $record->product->name_ar),
                Tables\Columns\TextColumn::make('expected_quantity_snapshot')
                    ->label(__('app.count_lines.fields.expected')),
                Tables\Columns\TextColumn::make('counted_quantity')
                    ->label(__('app.count_lines.fields.counted')),
                Tables\Columns\TextColumn::make('variance')
                    ->label(__('app.count_lines.fields.variance'))
                    ->badge()
                    ->color(fn (InventoryCountLine $record): string => match (true) {
                        $record->variance === 0 => 'success',
                        $record->variance > 0 => 'info',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('lastScannedBy.name')
                    ->label(__('app.count_lines.fields.last_scanned_by'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('last_scanned_at')
                    ->label(__('app.count_lines.fields.last_scanned_at'))
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_variance')
                    ->label(__('app.count_lines.filters.has_variance'))
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
