<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Thêm công ty — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5" style="max-width: 560px;">
            <h1 class="h4 mb-4">Thêm công ty</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('hr.companies.store') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Tên công ty</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>

                <div class="mb-3">
                    <label for="short_name" class="form-label">Tên viết tắt</label>
                    <input type="text" class="form-control" id="short_name" name="short_name" value="{{ old('short_name') }}">
                </div>

                <div class="mb-3">
                    <label for="industry" class="form-label">Ngành nghề</label>
                    <input type="text" class="form-control" id="industry" name="industry" value="{{ old('industry') }}">
                </div>

                <div class="mb-3">
                    <label for="website" class="form-label">Website</label>
                    <input type="text" class="form-control" id="website" name="website" value="{{ old('website') }}">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Tạo công ty</button>
            </form>
        </div>
    </body>
</html>
