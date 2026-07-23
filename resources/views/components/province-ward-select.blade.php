@props([
    'provinces',
    'wards',
    'wardField' => 'ward_id',
    'selectedWardId' => null,
    'required' => false,
    'wardLabel' => 'Phường/xã',
    'provinceLabel' => 'Tỉnh/thành phố',
])

@php
    $provincesJson = collect($provinces)->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values();
    $wardsJson = collect($wards)->map(fn ($w) => ['id' => $w->id, 'name' => $w->name, 'province_id' => $w->province_id])->values();

    $oldWardId = old($wardField, $selectedWardId);
    $oldWardId = $oldWardId !== null && $oldWardId !== '' ? (int) $oldWardId : null;
    $initialProvinceId = $oldWardId ? collect($wards)->firstWhere('id', $oldWardId)?->province_id : null;
@endphp

<div
    x-data="{
        provinceId: {{ $initialProvinceId ? (int) $initialProvinceId : 'null' }},
        wardId: {{ $oldWardId ?? 'null' }},
        provinces: {{ $provincesJson->toJson(JSON_UNESCAPED_UNICODE) }},
        wards: {{ $wardsJson->toJson(JSON_UNESCAPED_UNICODE) }},
        get filteredWards() {
            return this.wards.filter((w) => this.provinceId && w.province_id === this.provinceId);
        },
    }"
>
    <div class="mb-3">
        <label for="{{ $wardField }}_province" class="form-label">{{ $provinceLabel }}</label>
        <select
            class="form-select"
            id="{{ $wardField }}_province"
            x-model.number="provinceId"
            @change="wardId = null"
            style="min-height: 44px"
        >
            <option value="">-- Chọn {{ mb_strtolower($provinceLabel) }} --</option>
            <template x-for="province in provinces" :key="province.id">
                <option :value="province.id" x-text="province.name"></option>
            </template>
        </select>
    </div>

    <div class="mb-3">
        <label for="{{ $wardField }}" class="form-label">{{ $wardLabel }}</label>
        <select
            class="form-select @error($wardField) is-invalid @enderror"
            id="{{ $wardField }}"
            name="{{ $wardField }}"
            x-model.number="wardId"
            :disabled="!provinceId"
            @if ($required) required @endif
            style="min-height: 44px"
        >
            <option value="">-- Chọn {{ mb_strtolower($wardLabel) }} --</option>
            <template x-for="ward in filteredWards" :key="ward.id">
                <option :value="ward.id" x-text="ward.name"></option>
            </template>
        </select>
        @error($wardField)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>
