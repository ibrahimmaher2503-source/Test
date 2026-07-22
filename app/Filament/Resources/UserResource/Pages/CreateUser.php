<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $roleToAssign = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->roleToAssign = $data['role'];
        unset($data['role']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncRoles([$this->roleToAssign]);
    }
}
