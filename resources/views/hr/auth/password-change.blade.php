<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Đổi mật khẩu — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5" style="max-width: 420px;">
            <h1 class="h4 mb-3">Đổi mật khẩu</h1>
            <p class="text-secondary">Bắt buộc đặt mật khẩu mới trước khi tiếp tục sử dụng hệ thống.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('hr.password.update') }}" novalidate>
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu mới</label>
                    <input type="password" class="form-control" id="password" name="password" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Xác nhận mật khẩu mới</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Đổi mật khẩu</button>
            </form>

            <form method="POST" action="{{ route('hr.logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-link px-0">Đăng xuất</button>
            </form>
        </div>
    </body>
</html>
