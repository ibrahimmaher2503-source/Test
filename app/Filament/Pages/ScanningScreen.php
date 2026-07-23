<?php

namespace App\Filament\Pages;

use App\Models\InventoryCountLine;
use App\Models\InventorySession;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\ScanEvent;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ScanningScreen extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static string $view = 'filament.pages.scanning-screen';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'scanning-screen/{session}';

    public InventorySession $session;

    /** @var array<int, array{id:int,sku:string,name_en:string,name_ar:string,counted_quantity:int,expected_quantity_snapshot:int,is_manual:bool}> */
    public array $runningList = [];

    public ?string $unknownBarcode = null;

    public bool $showCreateProductForm = false;

    public string $newProductNameEn = '';

    public string $newProductNameAr = '';

    public ?int $newProductCategoryId = null;

    public string $newProductUnit = 'pcs';

    public ?int $manualAdjustLineId = null;

    public ?int $manualAdjustQuantity = null;

    public string $manualAdjustNote = '';

    public string $keyboardWedgeInput = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            User::ROLE_SUPER_ADMIN,
            User::ROLE_BRANCH_MANAGER,
            User::ROLE_COUNTER,
        ]) ?? false;
    }

    public function mount(InventorySession $session): void
    {
        $user = Auth::user();

        $isAssignedCounter = $user->hasRole(User::ROLE_COUNTER)
            && $session->counters()->where('users.id', $user->id)->exists();
        $isOverseeing = $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_BRANCH_MANAGER])
            && ($user->hasRole(User::ROLE_SUPER_ADMIN) || $session->branch_id === $user->branch_id);

        abort_unless($isAssignedCounter || $isOverseeing, 403);

        $this->session = $session;
        $this->refreshRunningList();
    }

    public function getTitle(): string
    {
        return __('app.scanning.title')." — {$this->session->reference}";
    }

    protected function refreshRunningList(): void
    {
        $this->runningList = $this->session->countLines()
            ->with('product')
            ->orderByDesc('last_scanned_at')
            ->get()
            ->map(fn (InventoryCountLine $line) => [
                'id' => $line->id,
                'sku' => $line->product->sku,
                'name_en' => $line->product->name_en,
                'name_ar' => $line->product->name_ar,
                'counted_quantity' => $line->counted_quantity,
                'expected_quantity_snapshot' => $line->expected_quantity_snapshot,
            ])
            ->all();
    }

    public function scan(?string $rawBarcode, ?string $cameraError = null): void
    {
        if ($cameraError) {
            Notification::make()->title('Camera error')->body($cameraError)->danger()->send();

            return;
        }

        $rawBarcode = trim((string) $rawBarcode);

        if ($rawBarcode === '') {
            return;
        }

        if (! $this->canScan()) {
            Notification::make()->title(__('app.scanning.not_open_for_scanning'))->danger()->send();

            return;
        }

        // Any existing product with this barcode counts, regardless of status --
        // not just 'active'. Filtering to active-only here would mean a
        // pending_review product (e.g. from an earlier unknown-barcode scan)
        // looks "unknown" again on a second scan, and the quick-create form
        // would then try to insert a second product with the same barcode and
        // crash on the unique constraint instead of just counting it again.
        $product = Product::query()
            ->where('barcode', $rawBarcode)
            ->first();

        if ($product) {
            $this->recordScan($product, $rawBarcode);
            $this->keyboardWedgeInput = '';

            return;
        }

        // SPEC §5.5: unknown barcode -> prompt to quick-create a pending_review product
        $this->unknownBarcode = $rawBarcode;
        $this->showCreateProductForm = true;
    }

    protected function canScan(): bool
    {
        return $this->session->status === InventorySession::STATUS_IN_PROGRESS;
    }

    protected function recordScan(Product $product, string $rawBarcode): void
    {
        $line = InventoryCountLine::firstOrCreate(
            ['session_id' => $this->session->id, 'product_id' => $product->id],
            ['expected_quantity_snapshot' => ProductBranchStock::query()
                ->where('product_id', $product->id)
                ->where('branch_id', $this->session->branch_id)
                ->value('expected_quantity') ?? 0,
            ],
        );

        $line->increment('counted_quantity');
        $line->update([
            'last_scanned_by' => Auth::id(),
            'last_scanned_at' => now(),
        ]);

        ScanEvent::create([
            'session_id' => $this->session->id,
            'product_id' => $product->id,
            'raw_barcode' => $rawBarcode,
            'user_id' => Auth::id(),
            'quantity_delta' => 1,
            'scanned_at' => now(),
        ]);

        $this->refreshRunningList();

        Notification::make()->title(__('app.scanning.counted_notification', ['name' => $product->name_en]))->success()->send();
    }

    public function createPendingProduct(): void
    {
        if (! $this->canScan()) {
            Notification::make()->title(__('app.scanning.not_open_for_scanning'))->danger()->send();

            return;
        }

        $this->validate([
            'newProductNameEn' => ['required', 'string', 'max:255'],
            'newProductNameAr' => ['required', 'string', 'max:255'],
            'newProductUnit' => ['required', 'string', 'max:255'],
        ]);

        // Defense in depth against a race: two counters scanning the same brand-new
        // barcode at the same moment could both pass the "unknown" check in scan()
        // before either has inserted a row. Re-check right before creating, and if
        // another request already won, just count the product it created instead
        // of hitting the DB's unique constraint and surfacing a raw error.
        $product = Product::query()->where('barcode', $this->unknownBarcode)->first();

        if (! $product) {
            $product = Product::create([
                'sku' => 'SCN-'.strtoupper(uniqid()),
                'barcode' => $this->unknownBarcode,
                'barcode_source' => 'existing',
                'name_en' => $this->newProductNameEn,
                'name_ar' => $this->newProductNameAr,
                'category_id' => $this->newProductCategoryId,
                'unit' => $this->newProductUnit,
                'status' => 'pending_review',
                'is_active' => true,
            ]);
        }

        $this->recordScan($product, $this->unknownBarcode);

        $this->reset(['unknownBarcode', 'showCreateProductForm', 'newProductNameEn', 'newProductNameAr', 'newProductCategoryId', 'newProductUnit', 'keyboardWedgeInput']);
        $this->newProductUnit = 'pcs';
    }

    public function cancelUnknownBarcode(): void
    {
        $this->reset(['unknownBarcode', 'showCreateProductForm', 'keyboardWedgeInput']);
    }

    public function startManualAdjust(int $lineId): void
    {
        $line = InventoryCountLine::findOrFail($lineId);
        abort_unless($line->session_id === $this->session->id, 403);

        $this->manualAdjustLineId = $lineId;
        $this->manualAdjustQuantity = $line->counted_quantity;
        $this->manualAdjustNote = '';
    }

    public function saveManualAdjust(): void
    {
        if (! $this->canScan()) {
            Notification::make()->title(__('app.scanning.not_open_for_editing'))->danger()->send();

            return;
        }

        $this->validate([
            'manualAdjustQuantity' => ['required', 'integer', 'min:0'],
            'manualAdjustNote' => ['required', 'string', 'max:1000'],
        ]);

        $line = InventoryCountLine::findOrFail($this->manualAdjustLineId);
        abort_unless($line->session_id === $this->session->id, 403);

        $line->update([
            'counted_quantity' => $this->manualAdjustQuantity,
            'notes' => $this->manualAdjustNote,
            'last_scanned_by' => Auth::id(),
            'last_scanned_at' => now(),
        ]);

        $this->reset(['manualAdjustLineId', 'manualAdjustQuantity', 'manualAdjustNote']);
        $this->refreshRunningList();

        Notification::make()->title(__('app.scanning.manual_update_notification'))->success()->send();
    }

    public function cancelManualAdjust(): void
    {
        $this->reset(['manualAdjustLineId', 'manualAdjustQuantity', 'manualAdjustNote']);
    }

    public function submitKeyboardWedge(): void
    {
        $this->scan($this->keyboardWedgeInput);
    }
}
