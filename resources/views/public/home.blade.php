@extends('layouts.public')

@section('title', 'Công việc mơ ước của bạn — '.config('app.name', 'vieclam88'))
@section('meta_description', 'Tìm việc làm khu công nghiệp, công nhân, kỹ thuật đang tuyển — cập nhật liên tục, ứng tuyển nhanh.')
@section('canonical', route('home'))

@section('content')
<section class="bg-primary bg-gradient text-white py-5">
    <div class="container">
        <h1 class="h2 fw-bold mb-3">Công việc mơ ước của bạn</h1>

        <form method="GET" action="{{ route('jobs.index') }}" class="bg-white rounded p-3 d-flex flex-column flex-md-row gap-2">
            <input
                type="search"
                name="q"
                class="form-control text-dark"
                style="min-height:48px"
                placeholder="Tìm việc làm theo tên..."
            >
            <select name="administrative_unit_id" class="form-select text-dark" style="min-height:48px; max-width: 260px;">
                <option value="">Tất cả khu vực</option>
                @foreach ($administrativeUnits as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-warning fw-semibold text-nowrap" style="min-height:48px">
                Tìm việc ngay
            </button>
        </form>
    </div>
</section>

<div class="container py-4">
    @if ($featuredJobs->isNotEmpty())
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Việc làm nổi bật</h2>
                <a href="{{ route('jobs.index', ['sort' => 'urgent']) }}" class="text-decoration-none">Xem tất cả &rarr;</a>
            </div>
            <div class="row g-3">
                @foreach ($featuredJobs as $job)
                    <div class="col-sm-6 col-lg-4">
                        @include('public.jobs._card', ['job' => $job])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($regions->isNotEmpty())
        <section class="mb-5">
            <h2 class="h4 mb-3">Việc làm theo khu vực</h2>
            <div class="row g-3">
                @foreach ($regions as $region)
                    <div class="col-6 col-md-4 col-lg-2">
                        <a
                            href="{{ route('jobs.index', ['administrative_unit_id' => $region->id]) }}"
                            class="card h-100 text-decoration-none text-dark shadow-sm text-center"
                        >
                            <div class="card-body">
                                <p class="fw-semibold mb-1">{{ $region->name }}</p>
                                <p class="text-secondary small mb-0">{{ $region->jobs_count }} việc làm</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($newestJobs->isNotEmpty())
        <section class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Việc làm mới nhất</h2>
                <a href="{{ route('jobs.index') }}" class="text-decoration-none">Xem tất cả &rarr;</a>
            </div>
            <div class="row g-3">
                @foreach ($newestJobs as $job)
                    <div class="col-sm-6 col-lg-3">
                        @include('public.jobs._card', ['job' => $job])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="text-center">
        <a href="{{ route('jobs.index') }}" class="btn btn-primary btn-lg" style="min-height:48px">
            Xem tất cả việc làm
        </a>
    </div>
</div>
@endsection
