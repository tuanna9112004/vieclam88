@extends('layouts.hr')

@section('title', 'Sửa nhân viên')

@section('content')
    <h1 class="h4 mb-4">Sửa nhân viên</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('hr.staff.update', $staff) }}" novalidate style="max-width: 480px;">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Tên</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $staff->name) }}" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $staff->email) }}" required>
        </div>

        @if (auth()->user()->isSuperAdmin())
            <div class="mb-3">
                <label for="role" class="form-label">Vai trò</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="staff" @selected(old('role', $staff->role) === 'staff')>Staff</option>
                    <option value="branch_admin" @selected(old('role', $staff->role) === 'branch_admin')>Quản trị cơ sở</option>
                </select>
            </div>
        @else
            <input type="hidden" name="role" value="staff">
        @endif

        <div class="mb-3">
            <label for="branch_id" class="form-label">Cơ sở</label>
            <select class="form-select" id="branch_id" name="branch_id" required>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id', $staff->branch_id) == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Lưu</button>
    </form>

    <form method="POST" action="{{ route('hr.staff.reset-password', $staff) }}" class="mt-4" style="max-width: 480px;">
        @csrf
        <div class="mb-2">
            <label for="reset_password" class="form-label">Đặt lại mật khẩu tạm</label>
            <input type="password" class="form-control" id="reset_password" name="password" required>
        </div>
        <button type="submit" class="btn btn-outline-secondary">Đặt lại mật khẩu</button>
    </form>
@endsection
