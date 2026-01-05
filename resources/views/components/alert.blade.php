@props(['type' => 'info', 'dismissible' => true])

@php
    $classes = [
        'info' => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200',
        'success' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200',
        'error' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200',
    ];
@endphp

<div x-data="{ show: true }" x-show="show" x-transition {{ $attributes->merge(['class' => 'rounded-lg border p-4 relative ' . ($classes[$type] ?? $classes['info'])]) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1">
            {{ $slot }}
        </div>
        @if($dismissible)
            <button @click="show = false" type="button" class="ml-3 inline-flex flex-shrink-0 rounded-md p-1.5 hover:bg-black/5 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $type === 'success' ? 'text-green-600 dark:text-green-400 focus:ring-green-500' : ($type === 'error' ? 'text-red-600 dark:text-red-400 focus:ring-red-500' : 'text-blue-600 dark:text-blue-400 focus:ring-blue-500') }}">
                <span class="sr-only">{{ __('common.dismiss') }}</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        @endif
    </div>
</div>

@if($dismissible)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('[x-data*="show"]');
                alerts.forEach(function(alert) {
                    const alpineData = alert.__x;
                    if (alpineData && alpineData.$data.show !== undefined) {
                        alpineData.$data.show = false;
                    }
                });
            }, 5000); // Auto-dismiss after 5 seconds
        });
    </script>
@endif

