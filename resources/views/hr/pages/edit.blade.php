@extends('layouts.hr')

@section('title', 'Sửa trang')

@section('content')
    <h1 class="h4 mb-4">Sửa trang</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('hr.pages.update', $page) }}" novalidate style="max-width: 720px;">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="title" class="form-label">Tiêu đề</label>
            <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $page->title) }}" maxlength="200" required>
        </div>

        <div class="mb-3">
            <label for="slug" class="form-label">Slug</label>
            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $page->slug) }}" maxlength="220"
                pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required>
        </div>

        <div class="mb-3">
            <label for="content" class="form-label">Nội dung</label>
            <textarea class="form-control" id="content" name="content" rows="10" required>{{ old('content', $page->content) }}</textarea>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="meta_title" class="form-label">Meta title</label>
                <input type="text" class="form-control" id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" maxlength="255">
            </div>
            <div class="col-md-6">
                <label for="status" class="form-label">Trạng thái</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="draft" @selected(old('status', $page->status->value) === 'draft')>Nháp</option>
                    <option value="published" @selected(old('status', $page->status->value) === 'published')>Công khai</option>
                    <option value="hidden" @selected(old('status', $page->status->value) === 'hidden')>Ẩn</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="meta_description" class="form-label">Meta description</label>
            <textarea class="form-control" id="meta_description" name="meta_description" rows="2" maxlength="320">{{ old('meta_description', $page->meta_description) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="min-height:48px">Lưu thay đổi</button>
    </form>
@endsection
