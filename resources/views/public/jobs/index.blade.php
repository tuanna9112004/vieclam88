@extends('layouts.public')

@section('title', 'Việc làm đang tuyển — '.config('app.name', 'vieclam88'))
@section('canonical', route('jobs.index'))

@php
    $salaryBuckets = [
        'thoa-thuan' => 'Thỏa thuận',
        'duoi-10' => 'Dưới 10 triệu',
        '10-15' => '10 - 15 triệu',
        '15-20' => '15 - 20 triệu',
        '20-30' => '20 - 30 triệu',
        '30-50' => '30 - 50 triệu',
        'tren-50' => 'Trên 50 triệu',
    ];

    $sortOptions = [
        'latest' => 'Mới nhất',
        'urgent' => 'Tuyển gấp',
        'salary_desc' => 'Lương cao nhất',
    ];

    $hasActiveFilters = collect($filters)->filter()->isNotEmpty();
@endphp

@section('content')
<div class="container" x-data="{ filtersOpen: false }">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Việc làm đang tuyển</h1>
        <button
            type="button"
            class="btn btn-outline-secondary d-lg-none"
            style="min-height:48px"
            @click="filtersOpen = true"
        >
            Bộ lọc
        </button>
    </div>

    <div class="row g-4">
        {{-- Filter: sidebar on desktop, slide-over drawer on mobile (ui-guidelines.md) --}}
        <div
            class="col-lg-3"
            x-cloak
            :class="filtersOpen ? 'd-block position-fixed top-0 start-0 vh-100 w-100 bg-white p-3 overflow-auto' : 'd-none d-lg-block'"
            style="z-index: 1050;"
        >
            <div class="filter-panel">
                <div class="d-flex align-items-center justify-content-between d-lg-none mb-3">
                    <h2 class="h5 mb-0">Bộ lọc việc làm</h2>
                    <button type="button" class="btn-close" aria-label="Đóng bộ lọc" @click="filtersOpen = false"></button>
                </div>

                <form method="GET" action="{{ route('jobs.index') }}" class="d-flex flex-column gap-3">
                    {{-- Tu khoa khong con o dang truong nhap rieng trong bo loc — van giu lai
                         (an) de khong mat tim kiem dang den tu trang chu khi ap dung bo loc khac. --}}
                    <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">

                    <div>
                        <label class="form-label fw-semibold">Khu công nghiệp</label>
                        <x-multi-select
                            name="industrial_park_id"
                            :options="$industrialParks->pluck('name', 'id')->all()"
                            :selected="$filters['industrial_park_id'] ?? []"
                            placeholder="Tất cả"
                        />
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Đơn vị hành chính</label>
                        <x-multi-select
                            name="administrative_unit_id"
                            :options="$administrativeUnits->pluck('name', 'id')->all()"
                            :selected="$filters['administrative_unit_id'] ?? []"
                            placeholder="Tất cả"
                        />
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Mức lương</label>
                        <x-multi-select
                            name="salary"
                            :options="$salaryBuckets"
                            :selected="$filters['salary'] ?? []"
                            placeholder="Tất cả"
                        />
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Ca làm việc</label>
                        <x-multi-select
                            name="work_shift_id"
                            :options="$workShifts->pluck('name', 'id')->all()"
                            :selected="$filters['work_shift_id'] ?? []"
                            placeholder="Tất cả"
                        />
                    </div>

                    <div class="form-check" style="min-height:48px">
                        <input type="checkbox" class="form-check-input" id="shuttle_bus" name="shuttle_bus" value="1"
                            @checked(($filters['shuttle_bus'] ?? null) === '1')>
                        <label class="form-check-label" for="shuttle_bus">Có xe đưa đón</label>
                    </div>

                    <div class="form-check" style="min-height:48px">
                        <input type="checkbox" class="form-check-input" id="accommodation" name="accommodation" value="1"
                            @checked(($filters['accommodation'] ?? null) === '1')>
                        <label class="form-check-label" for="accommodation">Có chỗ ở</label>
                    </div>

                    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1" style="min-height:48px">Áp dụng</button>
                        @if ($hasActiveFilters)
                            <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary" style="min-height:48px">Bỏ lọc</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-9">
            <form method="GET" action="{{ route('jobs.index') }}" class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                @foreach (['q', 'shuttle_bus', 'accommodation'] as $preserved)
                    @if (! empty($filters[$preserved]))
                        <input type="hidden" name="{{ $preserved }}" value="{{ $filters[$preserved] }}">
                    @endif
                @endforeach
                @foreach (['industrial_park_id', 'administrative_unit_id', 'work_shift_id', 'salary'] as $multiPreserved)
                    @foreach ((array) ($filters[$multiPreserved] ?? []) as $value)
                        <input type="hidden" name="{{ $multiPreserved }}[]" value="{{ $value }}">
                    @endforeach
                @endforeach

                <span class="text-secondary fw-semibold">{{ $jobs->total() }} việc làm</span>

                <div class="d-flex align-items-center gap-2">
                    <label for="sort" class="form-label mb-0 text-nowrap">Sắp xếp</label>
                    <select id="sort" name="sort" class="form-select" onchange="this.form.submit()">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'latest') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($jobs->isEmpty())
                <div class="empty-state">
                    <p class="fs-5 fw-semibold mb-1">Không tìm thấy việc làm phù hợp bộ lọc hiện tại.</p>
                    <p class="mb-3">Thử bỏ bớt bộ lọc hoặc tìm với từ khóa khác.</p>
                    @if ($hasActiveFilters)
                        <a href="{{ route('jobs.index') }}" class="btn btn-outline-primary" style="min-height:48px">Bỏ lọc</a>
                    @endif
                </div>
            @else
                <div class="row g-3">
                    @foreach ($jobs as $job)
                        <div class="col-md-6">
                            @include('public.jobs._card', ['job' => $job])
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $jobs->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
