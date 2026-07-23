@extends('layouts.hr')

@section('title', 'Sửa công ty')

@section('content')
    <h1 class="h4 mb-4">Sửa công ty</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('hr.companies.update', $company) }}" novalidate style="max-width: 560px;">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Tên công ty</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $company->name) }}" required>
        </div>

        <div class="mb-3">
            <label for="short_name" class="form-label">Tên viết tắt</label>
            <input type="text" class="form-control" id="short_name" name="short_name" value="{{ old('short_name', $company->short_name) }}">
        </div>

        <div class="mb-3">
            <label for="industry" class="form-label">Ngành nghề</label>
            <input type="text" class="form-control" id="industry" name="industry" value="{{ old('industry', $company->industry) }}">
        </div>

        <div class="mb-3">
            <label for="website" class="form-label">Website</label>
            <input type="text" class="form-control" id="website" name="website" value="{{ old('website', $company->website) }}">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Mô tả</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $company->description) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100">Lưu</button>
    </form>
@endsection
