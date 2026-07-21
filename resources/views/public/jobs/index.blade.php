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
    <div class="d-flex align-items-center justify-content-between mb-3">
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
            <div class="d-flex align-items-center justify-content-between d-lg-none mb-3">
                <h2 class="h5 mb-0">Bộ lọc việc làm</h2>
                <button type="button" class="btn-close" aria-label="Đóng bộ lọc" @click="filtersOpen = false"></button>
            </div>

            <form method="GET" action="{{ route('jobs.index') }}" class="d-flex flex-column gap-3">
                <div>
                    <label for="q" class="form-label">Từ khóa</label>
                    <input type="search" id="q" name="q" class="form-control" placeholder="Tên việc làm..." value="{{ $filters['q'] ?? '' }}">
                </div>

                <div>
                    <label for="industrial_park_id" class="form-label">Khu công nghiệp</label>
                    <select id="industrial_park_id" name="industrial_park_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($industrialParks as $park)
                            <option value="{{ $park->id }}" @selected((string) ($filters['industrial_park_id'] ?? '') === (string) $park->id)>
                                {{ $park->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="administrative_unit_id" class="form-label">Đơn vị hành chính</label>
                    <select id="administrative_unit_id" name="administrative_unit_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($administrativeUnits as $unit)
                            <option value="{{ $unit->id }}" @selected((string) ($filters['administrative_unit_id'] ?? '') === (string) $unit->id)>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="work_shift_id" class="form-label">Ca làm việc</label>
                    <select id="work_shift_id" name="work_shift_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($workShifts as $shift)
                            <option value="{{ $shift->id }}" @selected((string) ($filters['work_shift_id'] ?? '') === (string) $shift->id)>
                                {{ $shift->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="salary" class="form-label">Mức lương</label>
                    <select id="salary" name="salary" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($salaryBuckets as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['salary'] ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
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

        <div class="col-lg-9">
            <form method="GET" action="{{ route('jobs.index') }}" class="d-flex justify-content-between align-items-center mb-3 gap-2">
                @foreach (['q', 'industrial_park_id', 'administrative_unit_id', 'work_shift_id', 'salary', 'shuttle_bus', 'accommodation'] as $preserved)
                    @if (! empty($filters[$preserved]))
                        <input type="hidden" name="{{ $preserved }}" value="{{ $filters[$preserved] }}">
                    @endif
                @endforeach

                <span class="text-secondary">{{ $jobs->total() }} việc làm</span>

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
                <div class="text-center text-secondary py-5">
                    Không tìm thấy việc làm phù hợp bộ lọc hiện tại.
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach ($jobs as $job)
                        @include('public.jobs._card', ['job' => $job])
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
