@extends('layouts.public')

@section('title', 'Việc làm tại '.$industrialPark->name.' — '.config('app.name', 'vieclam88'))
@section('meta_description', 'Danh sách việc làm công khai còn hiệu lực tại '.$industrialPark->name.', '.$industrialPark->administrativeUnit->name.'.')
@section('canonical', route('industrial-parks.show', $industrialPark->slug))

@section('content')
<div class="container">
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('jobs.index') }}">Việc làm</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $industrialPark->name }}</li>
        </ol>
    </nav>

    <section class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <h1 class="h2 mb-2">{{ $industrialPark->name }}</h1>
            @if ($industrialPark->official_name)
                <p class="mb-2">{{ $industrialPark->official_name }}</p>
            @endif
            <p class="text-secondary mb-0">
                {{ $industrialPark->address_detail ? $industrialPark->address_detail.', ' : '' }}{{ $industrialPark->administrativeUnit->name }}
            </p>
        </div>
    </section>

    <section>
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <h2 class="h4 mb-0">Việc làm đang tuyển tại khu công nghiệp</h2>
            <a href="{{ route('jobs.index', ['industrial_park_id' => $industrialPark->id]) }}" class="btn btn-outline-primary" style="min-height:48px">
                Mở bộ lọc KCN
            </a>
        </div>

        @if ($jobs->isEmpty())
            <div class="alert alert-light border text-center py-5">
                Khu công nghiệp hiện chưa có việc làm công khai còn hiệu lực.
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
