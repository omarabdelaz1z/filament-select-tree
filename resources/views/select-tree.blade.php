<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
            wire:key="{{ rand() }}"
            wire:ignore
            x-data
            x-load-css="[
        @js(\Filament\Support\Facades\FilamentAsset::getStyleHref('tree', package: 'codewithdennis/filament-select-tree')),
        @js(\Filament\Support\Facades\FilamentAsset::getStyleHref('custom', package: 'codewithdennis/filament-select-tree'))
        ]"
    >
        <div
                x-ignore
                ax-load="visible"
                ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('tree', package: 'codewithdennis/filament-select-tree') }}"
                x-data="tree({
                name: '{{ $getName() }}',
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
                options: {{ json_encode($getTree()) }},
                searchable: '{{ $isSearchable() }}',
                showCount: '{{ $getWithCount() }}',
                placeholder: '{{ $getPlaceholder() }}',
                disabledBranchNode: '{{ !$getEnableBranchNode() }}',
                disabled: '{{ $isDisabled() }}',
                isSingleSelect: '{{ !$getMultiple() }}',
                isIndependentNodes: '{{ $getIndependent() }}',
                showTags: '{{ $getMultiple() }}',
                alwaysOpen: '{{ $getAlwaysOpen() }}',
                clearable: '{{ $getClearable() }}',
                emptyText: '{{ $getEmptyLabel() }}',
                expandSelected: '{{ $getExpandSelected() }}',
                grouped: '{{ $getGrouped() }}',
                openLevel: '{{ $getDefaultOpenLevel() }}',
                direction: '{{ $getDirection() }}',
                rtl: '{{ __('filament-panels::layout.direction') === 'rtl' }}'
            })"
        >
            <div x-ref="tree"></div>
        </div>
    </div>
</x-dynamic-component>
