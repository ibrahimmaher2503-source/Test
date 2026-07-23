@php
    $current = app()->getLocale();
    $target = $current === 'ar' ? 'en' : 'ar';
    $label = $current === 'ar' ? __('app.locale.switch_to_english') : __('app.locale.switch_to_arabic');
@endphp

<a
    href="{{ route('locale.switch', ['locale' => $target]) }}"
    class="fi-btn fi-btn-size-md flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5"
>
    {{ $label }}
</a>
