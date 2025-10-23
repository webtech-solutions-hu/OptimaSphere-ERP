@php
    $version = config('app-version.version', '1.0.0');
    $stage = config('app-version.stage', 'stable');
    $showStageBadge = in_array($stage, ['alpha', 'beta']);
@endphp

<div class="fi-footer flex items-center justify-center gap-x-4 border-t border-gray-200 px-4 py-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
    <div class="flex items-center gap-x-1">
        <span>{{ now()->year }} &copy;</span>
        <a
            href="https://webtech-solutions.hu"
            target="_blank"
            rel="noopener noreferrer"
            class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
        >
            Webtech-Solutions
        </a>
    </div>
    <span class="text-gray-300 dark:text-gray-600">|</span>
    <div class="flex items-center gap-x-2">
        <span class="font-medium">OptimaSphere ERP</span>
        <span class="text-gray-300 dark:text-gray-600">|</span>
        <span class="inline-flex items-center gap-x-1">
            <span class="text-xs">v{{ $version }}</span>
            @if($showStageBadge)
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                    {{ $stage === 'alpha' ? 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/30' : '' }}
                    {{ $stage === 'beta' ? 'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-500 dark:ring-yellow-400/30' : '' }}">
                    {{ strtoupper($stage) }}
                </span>
            @endif
        </span>
    </div>
</div>
