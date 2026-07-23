<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\InventorySession;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user->hasRole(User::ROLE_COUNTER)) {
            $activeCount = $user->assignedSessions()
                ->where('status', InventorySession::STATUS_IN_PROGRESS)
                ->count();

            return [
                Stat::make(__('app.dashboard.my_active_sessions'), $activeCount)
                    ->description(__('app.dashboard.my_active_sessions_desc'))
                    ->icon('heroicon-o-qr-code')
                    ->color($activeCount > 0 ? 'info' : 'gray'),
            ];
        }

        $isSuperAdmin = $user->hasRole(User::ROLE_SUPER_ADMIN);

        $sessionsQuery = InventorySession::query()->where('status', InventorySession::STATUS_IN_PROGRESS);
        $pendingQuery = Product::query()->where('status', 'pending_review');

        if (! $isSuperAdmin) {
            $sessionsQuery->where('branch_id', $user->branch_id);
            $pendingQuery->whereHas(
                'countLines.session',
                fn ($query) => $query->where('branch_id', $user->branch_id),
            );
        }

        $stats = [
            Stat::make(__('app.dashboard.sessions_in_progress'), $sessionsQuery->count())
                ->description($isSuperAdmin ? __('app.dashboard.all_branches') : __('app.dashboard.this_branch'))
                ->icon('heroicon-o-clipboard-document-check')
                ->color('info'),
            Stat::make(__('app.dashboard.pending_review'), $pendingQuery->count())
                ->description(__('app.dashboard.pending_review_desc'))
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning'),
            Stat::make(__('app.dashboard.total_products'), Product::query()->count())
                ->description(__('app.dashboard.total_products_desc'))
                ->icon('heroicon-o-cube')
                ->color('gray'),
        ];

        if ($isSuperAdmin) {
            array_unshift(
                $stats,
                Stat::make(__('app.dashboard.branches'), Branch::query()->count())
                    ->icon('heroicon-o-building-office-2')
                    ->color('primary'),
            );
        }

        return $stats;
    }
}
