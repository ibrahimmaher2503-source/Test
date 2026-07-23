<?php

namespace App\Filament\Resources\InventorySessionResource\Widgets;

use App\Models\InventorySession;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Facades\DB;

class CountingVelocityChart extends LineChartWidget
{
    public ?InventorySession $record = null;

    public function getHeading(): string
    {
        return __('app.dashboard.counting_velocity');
    }

    protected function getData(): array
    {
        $session = $this->record;

        if (! $session || ! $session->started_at) {
            return ['datasets' => [], 'labels' => []];
        }

        // One aggregated row per hour bucket -- never pulls raw scan_events rows.
        $buckets = DB::table('scan_events')
            ->where('session_id', $session->id)
            ->selectRaw('TIMESTAMPDIFF(HOUR, ?, scanned_at) as hour_bucket', [$session->started_at])
            ->selectRaw('COUNT(*) as scans')
            ->groupBy('hour_bucket')
            ->orderBy('hour_bucket')
            ->get();

        $cumulative = 0;
        $labels = [];
        $data = [];

        foreach ($buckets as $bucket) {
            $cumulative += $bucket->scans;
            $labels[] = __('app.dashboard.hour_n', ['n' => $bucket->hour_bucket]);
            $data[] = $cumulative;
        }

        return [
            'datasets' => [
                [
                    'label' => __('app.dashboard.cumulative_scans'),
                    'data' => $data,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
