@props(['name', 'options', 'selected' => [], 'placeholder' => 'Tất cả'])

@php
    $optionsJson = collect($options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values();
    $selectedJson = collect($selected)->map(fn ($value) => (string) $value)->values();
@endphp

<div
    class="multi-select"
    x-data="{
        open: false,
        selected: {{ $selectedJson->toJson() }},
        options: {{ $optionsJson->toJson() }},
        toggle(value) {
            const idx = this.selected.indexOf(value);
            if (idx === -1) {
                this.selected.push(value);
            } else {
                this.selected.splice(idx, 1);
            }
        },
        remove(value) {
            this.selected = this.selected.filter((v) => v !== value);
        },
        labelFor(value) {
            const found = this.options.find((option) => option.value === value);
            return found ? found.label : value;
        },
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
>
    <template x-for="value in selected" :key="value">
        <input type="hidden" :name="'{{ $name }}[]'" :value="value">
    </template>

    <div
        class="multi-select__control"
        role="button"
        tabindex="0"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox"
        @click="open = !open"
        @keydown.enter.prevent="open = !open"
        @keydown.space.prevent="open = !open"
    >
        <span class="multi-select__placeholder" x-show="selected.length === 0">{{ $placeholder }}</span>
        <span class="multi-select__chips" x-show="selected.length > 0">
            <template x-for="value in selected" :key="value">
                <span class="multi-select__chip">
                    <span x-text="labelFor(value)"></span>
                    <button
                        type="button"
                        class="multi-select__chip-remove"
                        @click.stop="remove(value)"
                        :aria-label="'Bỏ chọn ' + labelFor(value)"
                    >&times;</button>
                </span>
            </template>
        </span>
    </div>

    <div class="multi-select__panel" x-show="open" x-cloak role="listbox" :aria-multiselectable="true">
        <template x-for="option in options" :key="option.value">
            <div
                class="multi-select__option"
                :class="{ 'is-selected': selected.includes(option.value) }"
                role="option"
                :aria-selected="selected.includes(option.value).toString()"
                @click="toggle(option.value)"
            >
                <span x-text="option.label"></span>
                <span class="multi-select__check" x-show="selected.includes(option.value)" aria-hidden="true">&check;</span>
            </div>
        </template>
    </div>
</div>
