@extends('layouts.hr')

@section('title', 'Đơn vị hành chính')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1">Đơn vị hành chính (cũ)</h1>
        <p class="text-body-secondary mb-0">
            Xem lại cây tỉnh, thành phố, xã, phường theo dữ liệu lịch sử.
        </p>
    </div>

    <div class="alert alert-warning" role="alert">
        <p class="fw-semibold mb-2">Trang này chỉ còn ở chế độ xem (read-only).</p>
        <p class="mb-0">
            TASK 1.3: dữ liệu địa chỉ mới nằm ở <code>provinces</code>/<code>wards</code>, đồng bộ qua
            <code>php artisan locations:sync</code>. Không còn tạo/sửa đơn vị hành chính cũ qua giao diện
            này trong giai đoạn chuyển tiếp — dữ liệu bên dưới được giữ lại để tra cứu và làm fallback
            hiển thị cho bản ghi nghiệp vụ chưa backfill sang ward mới.
        </p>
    </div>

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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $administrativeUnits->links() }}
        @endif
    </section>
@endsection
