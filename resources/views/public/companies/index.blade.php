@extends('layouts.public')

@section('title', 'Danh sách công ty đang hoạt động — '.config('app.name', 'vieclam88'))
@section('meta_description', 'Khám phá các công ty, nhà máy đang hoạt động và việc làm công khai còn hiệu lực trên vieclam88.')
@section('canonical', route('companies.index'))

@section('content')
<div class="container">
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Công ty</li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-2 mb-4">
        <div>
            <h1 class="h2 mb-2">Công ty đang hoạt động</h1>
            <p class="text-secondary mb-0">Thông tin công khai và các vị trí đang tuyển còn hiệu lực.</p>
        </div>
        <span class="text-secondary">{{ $companies->total() }} công ty</span>
    </div>

    @if ($companies->isEmpty())
        <div class="alert alert-light border text-center py-5">
            Chưa có công ty công khai.
        </div>
    @else
        <div class="row g-3">
            @foreach ($companies as $company)
                <div class="col-12 col-md-6 col-lg-4">
                    <article class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5">
                                <a href="{{ route('companies.show', $company->slug) }}" class="text-decoration-none text-dark">
                                    {{ $company->name }}
                                </a>
                            </h2>

                            @if ($company->industry)
                                <p class="text-secondary mb-2">{{ $company->industry }}</p>
                            @endif

                            @if ($company->description)
                                <p class="mb-3">{{ \Illuminate\Support\Str::limit($company->description, 140) }}</p>
                            @endif

                            <div class="mt-auto d-flex justify-content-between align-items-center gap-2">
                                <span class="badge text-bg-light border">
                                    {{ $company->public_jobs_count }} việc đang tuyển
                                </span>
                                <a href="{{ route('companies.show', $company->slug) }}" class="btn btn-primary" style="min-height:48px">
                                    Xem công ty
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $companies->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
