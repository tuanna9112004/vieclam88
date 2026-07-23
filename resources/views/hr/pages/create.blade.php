<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Thêm trang — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5" style="max-width: 720px;">
            <h1 class="h4 mb-4">Thêm trang</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('hr.pages.store') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="title" class="form-label">Tiêu đề</label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" maxlength="200" required>
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug') }}" maxlength="220"
                        pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="vi-du: gioi-thieu" required>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Nội dung</label>
                    <textarea class="form-control" id="content" name="content" rows="10" required>{{ old('content') }}</textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="meta_title" class="form-label">Meta title</label>
                        <input type="text" class="form-control" id="meta_title" name="meta_title" value="{{ old('meta_title') }}" maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="draft" @selected(old('status', 'draft') === 'draft')>Nháp</option>
                            <option value="published" @selected(old('status') === 'published')>Công khai</option>
                            <option value="hidden" @selected(old('status') === 'hidden')>Ẩn</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="meta_description" class="form-label">Meta description</label>
                    <textarea class="form-control" id="meta_description" name="meta_description" rows="2" maxlength="320">{{ old('meta_description') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="min-height:48px">Tạo trang</button>
            </form>
        </div>
    </body>
</html>
