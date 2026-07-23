@extends('layouts.public')

@section('title', 'Câu hỏi thường gặp — '.config('app.name', 'vieclam88'))
@section('meta_description', 'Giải đáp các câu hỏi thường gặp khi tìm việc và ứng tuyển trên vieclam88.')
@section('canonical', route('faqs.index'))

@section('content')
<div class="container">
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Câu hỏi thường gặp</li>
        </ol>
    </nav>

    <h1 class="h2 mb-4">Câu hỏi thường gặp</h1>

    @if ($faqs->isEmpty())
        <div class="alert alert-light border text-center py-5">
            Chưa có câu hỏi thường gặp nào.
        </div>
    @else
        <div class="accordion" id="faq-accordion">
            @foreach ($faqs as $faq)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-{{ $faq->id }}">
                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#faq-collapse-{{ $faq->id }}"
                            aria-expanded="false"
                            aria-controls="faq-collapse-{{ $faq->id }}"
                        >
                            {{ $faq->question }}
                        </button>
                    </h2>
                    <div
                        id="faq-collapse-{{ $faq->id }}"
                        class="accordion-collapse collapse"
                        aria-labelledby="faq-heading-{{ $faq->id }}"
                        data-bs-parent="#faq-accordion"
                    >
                        <div class="accordion-body">{{ $faq->answer }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
