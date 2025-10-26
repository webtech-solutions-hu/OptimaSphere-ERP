<x-filament-panels::page>
    @if ($this->newToken)
        <x-filament::section>
            <x-slot name="heading">
                New API Token Created
            </x-slot>

            <x-slot name="description">
                Make sure to copy your new API token now. You won't be able to see it again!
            </x-slot>

            <div class="space-y-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <code class="text-sm break-all select-all">{{ $this->newToken }}</code>
                </div>

                <div class="flex gap-2">
                    <x-filament::button
                        color="gray"
                        icon="heroicon-o-clipboard"
                        onclick="navigator.clipboard.writeText('{{ $this->newToken }}'); window.$wireui.notify({title: 'Copied!', description: 'Token copied to clipboard', icon: 'success'})"
                    >
                        Copy to Clipboard
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        icon="heroicon-o-x-mark"
                        wire:click="$set('newToken', null)"
                    >
                        Close
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">
            API Documentation
        </x-slot>

        <div class="prose dark:prose-invert max-w-none">
            <h3>Using Your API Tokens</h3>
            <p>Include your API token in the Authorization header of your HTTP requests:</p>

            <pre class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg overflow-auto"><code class="text-black dark:text-gray-100">Authorization: Bearer YOUR_API_TOKEN</code></pre>

            <h3>Example Request</h3>
            <pre class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg overflow-auto"><code class="text-black dark:text-gray-100">curl {{ url('/api/v1/products') }} \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</code></pre>

            <h3>Available Endpoints</h3>
            <ul>
                <li><strong>Products:</strong> <code>GET|POST|PUT|DELETE /api/v1/products</code></li>
                <li><strong>Customers:</strong> <code>GET|POST|PUT|DELETE /api/v1/customers</code></li>
                <li><strong>Suppliers:</strong> <code>GET|POST|PUT|DELETE /api/v1/suppliers</code></li>
                <li><strong>Categories:</strong> <code>GET|POST|PUT|DELETE /api/v1/categories</code></li>
                <li><strong>Units:</strong> <code>GET|POST|PUT|DELETE /api/v1/units</code></li>
            </ul>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                For detailed API documentation, visit <a href="{{ url('/api/documentation') }}" class="text-primary-600 hover:text-primary-500">API Documentation</a>
            </p>
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
