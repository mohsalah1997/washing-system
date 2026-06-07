<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'purchases'"
            wire:click="setActiveTab('purchases')"
        >
            المشتريات
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'notes'"
            wire:click="setActiveTab('notes')"
        >
            الملاحظات
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div class="mt-6">
        @if ($activeTab === 'purchases')
            @livewire(\App\Livewire\PurchasesTable::class)
        @else
            @livewire(\App\Livewire\NotesTable::class)
        @endif
    </div>
</x-filament-panels::page>
