@php
    $primaryLocation = $job->jobLocations->first()?->companyLocation;
    $shiftNames = $job->relationLoaded('jobWorkShifts')
        ? $job->jobWorkShifts->pluck('workShift.name')->filter()->implode(', ')
        : '';
    $companyInitial = mb_strtoupper(mb_substr($job->company?->name ?? '?', 0, 1));
@endphp
<article class="job-card">
    <div class="job-card__body">
        <div class="d-flex gap-3 mb-2">
            <div class="job-card__avatar" aria-hidden="true">{{ $companyInitial }}</div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <h3 class="h6 job-card__title mb-1">
                        <a href="{{ route('jobs.show', $job->slug) }}" class="text-decoration-none text-dark stretched-link">
                            {{ $job->title }}
                        </a>
                    </h3>
                    @if ($job->is_urgent)
                        <span class="badge text-bg-danger text-nowrap">Tuyển gấp</span>
                    @endif
                </div>
                <p class="text-secondary small mb-0">{{ $job->company?->name }}</p>
            </div>
        </div>

        <p class="fw-semibold text-primary mb-2">{{ $job->formattedSalary() }}</p>

        <div class="d-flex flex-column gap-1 mb-2">
            @if ($primaryLocation)
                <p class="job-card__meta mb-0">
                    <span class="job-card__meta-icon" aria-hidden="true">&#128205;</span>
                    <span>
                        {{ $primaryLocation->name }}@if ($primaryLocation->administrativeUnit)
                            , {{ $primaryLocation->administrativeUnit->name }}
                        @endif
                    </span>
                </p>
            @endif

            @if ($shiftNames)
                <p class="job-card__meta mb-0">
                    <span class="job-card__meta-icon" aria-hidden="true">&#128337;</span>
                    <span>{{ $shiftNames }}</span>
                </p>
            @endif

            @if ($job->published_at)
                <p class="job-card__meta mb-0">
                    <span class="job-card__meta-icon" aria-hidden="true">&#128197;</span>
                    <span>Đăng {{ $job->published_at->diffForHumans() }}</span>
                </p>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            @if ($job->has_shuttle_bus)
                <span class="badge text-bg-light border">Xe đưa đón</span>
            @endif
            @if ($job->has_accommodation)
                <span class="badge text-bg-light border">Chỗ ở</span>
            @endif
        </div>

        <div class="job-card__spacer"></div>

        <div class="cta-group position-relative" style="z-index: 2">
            <a href="{{ route('jobs.show', $job->slug) }}" class="btn btn-primary flex-grow-1">
                Xem chi tiết
            </a>
            @if ($job->relationLoaded('ownerBranch') && $job->ownerBranch?->phone)
                <a href="tel:{{ $job->ownerBranch->phone }}" class="btn btn-outline-primary">
                    Gọi {{ $job->ownerBranch->phone }}
                </a>
            @endif
        </div>
    </div>
</article>
