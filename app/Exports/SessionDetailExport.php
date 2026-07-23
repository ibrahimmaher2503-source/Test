<?php

namespace App\Exports;

use App\Models\InventoryCountLine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SessionDetailExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly int $sessionId) {}

    public function collection()
    {
        return InventoryCountLine::query()
            ->with(['product', 'lastScannedBy'])
            ->where('session_id', $this->sessionId)
            ->get();
    }

    public function headings(): array
    {
        return ['SKU', 'Barcode', 'Product (EN)', 'Product (AR)', 'Expected', 'Counted', 'Variance', 'Last scanned by', 'Last scanned at'];
    }

    public function map($line): array
    {
        return [
            $line->product->sku,
            $line->product->barcode,
            $line->product->name_en,
            $line->product->name_ar,
            (string) $line->expected_quantity_snapshot,
            (string) $line->counted_quantity,
            (string) $line->variance,
            $line->lastScannedBy?->name,
            $line->last_scanned_at?->toDateTimeString(),
        ];
    }
}
