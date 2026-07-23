<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    @php $lines = $this->getLines(); @endphp

    @if (($data['session_id'] ?? null) && $lines->isNotEmpty())
        <div class="mt-4 flex justify-end">
            <button wire:click="export" type="button" class="fi-btn rounded-lg bg-primary-600 px-4 py-2 text-white">
                Export CSV
            </button>
        </div>

        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="p-2 text-start">SKU</th>
                        <th class="p-2 text-start">Product</th>
                        <th class="p-2 text-end">Expected</th>
                        <th class="p-2 text-end">Counted</th>
                        <th class="p-2 text-end">Variance</th>
                        <th class="p-2 text-start">Last scanned by</th>
                        <th class="p-2 text-start">Last scanned at</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $line)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="p-2">{{ $line->product->sku }}</td>
                            <td class="p-2">{{ $line->product->name_en }} / {{ $line->product->name_ar }}</td>
                            <td class="p-2 text-end">{{ $line->expected_quantity_snapshot }}</td>
                            <td class="p-2 text-end">{{ $line->counted_quantity }}</td>
                            <td class="p-2 text-end {{ $line->variance !== 0 ? 'font-bold text-danger-600' : '' }}">{{ $line->variance }}</td>
                            <td class="p-2">{{ $line->lastScannedBy?->name ?? '—' }}</td>
                            <td class="p-2">{{ $line->last_scanned_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif ($data['session_id'] ?? null)
        <p class="mt-4 text-gray-500">No count lines for this session yet.</p>
    @endif
</x-filament-panels::page>
