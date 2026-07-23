<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ScanningScreen;
use App\Models\InventorySession;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ActiveSessionsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->heading(__('app.dashboard.active_sessions'))
            ->query(
                InventorySession::query()
                    ->with('branch')
                    ->withCount('counters')
                    ->where('status', InventorySession::STATUS_IN_PROGRESS)
                    ->when(
                        $user->hasRole(User::ROLE_COUNTER),
                        fn (Builder $query) => $query->whereHas(
                            'counters',
                            fn ($q) => $q->where('users.id', $user->id),
                        ),
                    )
                    ->when(
                        $user->hasRole(User::ROLE_BRANCH_MANAGER),
                        fn (Builder $query) => $query->where('branch_id', $user->branch_id),
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('app.session.fields.reference')),
                Tables\Columns\TextColumn::make('branch.name_en')
                    ->label(__('app.session.fields.branch')),
                Tables\Columns\TextColumn::make('started_at')
                    ->label(__('app.session.fields.started_at'))
                    ->dateTime()
                    ->since(),
                Tables\Columns\TextColumn::make('counters_count')
                    ->label(__('app.session.fields.counters')),
            ])
            ->actions([
                Tables\Actions\Action::make('scan')
                    ->label(__('app.scanning.scan_button'))
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (InventorySession $record): string => ScanningScreen::getUrl(['session' => $record->id])),
            ])
            ->emptyStateHeading(__('app.dashboard.no_active_sessions'))
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->paginated(false);
    }
}
