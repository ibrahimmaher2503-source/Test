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

    protected static string $view = 'filament.pages.my-inventory-sessions';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            User::ROLE_SUPER_ADMIN,
            User::ROLE_BRANCH_MANAGER,
            User::ROLE_COUNTER,
        ]) ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('app.scanning.nav_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.inventory');
    }

    public function getTitle(): string
    {
        return __('app.scanning.nav_label');
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
                Tables\Columns\TextColumn::make('reference')->label(__('app.session.fields.reference')),
                Tables\Columns\TextColumn::make('branch.name_en')->label(__('app.session.fields.branch')),
                Tables\Columns\TextColumn::make('started_at')->label(__('app.session.fields.started_at'))->dateTime(),
                Tables\Columns\TextColumn::make('counters_count')->counts('counters')->label(__('app.session.fields.counters')),
            ])
            ->actions([
                Tables\Actions\Action::make('scan')
                    ->label(__('app.scanning.scan_button'))
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (InventorySession $record): string => ScanningScreen::getUrl(['session' => $record->id])),
            ]);
    }
}
