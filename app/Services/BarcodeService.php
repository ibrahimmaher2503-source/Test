<?php

namespace App\Services;

use App\Models\Product;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeService
{
    /**
     * Generate a unique numeric value suitable for a Code128 barcode. Retries on the
     * rare collision against products.barcode's unique index.
     */
    public function generateUniqueValue(): string
    {
        do {
            $value = (string) random_int(100000000000, 999999999999);
        } while (Product::query()->where('barcode', $value)->exists());

        return $value;
    }

    /**
     * Render a barcode value as a base64 PNG data URI, embeddable directly in a
     * DomPDF view via <img src="...">.
     */
    public function toPngDataUri(string $value): string
    {
        $generator = new BarcodeGeneratorPNG;
        $png = $generator->getBarcode($value, $generator::TYPE_CODE_128, 2, 60);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
