<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\BarChartWidget;
use Illuminate\Support\Facades\DB;

class BranchVarianceComparisonChart extends BarChartWidget
{
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_SUPER_ADMIN) ?? false;
    }

    public function getHeading(): string
    {
        return __('app.dashboard.branch_comparison');
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => __('app.dashboard.last_7_days'),
            '30' => __('app.dashboard.last_30_days'),
            '90' => __('app.dashboard.last_90_days'),
            'all' => __('app.dashboard.all_time'),
        ];
    }

    protected function getData(): array
    {
        $from = match ($this->filter) {
            '7' => now()->subDays(7),
            '90' => now()->subDays(90),
            'all' => null,
            default => now()->subDays(30),
        };

        // Fully aggregated in SQL: join count lines -> sessions -> branches, sum
        // absolute variance per branch, no row-level PHP summing.
        $rows = DB::table('inventory_count_lines')
            ->join('inventory_sessions', 'inventory_sessions.id', '=', 'inventory_count_lines.session_id')
            ->join('branches', 'branches.id', '=', 'inventory_sessions.branch_id')
            ->whereIn('inventory_sessions.status', ['closed', 'approved'])
            ->when($from, fn ($query) => $query->where('inventory_sessions.created_at', '>=', $from))
            ->selectRaw('branches.name_en as branch_name')
            ->selectRaw('SUM(ABS(inventory_count_lines.counted_quantity - inventory_count_lines.expected_quantity_snapshot)) as total_variance')
            ->groupBy('branches.id', 'branches.name_en')
            ->orderBy('branches.name_en')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('app.dashboard.total_variance'),
                    'data' => $rows->pluck('total_variance')->map(fn ($v) => (int) $v)->all(),
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $rows->pluck('branch_name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
