@extends('layouts.public')

@section('title', $company->name.' — Công ty tuyển dụng | '.config('app.name', 'vieclam88'))
@section('meta_description', \Illuminate\Support\Str::limit($company->description ?: 'Xem thông tin công khai và việc làm đang tuyển tại '.$company->name.'.', 155))
@section('canonical', route('companies.show', $company->slug))

@section('content')
<div class="container">
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">Công ty</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $company->name }}</li>
        </ol>
    </nav>

    <section class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                <div>
                    <h1 class="h2 mb-2">{{ $company->name }}</h1>
                    @if ($company->industry)
                        <p class="text-secondary mb-2">{{ $company->industry }}</p>
                    @endif
                    @if ($company->description)
                        <p class="mb-0">{!! nl2br(e($company->description)) !!}</p>
                    @endif
                </div>
                @if ($company->is_verified)
                    <div><span class="badge text-bg-success">Đã xác minh</span></div>
                @endif
            </div>
        </div>
    </section>

    <section>
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <h2 class="h4 mb-0">Việc làm đang tuyển</h2>
            <a href="{{ route('jobs.index', ['company_id' => $company->id]) }}" class="btn btn-outline-primary" style="min-height:48px">
                Xem trong bộ lọc việc làm
            </a>
        </div>

        @if ($jobs->isEmpty())
            <div class="alert alert-light border text-center py-5">
                Công ty hiện chưa có việc làm công khai còn hiệu lực.
            </div>
        @else
            <div class="row g-3">
                @foreach ($jobs as $job)
                    <div class="col-12 col-lg-6">
                        @include('public.jobs._card', ['job' => $job])
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $jobs->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
</div>
@endsection
