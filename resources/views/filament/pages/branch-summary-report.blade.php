<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    @php $rows = $this->getSummary(); @endphp

    <div class="mt-4 flex justify-end">
        <button wire:click="export" type="button" class="fi-btn rounded-lg bg-primary-600 px-4 py-2 text-white">
            Export CSV
        </button>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="p-2 text-start">Branch</th>
                    <th class="p-2 text-end">Sessions</th>
                    <th class="p-2 text-end">Total items counted</th>
                    <th class="p-2 text-end">Total variance</th>
                    <th class="p-2 text-end">Pending review products</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="p-2">{{ $row['branch']->name_en }}</td>
                        <td class="p-2 text-end">{{ $row['sessions_count'] }}</td>
                        <td class="p-2 text-end">{{ $row['total_counted'] }}</td>
                        <td class="p-2 text-end {{ $row['total_variance'] !== 0 ? 'font-bold text-danger-600' : '' }}">{{ $row['total_variance'] }}</td>
                        <td class="p-2 text-end">{{ $row['pending_review_count'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-gray-500">No data for this range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
