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

    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('barcode')
                    ->maxLength(255)
                    ->default(null)
                    ->helperText('Leave blank and use "Generate barcode" from the table once saved, or enter a value scanned from a real label.')
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null),
                Forms\Components\Hidden::make('barcode_source')
                    ->dehydrateStateUsing(fn (Get $get): string => filled($get('barcode')) ? 'existing' : 'generated'),
                Forms\Components\TextInput::make('name_en')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_ar')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name_en')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('unit')
                    ->options([
                        'pcs' => 'pcs',
                        'box' => 'box',
                        'kg' => 'kg',
                    ])
                    ->native(false)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending_review' => 'Pending review',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->native(false)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->searchable()
                    ->placeholder('— none —'),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name')
                    ->description(fn (Product $record): string => $record->name_ar)
                    ->searchable(['name_en', 'name_ar']),
                Tables\Columns\TextColumn::make('category.name_en')
                    ->label('Category')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending_review' => 'warning',
                        'inactive' => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending_review' => 'Pending review',
                        'inactive' => 'Inactive',
                    ]),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name_en'),
                Tables\Filters\TernaryFilter::make('has_barcode')
                    ->label('Has barcode')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('barcode'),
                        false: fn ($query) => $query->whereNull('barcode'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('generateBarcode')
                    ->label('Generate barcode')
                    ->icon('heroicon-o-qr-code')
                    ->visible(fn (Product $record): bool => blank($record->barcode))
                    ->action(function (Product $record): void {
                        $record->update([
                            'barcode' => app(BarcodeService::class)->generateUniqueValue(),
                            'barcode_source' => 'generated',
                        ]);
                    }),
                Tables\Actions\Action::make('printLabel')
                    ->label('Print label')
                    ->icon('heroicon-o-printer')
                    ->visible(fn (Product $record): bool => filled($record->barcode))
                    ->action(fn (Product $record) => static::printLabels(collect([$record]))),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printLabels')
                        ->label('Print labels')
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
