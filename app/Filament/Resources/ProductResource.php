<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Services\BarcodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.product.nav_label');
    }

    public static function getModelLabel(): string
    {
        return __('app.product.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.product.plural_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Product::query()->where('status', 'pending_review')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->label(__('app.product.fields.sku'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('barcode')
                    ->label(__('app.product.fields.barcode'))
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->default(null)
                    ->helperText('Leave blank and use "Generate barcode" from the table once saved, or enter a value scanned from a real label.')
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null),
                Forms\Components\Hidden::make('barcode_source')
                    ->dehydrateStateUsing(fn (Get $get): string => filled($get('barcode')) ? 'existing' : 'generated'),
                Forms\Components\TextInput::make('name_en')
                    ->label(__('app.product.fields.name_en'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_ar')
                    ->label(__('app.product.fields.name_ar'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category_id')
                    ->label(__('app.product.fields.category'))
                    ->relationship('category', 'name_en')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('unit')
                    ->label(__('app.product.fields.unit'))
                    ->options([
                        'pcs' => 'pcs',
                        'box' => 'box',
                        'kg' => 'kg',
                    ])
                    ->native(false)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('app.product.fields.status'))
                    ->options([
                        'active' => __('app.product.status.active'),
                        'pending_review' => __('app.product.status.pending_review'),
                        'inactive' => __('app.product.status.inactive'),
                    ])
                    ->default('active')
                    ->native(false)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('app.product.fields.is_active'))
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label(__('app.product.fields.sku'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label(__('app.product.fields.barcode'))
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('app.product.fields.name'))
                    ->description(fn (Product $record): string => $record->name_ar)
                    ->searchable(['name_en', 'name_ar']),
                Tables\Columns\TextColumn::make('category.name_en')
                    ->label(__('app.product.fields.category'))
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label(__('app.product.fields.unit')),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.product.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("app.product.status.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending_review' => 'warning',
                        'inactive' => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('app.product.fields.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.product.fields.status'))
                    ->options([
                        'active' => __('app.product.status.active'),
                        'pending_review' => __('app.product.status.pending_review'),
                        'inactive' => __('app.product.status.inactive'),
                    ]),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label(__('app.product.fields.category'))
                    ->relationship('category', 'name_en'),
                Tables\Filters\TernaryFilter::make('has_barcode')
                    ->label(__('app.product.filters.has_barcode'))
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('barcode'),
                        false: fn ($query) => $query->whereNull('barcode'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('generateBarcode')
                    ->label(__('app.product.actions.generate_barcode'))
                    ->icon('heroicon-o-qr-code')
                    ->visible(fn (Product $record): bool => blank($record->barcode))
                    ->action(function (Product $record): void {
                        $record->update([
                            'barcode' => app(BarcodeService::class)->generateUniqueValue(),
                            'barcode_source' => 'generated',
                        ]);
                    }),
                Tables\Actions\Action::make('printLabel')
                    ->label(__('app.product.actions.print_label'))
                    ->icon('heroicon-o-printer')
                    ->visible(fn (Product $record): bool => filled($record->barcode))
                    ->action(fn (Product $record) => static::printLabels(collect([$record]))),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printLabels')
                        ->label(__('app.product.actions.print_labels'))
                        ->icon('heroicon-o-printer')
                        ->action(fn (\Illuminate\Support\Collection $records) => static::printLabels($records->filter(fn (Product $p) => filled($p->barcode)))),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Renders a DomPDF label sheet for the given products and records a
     * barcode_print_jobs row (SPEC §3.9). Simple first-pass 3-per-row grid layout.
     */
    public static function printLabels(\Illuminate\Support\Collection $products): \Symfony\Component\HttpFoundation\Response
    {
        $barcodeService = app(BarcodeService::class);
        $barcodes = $products->mapWithKeys(fn (Product $product) => [
            $product->id => $barcodeService->toPngDataUri($product->barcode),
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.barcode-labels', [
            'products' => $products->values(),
            'barcodes' => $barcodes,
        ]);

        $fileName = 'barcode-labels-'.now()->format('Ymd-His').'.pdf';
        $filePath = 'barcode-labels/'.$fileName;
        \Illuminate\Support\Facades\Storage::disk('local')->put($filePath, $pdf->output());

        \App\Models\BarcodePrintJob::create([
            'product_ids' => $products->pluck('id')->values()->all(),
            'generated_by' => auth()->id(),
            'file_path' => $filePath,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $fileName,
        );
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('category');
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
