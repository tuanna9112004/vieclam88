@php
    $primaryLocation = $job->jobLocations->first()?->companyLocation;
    $companyName = $job->company?->name ?? '';
    $initials = collect(preg_split('/\s+/', trim($companyName)))
        ->filter()
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->take(2)
        ->implode('') ?: '?';
    // Mau avatar chon theo hash id cong ty tu 1 bang mau co dinh — chi de phan biet
    // truc quan giua cac cong ty, khong phai du lieu that (khong co logo that).
    $avatarPalette = ['#fd7e14', '#0d6efd', '#20c997', '#6f42c1', '#d63384', '#198754'];
    $avatarColor = $avatarPalette[($job->company_id ?? 0) % count($avatarPalette)];
@endphp
<article class="job-card">
    <div class="job-card__body">
        <div class="d-flex gap-3 align-items-start mb-2">
            <div
                class="job-card__avatar"
                aria-hidden="true"
                style="color: {{ $avatarColor }}; border-color: {{ $avatarColor }}55; background-color: {{ $avatarColor }}1a;"
            >
                {{ $initials }}
            </div>
            <div class="flex-grow-1" style="min-width: 0">
                <h3 class="h6 job-card__title mb-1">
                    <a href="{{ route('jobs.show', $job->slug) }}" class="text-decoration-none text-dark stretched-link">
                        {{ $job->title }}
                    </a>
                </h3>
                <p class="job-card__company mb-1">{{ $companyName }}</p>
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
            </div>
            @if ($job->is_urgent)
                <span class="badge text-bg-danger text-nowrap">Tuyển gấp</span>
            @endif
        </div>

        @if ($job->has_shuttle_bus || $job->has_accommodation)
            <div class="d-flex flex-wrap gap-2 mb-2">
                @if ($job->has_shuttle_bus)
                    <span class="badge text-bg-light border">Xe đưa đón</span>
                @endif
                @if ($job->has_accommodation)
                    <span class="badge text-bg-light border">Chỗ ở</span>
                @endif
            </div>
        @endif

        <div class="job-card__spacer"></div>

        <div class="job-card__salary-bar">
            <span aria-hidden="true">&#128179;</span>
            {{ $job->formattedSalary() }}
        </div>
    </div>
</article>
