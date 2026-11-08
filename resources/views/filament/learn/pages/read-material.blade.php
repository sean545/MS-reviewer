<x-filament-panels::page>
    <div wire:ignore>
        <iframe
            src="{{ $this->pdfUrl() }}"
            title="{{ $material->title }}"
            style="width: 100%; height: calc(100vh - 15rem); min-height: 32rem; border: 0; border-radius: 0.75rem; background: #fff;"
        ></iframe>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
