@php
    $primaryLocation = $job->jobLocations->first()?->companyLocation;
    $shiftNames = $job->relationLoaded('jobWorkShifts')
        ? $job->jobWorkShifts->pluck('workShift.name')->filter()->implode(', ')
        : '';
@endphp
<article class="card h-100 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <h3 class="h5 mb-1">
                <a href="{{ route('jobs.show', $job->slug) }}" class="text-decoration-none text-dark">
                    {{ $job->title }}
                </a>
            </h3>
            @if ($job->is_urgent)
                <span class="badge text-bg-danger text-nowrap">Tuyển gấp</span>
            @endif
        </div>
        <p class="text-secondary mb-2">{{ $job->company?->name }}</p>

        <p class="fw-semibold text-primary mb-2">{{ $job->formattedSalary() }}</p>

        <p class="mb-2">
            @if ($primaryLocation)
                {{ $primaryLocation->name }}@if ($primaryLocation->administrativeUnit)
                    , {{ $primaryLocation->administrativeUnit->name }}
                @endif
            @endif
        </p>

        @if ($shiftNames)
            <p class="mb-2 text-secondary">Ca: {{ $shiftNames }}</p>
        @endif

        <div class="d-flex flex-wrap gap-2 mb-3">
            @if ($job->has_shuttle_bus)
                <span class="badge text-bg-light border">Xe đưa đón</span>
            @endif
            @if ($job->has_accommodation)
                <span class="badge text-bg-light border">Chỗ ở</span>
            @endif
        </div>

        @if ($job->relationLoaded('ownerBranch') && ($job->ownerBranch?->phone || $job->ownerBranch?->zalo))
            <div class="d-flex flex-wrap gap-2">
                @if ($job->ownerBranch->phone)
                    <a href="tel:{{ $job->ownerBranch->phone }}" class="btn btn-primary" style="min-height:48px">
                        Gọi {{ $job->ownerBranch->phone }}
                    </a>
                @endif
                @if ($job->ownerBranch->zalo)
                    <a href="https://zalo.me/{{ $job->ownerBranch->zalo }}" class="btn btn-outline-primary" style="min-height:48px" target="_blank" rel="noopener">
                        Nhắn Zalo
                    </a>
                @endif
            </div>
        @endif
    </div>
</article>
