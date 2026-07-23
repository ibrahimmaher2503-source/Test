<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingReviewBacklog extends BaseWidget
{
    protected function getStats(): array
    {
        $count = Product::query()->where('status', 'pending_review')->count();

        return [
            Stat::make(__('app.dashboard.pending_review'), $count)
                ->description(__('app.dashboard.pending_review_desc'))
                ->icon('heroicon-o-exclamation-triangle')
                ->color($count > 0 ? 'warning' : 'gray'),
        ];
    }
}
