<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\InventoryCountLine;
use App\Models\InventorySession;
use App\Models\User;
use Filament\Widgets\BarChartWidget;

class VarianceOverviewChart extends BarChartWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_BRANCH_MANAGER]) ?? false;
    }

    public function getHeading(): string
    {
        return __('app.dashboard.variance_overview');
    }

    protected function getFilters(): ?array
    {
        if (! auth()->user()->hasRole(User::ROLE_SUPER_ADMIN)) {
            return null;
        }

        return Branch::query()->pluck('name_en', 'id')->all();
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole(User::ROLE_SUPER_ADMIN);

        $sessions = InventorySession::query()
            ->when(! $isSuperAdmin, fn ($query) => $query->where('branch_id', $user->branch_id))
            ->when($isSuperAdmin && $this->filter, fn ($query) => $query->where('branch_id', $this->filter))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'reference']);

        $sessionIds = $sessions->pluck('id');

        // Aggregate in SQL rather than pulling every count line into PHP.
        $counts = InventoryCountLine::query()
            ->whereIn('session_id', $sessionIds)
            ->selectRaw('session_id')
            ->selectRaw('SUM(CASE WHEN counted_quantity = expected_quantity_snapshot THEN 1 ELSE 0 END) as matched')
            ->selectRaw('SUM(CASE WHEN counted_quantity != expected_quantity_snapshot THEN 1 ELSE 0 END) as variant')
            ->groupBy('session_id')
            ->get()
            ->keyBy('session_id');

        return [
            'datasets' => [
                [
                    'label' => __('app.dashboard.variance_zero'),
                    'data' => $sessions->map(fn (InventorySession $s) => (int) ($counts[$s->id]->matched ?? 0))->all(),
                    'backgroundColor' => '#22c55e',
                ],
                [
                    'label' => __('app.dashboard.variance_nonzero'),
                    'data' => $sessions->map(fn (InventorySession $s) => (int) ($counts[$s->id]->variant ?? 0))->all(),
                    'backgroundColor' => '#ef4444',
                ],
            ],
            'labels' => $sessions->pluck('reference')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
        ];
    }
}
