<?php

namespace App\Filament\Pages;

use App\Exports\SessionDetailExport;
use App\Models\InventoryCountLine;
use App\Models\InventorySession;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SessionDetailReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.pages.session-detail-report';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_BRANCH_MANAGER]) ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.reports.session_detail.nav_label');
    }

    public function getTitle(): string
    {
        return __('app.reports.session_detail.title');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Select::make('session_id')
                    ->label(__('app.reports.session_detail.session_field'))
                    ->options(
                        InventorySession::query()
                            ->when(
                                ! $user->hasRole(User::ROLE_SUPER_ADMIN),
                                fn ($query) => $query->where('branch_id', $user->branch_id),
                            )
                            ->orderByDesc('created_at')
                            ->get()
                            ->mapWithKeys(fn (InventorySession $s) => [$s->id => "{$s->reference} ({$s->branch->name_en})"]),
                    )
                    ->searchable()
                    ->live()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function getLines(): Collection
    {
        $sessionId = $this->data['session_id'] ?? null;

        if (! $sessionId) {
            return collect();
        }

        return InventoryCountLine::query()
            ->with(['product', 'lastScannedBy'])
            ->where('session_id', $sessionId)
            ->get();
    }

    public function export(): BinaryFileResponse
    {
        $sessionId = $this->data['session_id'];
        $session = InventorySession::findOrFail($sessionId);

        return Excel::download(new SessionDetailExport((int) $sessionId), "session-{$session->reference}.csv", ExcelFormat::CSV);
    }
}
