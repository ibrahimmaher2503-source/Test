<?php

namespace App\Filament\Resources\InventorySessionResource\Pages;

use App\Filament\Resources\InventorySessionResource;
use App\Models\Branch;
use App\Models\InventorySession;
use App\Services\InventorySessionReferenceGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateInventorySession extends CreateRecord
{
    protected static string $resource = InventorySessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $branch = Branch::findOrFail($data['branch_id']);

        $data['reference'] = app(InventorySessionReferenceGenerator::class)->generate($branch);
        $data['status'] = InventorySession::STATUS_DRAFT;
        $data['created_by'] = auth()->id();

        return $data;
    }
}
