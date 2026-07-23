<?php

namespace App\Filament\Resources\InventorySessionResource\Pages;

use App\Filament\Resources\InventorySessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventorySession extends EditRecord
{
    protected static string $resource = InventorySessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
