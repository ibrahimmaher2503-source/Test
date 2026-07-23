<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\ProductBranchStock;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductBranchStockGrid extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string $view = 'filament.pages.product-branch-stock-grid';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_BRANCH_MANAGER]) ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('app.stock.nav_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.catalog');
    }

    public function getTitle(): string
    {
        return __('app.stock.title');
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole(User::ROLE_SUPER_ADMIN);

        return $table
            ->query(
                ProductBranchStock::query()
                    ->with(['product', 'branch'])
                    ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('branch_id', $user->branch_id))
            )
            ->columns([
                Tables\Columns\TextColumn::make('branch.name_en')
                    ->label(__('app.stock.fields.branch'))
                    ->visible($isSuperAdmin)
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.sku')
                    ->label(__('app.stock.fields.sku'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name_en')
                    ->label(__('app.stock.fields.product'))
                    ->description(fn (ProductBranchStock $record): string => $record->product->name_ar)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('expected_quantity')
                    ->label(__('app.stock.fields.expected_quantity'))
                    ->type('number')
                    ->rules(['required', 'integer', 'min:0'])
                    ->updateStateUsing(function (ProductBranchStock $record, $state): int {
                        $quantity = max(0, (int) $state);

                        $record->update([
                            'expected_quantity' => $quantity,
                            'updated_by' => auth()->id(),
                        ]);

                        return $quantity;
                    }),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label(__('app.stock.fields.updated_by'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('app.stock.fields.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label(__('app.stock.fields.branch'))
                    ->options(Branch::query()->pluck('name_en', 'id'))
                    ->visible($isSuperAdmin),
            ]);
    }
}
