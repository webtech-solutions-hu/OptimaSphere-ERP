@php
    $user = filament()->auth()->user();
    $avatarUrl = $user->avatar
        ? asset('storage/' . $user->avatar)
        : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&color=7F9CF5&background=EBF4FF';
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-x-4">
            <div class="flex items-center gap-x-3">
                <x-filament::avatar
                    :src="$avatarUrl"
                    :alt="$user->name"
                    size="lg"
                />

                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $user->name }}
                    </h2>

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $user->email }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-x-2">
                <x-filament::button
                    :href="$this->getProfileUrl()"
                    tag="a"
                    color="gray"
                    outlined
                    size="sm"
                    icon="heroicon-o-user-circle"
                >
                    Edit Profile
                </x-filament::button>

                <form action="{{ filament()->getLogoutUrl() }}" method="post">
                    @csrf
                    <x-filament::button
                        type="submit"
                        color="gray"
                        outlined
                        size="sm"
                        icon="heroicon-o-arrow-right-on-rectangle"
                    >
                        Logout
                    </x-filament::button>
                </form>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
