<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Địa điểm — {{ $company->name }} — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h4 mb-0">Địa điểm — {{ $company->name }}</h1>
                <a href="{{ route('hr.companies.index') }}" class="btn btn-outline-secondary">Về danh sách công ty</a>
            </div>

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
                    <h2 class="h6 mb-3">Thêm địa điểm</h2>

                    <form method="POST" action="{{ route('hr.company-locations.store', $company) }}" novalidate>
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="name" class="form-label">Tên địa điểm</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                            </div>

                            <div class="col-md-3">
                                <label for="administrative_unit_id" class="form-label">Tỉnh/thành</label>
                                <select class="form-select" id="administrative_unit_id" name="administrative_unit_id">
                                    <option value="">-- Chưa xác định --</option>
                                    @foreach ($administrativeUnits as $unit)
                                        <option value="{{ $unit->id }}" @selected(old('administrative_unit_id') == $unit->id)>{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="industrial_park_id" class="form-label">Khu công nghiệp</label>
                                <select class="form-select" id="industrial_park_id" name="industrial_park_id">
                                    <option value="">-- Không --</option>
                                    @foreach ($industrialParks as $park)
                                        <option value="{{ $park->id }}" @selected(old('industrial_park_id') == $park->id)>{{ $park->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Thêm</button>
                            </div>

                            <div class="col-12">
                                <label for="address_detail" class="form-label">Địa chỉ chi tiết</label>
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
                        <th>Tỉnh/thành</th>
                        <th>Khu công nghiệp</th>
                        <th>Địa chỉ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($locations as $location)
                        @php $formId = 'location-edit-'.$location->id; @endphp
                        <tr>
                            <td>
                                <input type="text" class="form-control form-control-sm" form="{{ $formId }}" name="name" value="{{ $location->name }}" required>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" form="{{ $formId }}" name="administrative_unit_id">
                                    <option value="">-- Chưa xác định --</option>
                                    @foreach ($administrativeUnits as $unit)
                                        <option value="{{ $unit->id }}" @selected($location->administrative_unit_id === $unit->id)>{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" form="{{ $formId }}" name="industrial_park_id">
                                    <option value="">-- Không --</option>
                                    @foreach ($industrialParks as $park)
                                        <option value="{{ $park->id }}" @selected($location->industrial_park_id === $park->id)>{{ $park->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" form="{{ $formId }}" name="address_detail" value="{{ $location->address_detail }}">
                            </td>
                            <td class="text-end">
                                <button type="submit" form="{{ $formId }}" class="btn btn-sm btn-outline-secondary">Lưu</button>

                                @can('delete', $location)
                                    <form method="POST" action="{{ route('hr.company-locations.destroy', [$company, $location]) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @foreach ($locations as $location)
                <form id="location-edit-{{ $location->id }}" method="POST" action="{{ route('hr.company-locations.update', [$company, $location]) }}" class="d-none">
                    @csrf
                    @method('PUT')
                </form>
            @endforeach
        </div>
    </body>
</html>
