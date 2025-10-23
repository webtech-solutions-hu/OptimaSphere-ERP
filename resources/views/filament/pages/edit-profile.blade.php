<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="updateProfile">
            {{ $this->profileForm }}

            <div class="mt-6">
                <x-filament::button type="submit">
                    Save Profile
                </x-filament::button>
            </div>
        </form>

        <x-filament::section class="mt-6">
            <form wire:submit="updatePassword">
                {{ $this->passwordForm }}

                <div class="mt-6">
                    <x-filament::button type="submit" color="warning">
                        Update Password
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
