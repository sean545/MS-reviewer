<x-filament-panels::page>
    <iframe
        src="{{ route('materials.file', $material) }}#view=FitH"
        title="{{ $material->title }}"
        style="width: 100%; height: calc(100vh - 15rem); min-height: 32rem; border: 0; border-radius: 0.75rem; background: #fff;"
    ></iframe>
</x-filament-panels::page>
