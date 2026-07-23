<?php

namespace App\Filament\Resources\InventorySessionResource\Pages;

use App\Filament\Resources\InventorySessionResource;
use App\Filament\Resources\InventorySessionResource\Widgets\CountingVelocityChart;
use App\Models\InventorySession;
use Filament\Resources\Pages\ViewRecord;

class ViewInventorySession extends ViewRecord
{
    protected static string $resource = InventorySessionResource::class;

    protected function getHeaderWidgets(): array
    {
        if (! in_array($this->getRecord()->status, [
            InventorySession::STATUS_IN_PROGRESS,
            InventorySession::STATUS_SUBMITTED,
        ], true)) {
            return [];
        }

        return [
            CountingVelocityChart::class,
        ];
    }
}
