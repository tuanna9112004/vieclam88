@extends('layouts.hr')

@section('title', 'Đầu mối — '.$company->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Đầu mối — {{ $company->name }}</h1>
        <a href="{{ route('hr.companies.index') }}" class="btn btn-outline-secondary">Về danh sách công ty</a>
    </div>

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
            <h2 class="h6 mb-3">Thêm đầu mối</h2>

            <form method="POST" action="{{ route('hr.company-contacts.store', $company) }}" novalidate>
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="name" class="form-label">Tên</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                    </div>

                    <div class="col-md-3">
                        <label for="position" class="form-label">Chức vụ</label>
                        <input type="text" class="form-control" id="position" name="position" value="{{ old('position') }}">
                    </div>

                    <div class="col-md-2">
                        <label for="phone" class="form-label">Điện thoại</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
                    </div>

                    <div class="col-md-3">
                        <label for="zalo" class="form-label">Zalo</label>
                        <input type="text" class="form-control" id="zalo" name="zalo" value="{{ old('zalo') }}">
                    </div>

                    <div class="col-md-4">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="is_primary" value="0">
                            <input type="checkbox" class="form-check-input" id="is_primary" name="is_primary" value="1" @checked(old('is_primary'))>
                            <label for="is_primary" class="form-check-label">Đầu mối chính</label>
                        </div>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="is_public" value="0">
                            <input type="checkbox" class="form-check-input" id="is_public" name="is_public" value="1" @checked(old('is_public'))>
                            <label for="is_public" class="form-check-label">Công khai</label>
                        </div>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Thêm</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Điện thoại/Zalo</th>
                    <th>Email</th>
                    <th>Chính</th>
                    <th>Công khai</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($contacts as $contact)
                    <tr>
                        <td>{{ $contact->name }}</td>
                        <td>{{ $contact->phone }}{{ $contact->phone && $contact->zalo ? ' / ' : '' }}{{ $contact->zalo }}</td>
                        <td>{{ $contact->email }}</td>
                        <td>{{ $contact->is_primary ? 'Có' : '' }}</td>
                        <td>{{ $contact->is_public ? 'Có' : '' }}</td>
                        <td>{{ $contact->status->value === 'active' ? 'Hoạt động' : 'Ngừng hoạt động' }}</td>
                        <td class="text-end">
                            @can('delete', $contact)
                                <form method="POST" action="{{ route('hr.company-contacts.destroy', [$company, $contact]) }}" class="d-inline">
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
    </div>
@endsection
