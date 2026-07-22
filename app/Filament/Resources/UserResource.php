<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        $currentUser = auth()->user();
        $isBranchManager = $currentUser?->hasRole(User::ROLE_BRANCH_MANAGER) ?? false;

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options($isBranchManager
                        ? [User::ROLE_COUNTER => 'Counter']
                        : [
                            User::ROLE_SUPER_ADMIN => 'Super Admin',
                            User::ROLE_BRANCH_MANAGER => 'Branch Manager',
                            User::ROLE_COUNTER => 'Counter',
                        ])
                    ->default($isBranchManager ? User::ROLE_COUNTER : null)
                    ->disabled($isBranchManager)
                    ->dehydrated()
                    ->required()
                    ->live()
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?User $record): void {
                        $component->state($record?->getRoleNames()->first());
                    }),
                Forms\Components\Select::make('branch_id')
                    ->label('Branch')
                    ->options(Branch::query()->pluck('name_en', 'id'))
                    ->default($isBranchManager ? $currentUser->branch_id : null)
                    ->disabled($isBranchManager)
                    ->dehydrated()
                    ->required(fn (Get $get): bool => $get('role') !== User::ROLE_SUPER_ADMIN)
                    ->hidden(fn (Get $get): bool => $get('role') === User::ROLE_SUPER_ADMIN),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge(),
                Tables\Columns\TextColumn::make('branch.name_en')
                    ->label('Branch')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Super Admin sees everyone. Branch Manager only sees counters in their own
     * branch (SPEC §2) — never other managers, admins, or another branch's staff.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->hasRole(User::ROLE_BRANCH_MANAGER)) {
            return $query->role(User::ROLE_COUNTER)->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
