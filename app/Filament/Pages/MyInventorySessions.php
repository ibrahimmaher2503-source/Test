<?php

namespace App\Filament\Pages;

use App\Models\InventorySession;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyInventorySessions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'My Sessions';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $title = 'My Sessions';

    protected static string $view = 'filament.pages.my-inventory-sessions';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            User::ROLE_SUPER_ADMIN,
            User::ROLE_BRANCH_MANAGER,
            User::ROLE_COUNTER,
        ]) ?? false;
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                InventorySession::query()
                    ->where('status', InventorySession::STATUS_IN_PROGRESS)
                    ->when(
                        $user->hasRole(User::ROLE_COUNTER),
                        fn (Builder $query) => $query->whereHas('counters', fn ($q) => $q->where('users.id', $user->id)),
                    )
                    ->when(
                        $user->hasRole(User::ROLE_BRANCH_MANAGER),
                        fn (Builder $query) => $query->where('branch_id', $user->branch_id),
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference'),
                Tables\Columns\TextColumn::make('branch.name_en')->label('Branch'),
                Tables\Columns\TextColumn::make('started_at')->dateTime(),
                Tables\Columns\TextColumn::make('counters_count')->counts('counters')->label('Counters'),
            ])
            ->actions([
                Tables\Actions\Action::make('scan')
                    ->label('Scan')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (InventorySession $record): string => ScanningScreen::getUrl(['session' => $record->id])),
            ]);
    }
}
