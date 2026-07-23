@extends('layouts.public')

@section('title', 'Công việc mơ ước của bạn — '.config('app.name', 'vieclam88'))
@section('meta_description', 'Tìm việc làm khu công nghiệp, công nhân, kỹ thuật đang tuyển — cập nhật liên tục, ứng tuyển nhanh.')
@section('canonical', route('home'))

@php
    $heroSalaryBuckets = [
        'thoa-thuan' => 'Thỏa thuận',
        'duoi-10' => 'Dưới 10 triệu',
        '10-15' => '10 - 15 triệu',
        '15-20' => '15 - 20 triệu',
        '20-30' => '20 - 30 triệu',
        '30-50' => '30 - 50 triệu',
        'tren-50' => 'Trên 50 triệu',
    ];
@endphp

@section('content')
<section class="pub-hero py-5">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <p class="pub-hero__eyebrow mb-1">Cùng tìm kiếm</p>
                <h1 class="h2 fw-bold mb-4">Công việc mơ ước của bạn</h1>

                <form method="GET" action="{{ route('jobs.index') }}" class="pub-hero__search">
                    <div class="pub-hero__search-main">
                        <span class="pub-hero__search-icon" aria-hidden="true">&#128269;</span>
                        <label for="hero-search-q" class="visually-hidden">Tìm việc làm theo tên</label>
                        <input
                            type="search"
                            id="hero-search-q"
                            name="q"
                            class="pub-hero__search-input"
                            placeholder="Tìm kiếm việc làm"
                        >
                        <button type="submit" class="btn btn-primary fw-semibold pub-hero__search-submit">
                            Tìm kiếm
                        </button>
                    </div>

                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <x-multi-select
                                name="administrative_unit_id"
                                :options="$administrativeUnits->pluck('name', 'id')->all()"
                                placeholder="Tất cả tỉnh thành"
                            />
                        </div>
                        <div class="col-6">
                            <x-multi-select
                                name="salary"
                                :options="$heroSalaryBuckets"
                                placeholder="Tất cả mức lương"
                            />
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-5 d-none d-lg-block">
                <div class="pub-hero__art">
                    <div class="pub-hero__art-blob pub-hero__art-blob--1" aria-hidden="true"></div>
                    <div class="pub-hero__art-blob pub-hero__art-blob--2" aria-hidden="true"></div>
                    <div class="pub-hero__art-icon" aria-hidden="true">&#127981;</div>

                    @if ($regions->isNotEmpty())
                        <div class="pub-hero__tags">
                            @foreach ($regions->take(6) as $region)
                                <a
                                    href="{{ route('jobs.index', ['administrative_unit_id' => $region->id]) }}"
                                    class="pub-hero__tag text-decoration-none"
                                >
                                    {{ $region->name }} <span class="opacity-75">· {{ $region->jobs_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    @if ($featuredJobs->isNotEmpty())
        <section class="pub-section">
            <div class="pub-section__head">
                <h2 class="h4 pub-section__title">Việc làm nổi bật</h2>
                <a href="{{ route('jobs.index', ['sort' => 'urgent']) }}" class="text-decoration-none fw-semibold">
                    Xem tất cả &rarr;
                </a>
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
        <section class="pub-section">
            <div class="pub-section__head">
                <h2 class="h4 pub-section__title">Tìm việc nhanh theo khu vực</h2>
            </div>
            <div class="row g-3">
                @foreach ($regions as $region)
                    <div class="col-6 col-md-4 col-lg-2">
                        <a
                            href="{{ route('jobs.index', ['administrative_unit_id' => $region->id]) }}"
                            class="quick-link-tile card h-100 text-decoration-none text-dark text-center"
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
        <section class="pub-section">
            <div class="pub-section__head">
                <h2 class="h4 pub-section__title">Việc làm mới nhất</h2>
                <a href="{{ route('jobs.index') }}" class="text-decoration-none fw-semibold">Xem tất cả &rarr;</a>
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

    <section class="pub-section">
        <div class="pub-section__head">
            <h2 class="h4 pub-section__title">Quy trình ứng tuyển</h2>
        </div>
        <div class="row g-3">
            @foreach ([
                ['Tìm việc phù hợp', 'Lọc theo khu vực, mức lương và ca làm.'],
                ['Xem thông tin tuyển dụng', 'Đọc mô tả công việc, phúc lợi và địa điểm.'],
                ['Gửi hồ sơ ứng tuyển', 'Điền form ngắn, không cần tạo tài khoản.'],
                ['Nhân viên hỗ trợ', 'Cơ sở phụ trách liên hệ tư vấn qua điện thoại/Zalo.'],
                ['Phỏng vấn và nhận việc', 'Sắp xếp lịch hẹn phù hợp với bạn.'],
            ] as [$stepTitle, $stepDesc])
                <div class="col-6 col-md-4 col-lg">
                    <div class="process-step">
                        <div class="process-step__badge">{{ $loop->iteration }}</div>
                        <p class="fw-semibold mb-1">{{ $stepTitle }}</p>
                        <p class="text-secondary small mb-0">{{ $stepDesc }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="text-center bg-white rounded-4 shadow-sm py-5 px-3">
        <h2 class="h3 mb-2">Sẵn sàng tìm việc phù hợp?</h2>
        <p class="text-secondary mb-4">Hàng trăm vị trí đang tuyển tại các khu công nghiệp — tìm và ứng tuyển ngay hôm nay.</p>
        <a href="{{ route('jobs.index') }}" class="btn btn-primary btn-lg" style="min-height:48px">
            Xem tất cả việc làm
        </a>
    </section>
</div>
@endsection
