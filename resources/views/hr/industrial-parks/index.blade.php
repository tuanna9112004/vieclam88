<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Khu công nghiệp — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5">
            <h1 class="h4 mb-4">Khu công nghiệp</h1>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Thêm khu công nghiệp</h2>

                    <form method="POST" action="{{ route('hr.industrial-parks.store') }}" novalidate>
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="name" class="form-label">Tên</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                            </div>

                            <div class="col-md-3">
                                <label for="administrative_unit_id" class="form-label">Đơn vị hành chính</label>
                                <select class="form-select" id="administrative_unit_id" name="administrative_unit_id" required>
                                    <option value="">-- Chọn --</option>
                                    @foreach ($administrativeUnits as $unit)
                                        <option value="{{ $unit->id }}" @selected(old('administrative_unit_id') == $unit->id)>{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="official_name" class="form-label">Tên chính thức</label>
                                <input type="text" class="form-control" id="official_name" name="official_name" value="{{ old('official_name') }}">
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Thêm</button>
                            </div>

                            <div class="col-12">
                                <label for="address_detail" class="form-label">Địa chỉ</label>
                                <input type="text" class="form-control" id="address_detail" name="address_detail" value="{{ old('address_detail') }}">
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Đơn vị hành chính</th>
                        <th>Tên chính thức</th>
                        <th>Địa chỉ</th>
                        <th>Hoạt động</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($industrialParks as $park)
                        @php $formId = 'ip-edit-'.$park->id; @endphp
                        <tr>
                            <td>
                                <input type="text" class="form-control form-control-sm" form="{{ $formId }}" name="name" value="{{ $park->name }}" required>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" form="{{ $formId }}" name="administrative_unit_id" required>
                                    @foreach ($administrativeUnits as $unit)
                                        <option value="{{ $unit->id }}" @selected($park->administrative_unit_id === $unit->id)>{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" form="{{ $formId }}" name="official_name" value="{{ $park->official_name }}">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" form="{{ $formId }}" name="address_detail" value="{{ $park->address_detail }}">
                            </td>
                            <td class="text-center">
                                <input type="hidden" form="{{ $formId }}" name="is_active" value="0">
                                <input type="checkbox" class="form-check-input" form="{{ $formId }}" name="is_active" value="1" @checked($park->is_active)>
                            </td>
                            <td>
                                <button type="submit" form="{{ $formId }}" class="btn btn-sm btn-outline-secondary">Lưu</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @foreach ($industrialParks as $park)
                <form id="ip-edit-{{ $park->id }}" method="POST" action="{{ route('hr.industrial-parks.update', $park) }}" class="d-none">
                    @csrf
                    @method('PUT')
                </form>
            @endforeach

            {{ $industrialParks->links() }}
        </div>
    </body>
</html>
