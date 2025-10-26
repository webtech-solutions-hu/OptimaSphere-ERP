<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-2">
                <x-filament::icon
                    icon="heroicon-o-document-text"
                    class="h-5 w-5 text-gray-500 dark:text-gray-400"
                />
                <span>Documentation</span>
            </div>
        </x-slot>

        <div class="space-y-3">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Access comprehensive guides and API references
            </p>

            <div class="space-y-2">
                <a
                    href="{{ url('/docs/optimasphere') }}"
                    target="_blank"
                    class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group"
                >
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0">
                            <x-filament::icon
                                icon="heroicon-o-book-open"
                                class="h-5 w-5 text-primary-600 dark:text-primary-400"
                            />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                OptimaSphere Documentation
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Complete user and admin guides
                            </p>
                        </div>
                    </div>
                    <x-filament::icon
                        icon="heroicon-o-arrow-top-right-on-square"
                        class="h-4 w-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300"
                    />
                </a>

                <a
                    href="{{ url('/docs/api') }}"
                    target="_blank"
                    class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group"
                >
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0">
                            <x-filament::icon
                                icon="heroicon-o-code-bracket"
                                class="h-5 w-5 text-primary-600 dark:text-primary-400"
                            />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                API Documentation
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                REST API endpoints and examples
                            </p>
                        </div>
                    </div>
                    <x-filament::icon
                        icon="heroicon-o-arrow-top-right-on-square"
                        class="h-4 w-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300"
                    />
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
