<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Đơn vị hành chính — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="container py-4 py-md-5">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                <div>
                    <h1 class="h4 mb-1">Đơn vị hành chính</h1>
                    <p class="text-body-secondary mb-0">
                        Quản lý cây tỉnh, thành phố, xã, phường và dữ liệu hiệu lực.
                    </p>
                </div>

                @if ($editingUnit)
                    <a
                        href="{{ route('hr.administrative-units.index', array_filter(['q' => request('q')])) }}"
                        class="btn btn-outline-secondary"
                        style="min-height: 48px"
                    >
                        Hủy chỉnh sửa
                    </a>
                @endif
            </div>

            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <p class="fw-semibold mb-2">Vui lòng kiểm tra lại dữ liệu:</p>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">
                        {{ $editingUnit ? 'Cập nhật đơn vị hành chính' : 'Thêm hoặc đồng bộ đơn vị hành chính' }}
                    </h2>

                    @if ($editingUnit)
                        <form
                            method="POST"
                            action="{{ route('hr.administrative-units.update', $editingUnit) }}"
                            novalidate
                        >
                            @csrf
                            @method('PUT')

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="edit-name" class="form-label">Tên</label>
                                    <input
                                        type="text"
                                        class="form-control @error('name') is-invalid @enderror"
                                        id="edit-name"
                                        name="name"
                                        value="{{ old('name', $editingUnit->name) }}"
                                        maxlength="150"
                                        required
                                    >
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="edit-slug" class="form-label">Slug</label>
                                    <input
                                        type="text"
                                        class="form-control @error('slug') is-invalid @enderror"
                                        id="edit-slug"
                                        name="slug"
                                        value="{{ old('slug', $editingUnit->slug) }}"
                                        maxlength="170"
                                        pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                        required
                                    >
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="edit-official-code" class="form-label">Mã chính thức</label>
                                    <input
                                        type="text"
                                        class="form-control @error('official_code') is-invalid @enderror"
                                        id="edit-official-code"
                                        name="official_code"
                                        value="{{ old('official_code', $editingUnit->official_code) }}"
                                        maxlength="20"
                                    >
                                    @error('official_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="edit-type" class="form-label">Loại</label>
                                    <select
                                        class="form-select @error('type') is-invalid @enderror"
                                        id="edit-type"
                                        name="type"
                                        required
                                    >
                                        @foreach ($typeLabels as $type => $label)
                                            <option value="{{ $type }}" @selected(old('type', $editingUnit->type) === $type)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-8">
                                    <label for="edit-parent-id" class="form-label">Đơn vị cha</label>
                                    <select
                                        class="form-select @error('parent_id') is-invalid @enderror"
                                        id="edit-parent-id"
                                        name="parent_id"
                                    >
                                        <option value="">— Cấp gốc —</option>
                                        @foreach ($editParentOptions as $parentOption)
                                            <option
                                                value="{{ $parentOption->id }}"
                                                data-edit-parent-option="{{ $parentOption->id }}"
                                                @selected((string) old('parent_id', $editingUnit->parent_id) === (string) $parentOption->id)
                                            >
                                                {{ $parentOption->name }} · {{ $typeLabels[$parentOption->type] ?? $parentOption->type }}
                                                @unless ($parentOption->is_active)
                                                    · Ngừng hiệu lực
                                                @endunless
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Không hiển thị chính đơn vị này và các đơn vị con bên dưới.</div>
                                </div>

                                <div class="col-md-4">
                                    <label for="edit-valid-from" class="form-label">Hiệu lực từ</label>
                                    <input
                                        type="date"
                                        class="form-control @error('valid_from') is-invalid @enderror"
                                        id="edit-valid-from"
                                        name="valid_from"
                                        value="{{ old('valid_from', $editingUnit->valid_from?->format('Y-m-d')) }}"
                                    >
                                    @error('valid_from')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="edit-valid-to" class="form-label">Hiệu lực đến</label>
                                    <input
                                        type="date"
                                        class="form-control @error('valid_to') is-invalid @enderror"
                                        id="edit-valid-to"
                                        name="valid_to"
                                        value="{{ old('valid_to', $editingUnit->valid_to?->format('Y-m-d')) }}"
                                    >
                                    @error('valid_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input type="hidden" name="is_active" value="0">
                                        <input
                                            class="form-check-input @error('is_active') is-invalid @enderror"
                                            type="checkbox"
                                            role="switch"
                                            id="edit-is-active"
                                            name="is_active"
                                            value="1"
                                            @checked((string) old('is_active', $editingUnit->is_active ? '1' : '0') === '1')
                                        >
                                        <label class="form-check-label" for="edit-is-active">Đang hiệu lực</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <p class="small text-body-secondary mb-3">
                                        Mã chính thức và thời gian hiệu lực là dữ liệu provenance theo ADR-070.
                                        Đơn vị hết hiệu lực được giữ lại, không xóa cứng.
                                    </p>
                                    <button type="submit" class="btn btn-primary px-4" style="min-height: 48px">
                                        Lưu thay đổi
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('hr.administrative-units.store') }}" novalidate>
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="create-name" class="form-label">Tên</label>
                                    <input
                                        type="text"
                                        class="form-control @error('name') is-invalid @enderror"
                                        id="create-name"
                                        name="name"
                                        value="{{ old('name') }}"
                                        maxlength="150"
                                        required
                                    >
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="create-slug" class="form-label">Slug</label>
                                    <input
                                        type="text"
                                        class="form-control @error('slug') is-invalid @enderror"
                                        id="create-slug"
                                        name="slug"
                                        value="{{ old('slug') }}"
                                        maxlength="170"
                                        pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                        placeholder="vi-du: bac-ninh"
                                        required
                                    >
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="create-official-code" class="form-label">Mã chính thức</label>
                                    <input
                                        type="text"
                                        class="form-control @error('official_code') is-invalid @enderror"
                                        id="create-official-code"
                                        name="official_code"
                                        value="{{ old('official_code') }}"
                                        maxlength="20"
                                    >
                                    @error('official_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="create-type" class="form-label">Loại</label>
                                    <select
                                        class="form-select @error('type') is-invalid @enderror"
                                        id="create-type"
                                        name="type"
                                        required
                                    >
                                        <option value="">— Chọn loại —</option>
                                        @foreach ($typeLabels as $type => $label)
                                            <option value="{{ $type }}" @selected(old('type') === $type)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-8">
                                    <label for="create-parent-id" class="form-label">Đơn vị cha</label>
                                    <select
                                        class="form-select @error('parent_id') is-invalid @enderror"
                                        id="create-parent-id"
                                        name="parent_id"
                                    >
                                        <option value="">— Cấp gốc —</option>
                                        @foreach ($createParentOptions as $parentOption)
                                            <option
                                                value="{{ $parentOption->id }}"
                                                @selected((string) old('parent_id') === (string) $parentOption->id)
                                            >
                                                {{ $parentOption->name }} · {{ $typeLabels[$parentOption->type] ?? $parentOption->type }}
                                                @unless ($parentOption->is_active)
                                                    · Ngừng hiệu lực
                                                @endunless
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="create-valid-from" class="form-label">Hiệu lực từ</label>
                                    <input
                                        type="date"
                                        class="form-control @error('valid_from') is-invalid @enderror"
                                        id="create-valid-from"
                                        name="valid_from"
                                        value="{{ old('valid_from') }}"
                                    >
                                    @error('valid_from')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="create-valid-to" class="form-label">Hiệu lực đến</label>
                                    <input
                                        type="date"
                                        class="form-control @error('valid_to') is-invalid @enderror"
                                        id="create-valid-to"
                                        name="valid_to"
                                        value="{{ old('valid_to') }}"
                                    >
                                    @error('valid_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input type="hidden" name="is_active" value="0">
                                        <input
                                            class="form-check-input @error('is_active') is-invalid @enderror"
                                            type="checkbox"
                                            role="switch"
                                            id="create-is-active"
                                            name="is_active"
                                            value="1"
                                            @checked((string) old('is_active', '1') === '1')
                                        >
                                        <label class="form-check-label" for="create-is-active">Đang hiệu lực</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <p class="small text-body-secondary mb-3">
                                        Nếu mã chính thức đã tồn tại, dữ liệu sẽ được đồng bộ qua khóa upsert ADR-070.
                                        Đơn vị hết hiệu lực phải có ngày “Hiệu lực đến” và không bị xóa cứng.
                                    </p>
                                    <button type="submit" class="btn btn-primary px-4" style="min-height: 48px">
                                        Lưu đơn vị
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </section>

            <section aria-labelledby="administrative-unit-list-title">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                    <h2 class="h5 mb-0" id="administrative-unit-list-title">Danh sách phân cấp</h2>

                    <form method="GET" action="{{ route('hr.administrative-units.index') }}" class="d-flex gap-2">
                        <label for="search" class="visually-hidden">Tìm kiếm</label>
                        <input
                            type="search"
                            class="form-control"
                            id="search"
                            name="q"
                            value="{{ request('q') }}"
                            maxlength="150"
                            placeholder="Tên, slug hoặc mã..."
                            style="min-height: 48px"
                        >
                        <button type="submit" class="btn btn-outline-primary" style="min-height: 48px">Tìm</button>
                    </form>
                </div>

                @if ($administrativeUnits->isEmpty())
                    <div class="alert alert-info mb-0">Không tìm thấy đơn vị hành chính phù hợp.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Đơn vị</th>
                                    <th scope="col">Loại</th>
                                    <th scope="col">Đơn vị cha</th>
                                    <th scope="col">Mã</th>
                                    <th scope="col">Hiệu lực</th>
                                    <th scope="col">Trạng thái</th>
                                    <th scope="col"><span class="visually-hidden">Thao tác</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($administrativeUnits as $unit)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                @if ($unit->parent_id)
                                                    <span aria-hidden="true">↳</span>
                                                @endif
                                                {{ $unit->name }}
                                            </div>
                                            <div class="small text-body-secondary">{{ $unit->slug }}</div>
                                        </td>
                                        <td>{{ $typeLabels[$unit->type] ?? $unit->type }}</td>
                                        <td>{{ $unit->parent?->name ?? 'Cấp gốc' }}</td>
                                        <td>{{ $unit->official_code ?? '—' }}</td>
                                        <td>
                                            {{ $unit->valid_from?->format('d/m/Y') ?? '—' }}
                                            <span aria-hidden="true">→</span>
                                            {{ $unit->valid_to?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $unit->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                {{ $unit->is_active ? 'Đang hiệu lực' : 'Ngừng hiệu lực' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a
                                                href="{{ route('hr.administrative-units.index', array_filter(['q' => request('q'), 'edit' => $unit->id])) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                                style="min-height: 48px; display: inline-flex; align-items: center"
                                            >
                                                Sửa
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $administrativeUnits->links() }}
                @endif
            </section>
        </main>
    </body>
</html>
