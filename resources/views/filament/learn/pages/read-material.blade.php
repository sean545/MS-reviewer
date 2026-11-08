<x-filament-panels::page>
    <style>
        .reader-frame { width: 100%; height: calc(100vh - 15rem); min-height: 32rem; border: 0; border-radius: 0.75rem; background: #fff; }
        @media (max-width: 640px) {
            .reader-frame { height: 70vh; min-height: 24rem; }
        }
    </style>

    <div wire:ignore>
        <iframe class="reader-frame" src="{{ $this->pdfUrl() }}" title="{{ $material->title }}"></iframe>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
