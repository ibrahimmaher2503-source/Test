<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventorySessionResource\Pages;
use App\Filament\Resources\InventorySessionResource\RelationManagers\CountLinesRelationManager;
use App\Models\InventorySession;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventorySessionResource extends Resource
{
    protected static ?string $model = InventorySession::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.session.nav_label');
    }

    public static function getModelLabel(): string
    {
        return __('app.session.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.session.plural_label');
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isBranchManager = $user->hasRole(User::ROLE_BRANCH_MANAGER);

        return $form
            ->schema([
                Forms\Components\Select::make('branch_id')
                    ->label(__('app.session.fields.branch'))
                    ->relationship('branch', 'name_en')
                    ->default($isBranchManager ? $user->branch_id : null)
                    ->disabled(fn (string $operation): bool => $isBranchManager || $operation === 'edit')
                    ->dehydrated()
                    ->required()
                    ->live(),
                Forms\Components\Select::make('counters')
                    ->label(__('app.session.fields.counters'))
                    ->relationship('counters', 'name')
                    ->options(function (Get $get) {
                        $branchId = $get('branch_id');

                        if (! $branchId) {
                            return [];
                        }

                        return User::query()
                            ->role(User::ROLE_COUNTER)
                            ->where('branch_id', $branchId)
                            ->pluck('name', 'id');
                    })
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Only counters from the selected branch are listed.'),
                Forms\Components\Textarea::make('notes')
                    ->label(__('app.session.fields.notes'))
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('reference')->label(__('app.session.fields.reference')),
                TextEntry::make('branch.name_en')->label(__('app.session.fields.branch')),
                TextEntry::make('status')->label(__('app.session.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => __("app.session.status.{$state}")),
                TextEntry::make('createdBy.name')->label(__('app.session.fields.created_by')),
                TextEntry::make('counters.name')->label(__('app.session.fields.counters'))->listWithLineBreaks(),
                TextEntry::make('started_at')->label(__('app.session.fields.started_at'))->dateTime()->placeholder('—'),
                TextEntry::make('submitted_at')->label(__('app.session.fields.submitted_at'))->dateTime()->placeholder('—'),
                TextEntry::make('approvedBy.name')->label(__('app.session.fields.approved_by'))->placeholder('—'),
                TextEntry::make('approved_at')->label(__('app.session.fields.approved_at'))->dateTime()->placeholder('—'),
                TextEntry::make('notes')->label(__('app.session.fields.notes'))->placeholder('—')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('app.session.fields.reference'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch.name_en')
                    ->label(__('app.session.fields.branch'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.session.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("app.session.status.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        InventorySession::STATUS_DRAFT => 'gray',
                        InventorySession::STATUS_IN_PROGRESS => 'info',
                        InventorySession::STATUS_SUBMITTED => 'warning',
                        InventorySession::STATUS_APPROVED, InventorySession::STATUS_CLOSED => 'success',
                    }),
                Tables\Columns\TextColumn::make('counters_count')
                    ->counts('counters')
                    ->label(__('app.session.fields.counters')),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('app.session.fields.created_by')),
                Tables\Columns\TextColumn::make('started_at')
                    ->label(__('app.session.fields.started_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label(__('app.session.fields.submitted_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label(__('app.session.fields.approved_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.session.fields.status'))
                    ->options([
                        InventorySession::STATUS_DRAFT => __('app.session.status.draft'),
                        InventorySession::STATUS_IN_PROGRESS => __('app.session.status.in_progress'),
                        InventorySession::STATUS_SUBMITTED => __('app.session.status.submitted'),
                        InventorySession::STATUS_APPROVED => __('app.session.status.approved'),
                        InventorySession::STATUS_CLOSED => __('app.session.status.closed'),
                    ]),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label(__('app.session.fields.branch'))
                    ->relationship('branch', 'name_en')
                    ->visible(fn (): bool => auth()->user()->hasRole(User::ROLE_SUPER_ADMIN)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (InventorySession $record): bool => $record->status === InventorySession::STATUS_DRAFT),
                Tables\Actions\Action::make('start')
                    ->label(__('app.session.actions.start'))
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (InventorySession $record): bool => $record->status === InventorySession::STATUS_DRAFT)
                    ->action(function (InventorySession $record): void {
                        $alreadyInProgress = InventorySession::query()
                            ->where('branch_id', $record->branch_id)
                            ->where('status', InventorySession::STATUS_IN_PROGRESS)
                            ->exists();

                        if ($alreadyInProgress) {
                            Notification::make()
                                ->title(__('app.session.notifications.already_in_progress'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => InventorySession::STATUS_IN_PROGRESS,
                            'started_at' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('submit')
                    ->label(__('app.session.actions.submit'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (InventorySession $record): bool => $record->status === InventorySession::STATUS_IN_PROGRESS)
                    ->action(fn (InventorySession $record) => $record->update([
                        'status' => InventorySession::STATUS_SUBMITTED,
                        'submitted_at' => now(),
                    ])),
                Tables\Actions\Action::make('approve')
                    ->label(__('app.session.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (InventorySession $record): bool => $record->status === InventorySession::STATUS_SUBMITTED)
                    ->action(fn (InventorySession $record) => $record->update([
                        'status' => InventorySession::STATUS_APPROVED,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ])),
                Tables\Actions\Action::make('close')
                    ->label(__('app.session.actions.close'))
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (InventorySession $record): bool => $record->status === InventorySession::STATUS_APPROVED)
                    ->action(fn (InventorySession $record) => $record->update([
                        'status' => InventorySession::STATUS_CLOSED,
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Branch Manager only sees their own branch's sessions; Super Admin sees all.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withCount('counters');
        $user = auth()->user();

        if ($user?->hasRole(User::ROLE_BRANCH_MANAGER)) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            CountLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventorySessions::route('/'),
            'create' => Pages\CreateInventorySession::route('/create'),
            'edit' => Pages\EditInventorySession::route('/{record}/edit'),
            'view' => Pages\ViewInventorySession::route('/{record}'),
        ];
    }
}
