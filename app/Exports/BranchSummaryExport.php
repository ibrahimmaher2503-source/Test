<?php

namespace App\Exports;

use App\Services\BranchSummaryReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BranchSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $branches,
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    public function collection()
    {
        return app(BranchSummaryReportService::class)->build($this->branches, $this->from, $this->to);
    }

    public function headings(): array
    {
        return ['Branch', 'Sessions', 'Total items counted', 'Total variance', 'Pending review products'];
    }

    public function map($row): array
    {
        return [
            $row['branch']->name_en,
            (string) $row['sessions_count'],
            (string) $row['total_counted'],
            (string) $row['total_variance'],
            (string) $row['pending_review_count'],
        ];
    }
}
