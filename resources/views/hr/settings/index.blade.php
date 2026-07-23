@extends('layouts.hr')

@section('title', 'Cấu hình Phase 1')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Cấu hình Phase 1</h1>
        <p class="text-secondary mb-0">Chỉ các thiết lập nghiệp vụ trong allowlist được phép cập nhật.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-2">Vui lòng kiểm tra lại cấu hình:</p>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('hr.settings.update') }}" class="card shadow-sm" novalidate>
        @csrf
        @method('PUT')

        <div class="card-body p-3 p-md-4">
            <div class="row g-4">
                @foreach ($settings as $setting)
                    <div class="col-12 col-lg-6">
                        <label for="setting-{{ $setting['key'] }}" class="form-label fw-semibold">
                            {{ $setting['label'] }}
                        </label>
                        <input type="hidden" name="settings[{{ $setting['key'] }}][type]" value="{{ $setting['type'] }}">

                        @if ($setting['type'] === 'boolean')
                            <select
                                id="setting-{{ $setting['key'] }}"
                                name="settings[{{ $setting['key'] }}][value]"
                                class="form-select @error('settings.'.$setting['key'].'.value') is-invalid @enderror"
                                style="min-height:48px"
                            >
                                <option value="0" @selected((string) old('settings.'.$setting['key'].'.value', $setting['value'] === true || $setting['value'] === 'true' ? '1' : '0') === '0')>Tắt</option>
                                <option value="1" @selected((string) old('settings.'.$setting['key'].'.value', $setting['value'] === true || $setting['value'] === 'true' ? '1' : '0') === '1')>Bật</option>
                            </select>
                        @else
                            <input
                                type="number"
                                min="1"
                                max="365"
                                id="setting-{{ $setting['key'] }}"
                                name="settings[{{ $setting['key'] }}][value]"
                                value="{{ old('settings.'.$setting['key'].'.value', $setting['value']) }}"
                                class="form-control @error('settings.'.$setting['key'].'.value') is-invalid @enderror"
                                style="min-height:48px"
                                @required(! $setting['nullable'])
                            >
                        @endif

                        @error('settings.'.$setting['key'].'.value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">{{ $setting['help'] }}</div>
                        <code class="small">{{ $setting['key'] }} ({{ $setting['type'] }})</code>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card-footer bg-white d-flex justify-content-end p-3">
            <button type="submit" class="btn btn-primary" style="min-height:48px">Lưu cấu hình</button>
        </div>
    </form>
@endsection
