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

    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isBranchManager = $user->hasRole(User::ROLE_BRANCH_MANAGER);

        return $form
            ->schema([
                Forms\Components\Select::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name_en')
                    ->default($isBranchManager ? $user->branch_id : null)
                    ->disabled(fn (string $operation): bool => $isBranchManager || $operation === 'edit')
                    ->dehydrated()
                    ->required()
                    ->live(),
                Forms\Components\Select::make('counters')
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
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('reference'),
                TextEntry::make('branch.name_en')->label('Branch'),
                TextEntry::make('status')->badge(),
                TextEntry::make('createdBy.name')->label('Created by'),
                TextEntry::make('counters.name')->label('Assigned counters')->listWithLineBreaks(),
                TextEntry::make('started_at')->dateTime()->placeholder('—'),
                TextEntry::make('submitted_at')->dateTime()->placeholder('—'),
                TextEntry::make('approvedBy.name')->label('Approved by')->placeholder('—'),
                TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch.name_en')
                    ->label('Branch')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        InventorySession::STATUS_DRAFT => 'gray',
                        InventorySession::STATUS_IN_PROGRESS => 'info',
                        InventorySession::STATUS_SUBMITTED => 'warning',
                        InventorySession::STATUS_APPROVED, InventorySession::STATUS_CLOSED => 'success',
                    }),
                Tables\Columns\TextColumn::make('counters_count')
                    ->counts('counters')
                    ->label('Counters'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created by'),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        InventorySession::STATUS_DRAFT => 'Draft',
                        InventorySession::STATUS_IN_PROGRESS => 'In progress',
                        InventorySession::STATUS_SUBMITTED => 'Submitted',
                        InventorySession::STATUS_APPROVED => 'Approved',
                        InventorySession::STATUS_CLOSED => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name_en')
                    ->visible(fn (): bool => auth()->user()->hasRole(User::ROLE_SUPER_ADMIN)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (InventorySession $record): bool => $record->status === InventorySession::STATUS_DRAFT),
                Tables\Actions\Action::make('start')
                    ->label('Start')
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
                                ->title('This branch already has a session in progress.')
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
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (InventorySession $record): bool => $record->status === InventorySession::STATUS_IN_PROGRESS)
                    ->action(fn (InventorySession $record) => $record->update([
                        'status' => InventorySession::STATUS_SUBMITTED,
                        'submitted_at' => now(),
                    ])),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
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
                    ->label('Close')
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
