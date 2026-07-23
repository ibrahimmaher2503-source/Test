<x-filament-panels::page>
    @if ($this->session->status !== \App\Models\InventorySession::STATUS_IN_PROGRESS)
        <div class="rounded-lg bg-warning-50 p-4 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
            This session is <strong>{{ $this->session->status }}</strong> and is no longer open for scanning.
            You can still review what was counted below.
        </div>
    @endif

    @if ($this->session->status === \App\Models\InventorySession::STATUS_IN_PROGRESS)
        <div
            x-data="{
                scanner: null,
                init() {
                    this.scanner = window.startBarcodeScanner('reader', (text, error) => {
                        if (error) {
                            $wire.scan(null, error.toString());
                            return;
                        }
                        $wire.scan(text);
                    });
                },
            }"
            x-init="init()"
            class="mb-4"
        >
            <div id="reader" class="mx-auto max-w-md overflow-hidden rounded-lg"></div>
        </div>

        {{-- Keyboard-wedge fallback for USB/Bluetooth scanner guns (SPEC §5.7) --}}
        <form wire:submit.prevent="submitKeyboardWedge" class="mb-6 flex gap-2">
            <input
                type="text"
                wire:model="keyboardWedgeInput"
                placeholder="Or type/scan a barcode and press Enter"
                autofocus
                class="fi-input block w-full rounded-lg border-gray-300 text-lg shadow-sm dark:border-gray-600 dark:bg-gray-700"
            />
            <button type="submit" class="fi-btn rounded-lg bg-primary-600 px-4 py-2 text-white">Add</button>
        </form>
    @endif

    {{-- Unknown barcode -> quick-create pending_review product --}}
    @if ($showCreateProductForm)
        <div class="mb-6 rounded-lg border border-warning-300 bg-warning-50 p-4 dark:border-warning-500/30 dark:bg-warning-500/10">
            <p class="mb-3 font-semibold">Unknown barcode: {{ $unknownBarcode }} — add new product?</p>
            <form wire:submit.prevent="createPendingProduct" class="space-y-3">
                <input type="text" wire:model="newProductNameEn" placeholder="Name (English)" class="fi-input block w-full rounded-lg" />
                @error('newProductNameEn') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror

                <input type="text" wire:model="newProductNameAr" placeholder="الاسم (عربي)" dir="rtl" class="fi-input block w-full rounded-lg" />
                @error('newProductNameAr') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror

                <select wire:model="newProductCategoryId" class="fi-input block w-full rounded-lg">
                    <option value="">— No category —</option>
                    @foreach (\App\Models\Category::all() as $category)
                        <option value="{{ $category->id }}">{{ $category->name_en }}</option>
                    @endforeach
                </select>

                <select wire:model="newProductUnit" class="fi-input block w-full rounded-lg">
                    <option value="pcs">pcs</option>
                    <option value="box">box</option>
                    <option value="kg">kg</option>
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="fi-btn rounded-lg bg-primary-600 px-4 py-2 text-white">Save &amp; count</button>
                    <button type="button" wire:click="cancelUnknownBarcode" class="fi-btn rounded-lg bg-gray-200 px-4 py-2 dark:bg-gray-700">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Running list --}}
    <div class="space-y-2">
        <h3 class="text-sm font-semibold text-gray-500">Counted so far ({{ count($runningList) }})</h3>

        @forelse ($runningList as $line)
            <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700" wire:key="line-{{ $line['id'] }}">
                <div>
                    <div class="font-medium">{{ $line['name_en'] }}</div>
                    <div class="text-sm text-gray-500">{{ $line['sku'] }} — {{ $line['name_ar'] }}</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-lg font-bold">{{ $line['counted_quantity'] }}</span>
                    @if ($this->session->status === \App\Models\InventorySession::STATUS_IN_PROGRESS)
                        <button
                            type="button"
                            wire:click="startManualAdjust({{ $line['id'] }})"
                            class="text-sm text-primary-600 underline"
                        >
                            Adjust
                        </button>
                    @endif
                </div>
            </div>

            @if ($manualAdjustLineId === $line['id'])
                <div class="rounded-lg border border-primary-300 bg-primary-50 p-3 dark:border-primary-500/30 dark:bg-primary-500/10">
                    <form wire:submit.prevent="saveManualAdjust" class="space-y-2">
                        <input type="number" min="0" wire:model="manualAdjustQuantity" class="fi-input block w-full rounded-lg" />
                        @error('manualAdjustQuantity') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror

                        <textarea wire:model="manualAdjustNote" placeholder="Reason for manual adjustment (required)" class="fi-input block w-full rounded-lg"></textarea>
                        @error('manualAdjustNote') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror

                        <div class="flex gap-2">
                            <button type="submit" class="fi-btn rounded-lg bg-primary-600 px-4 py-2 text-white">Save</button>
                            <button type="button" wire:click="cancelManualAdjust" class="fi-btn rounded-lg bg-gray-200 px-4 py-2 dark:bg-gray-700">Cancel</button>
                        </div>
                    </form>
                </div>
            @endif
        @empty
            <p class="text-gray-500">Nothing scanned yet — point the camera at a barcode to start.</p>
        @endforelse
    </div>

    @vite('resources/js/scanner.js')
</x-filament-panels::page>
