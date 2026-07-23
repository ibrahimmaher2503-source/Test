<?php

namespace App\Filament\Pages;

use App\Exports\BranchSummaryExport;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchSummaryReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BranchSummaryReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $title = 'Branch Summary Report';

    protected static string $view = 'filament.pages.branch-summary-report';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_BRANCH_MANAGER]) ?? false;
    }

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'from' => now()->subDays(30)->toDateString(),
            'to' => now()->toDateString(),
            'branch_id' => $user->hasRole(User::ROLE_BRANCH_MANAGER) ? $user->branch_id : null,
        ]);
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole(User::ROLE_SUPER_ADMIN);

        return $form
            ->schema([
                DatePicker::make('from')->required(),
                DatePicker::make('to')->required(),
                Select::make('branch_id')
                    ->label('Branch')
                    ->options(Branch::query()->pluck('name_en', 'id'))
                    ->placeholder('All branches')
                    ->visible($isSuperAdmin)
                    ->disabled(! $isSuperAdmin),
            ])
            ->statePath('data');
    }

    public function getSummary(): Collection
    {
        $from = Carbon::parse($this->data['from'] ?? now()->subDays(30));
        $to = Carbon::parse($this->data['to'] ?? now());

        return app(BranchSummaryReportService::class)->build($this->scopedBranches(), $from, $to);
    }

    protected function scopedBranches(): Collection
    {
        $user = auth()->user();

        return $user->hasRole(User::ROLE_SUPER_ADMIN)
            ? (filled($this->data['branch_id'] ?? null)
                ? Branch::where('id', $this->data['branch_id'])->get()
                : Branch::all())
            : Branch::where('id', $user->branch_id)->get();
    }

    public function export(): BinaryFileResponse
    {
        $from = Carbon::parse($this->data['from']);
        $to = Carbon::parse($this->data['to']);

        return Excel::download(
            new BranchSummaryExport($this->scopedBranches(), $from, $to),
            "branch-summary-{$from->toDateString()}-to-{$to->toDateString()}.csv",
            ExcelFormat::CSV,
        );
    }
}
