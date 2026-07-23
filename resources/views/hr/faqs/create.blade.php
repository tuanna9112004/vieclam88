@extends('layouts.hr')

@section('title', 'Thêm câu hỏi')

@section('content')
    <h1 class="h4 mb-4">Thêm câu hỏi</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('hr.faqs.store') }}" novalidate style="max-width: 640px;">
        @csrf

        <div class="mb-3">
            <label for="question" class="form-label">Câu hỏi</label>
            <input type="text" class="form-control" id="question" name="question" value="{{ old('question') }}" maxlength="255" required>
        </div>

        <div class="mb-3">
            <label for="answer" class="form-label">Câu trả lời</label>
            <textarea class="form-control" id="answer" name="answer" rows="5" required>{{ old('answer') }}</textarea>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="sort_order" class="form-label">Thứ tự</label>
                <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="32767" required>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                        @checked((string) old('is_active', '1') === '1')>
                    <label class="form-check-label" for="is_active">Hiển thị công khai</label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="min-height:48px">Tạo câu hỏi</button>
    </form>
@endsection
