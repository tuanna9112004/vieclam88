<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Sửa nhân viên — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5" style="max-width: 480px;">
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

            <form method="POST" action="{{ route('hr.staff.update', $staff) }}" novalidate>
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

            <form method="POST" action="{{ route('hr.staff.reset-password', $staff) }}" class="mt-4">
                @csrf
                <div class="mb-2">
                    <label for="reset_password" class="form-label">Đặt lại mật khẩu tạm</label>
                    <input type="password" class="form-control" id="reset_password" name="password" required>
                </div>
                <button type="submit" class="btn btn-outline-secondary">Đặt lại mật khẩu</button>
            </form>
        </div>
    </body>
</html>
