@extends('layouts.public')

@php
    $effectiveStatus = $job->effectiveStatus();
    $isOpen = $job->isOpenForApplication();
    $primaryLocation = $job->jobLocations->firstWhere('is_primary', true)?->companyLocation
        ?? $job->jobLocations->first()?->companyLocation;
    $shiftNames = $job->jobWorkShifts->pluck('workShift.name')->filter()->implode(', ');
    $publicContact = $job->publicCompanyContact();

    $statusMessages = [
        'paused' => 'Tạm ngừng tuyển',
        'closed' => 'Đã ngừng tuyển',
        'expired' => 'Đã hết hạn tuyển',
    ];

    $genderLabels = ['male' => 'Nam', 'female' => 'Nữ', 'any' => 'Không yêu cầu'];
    $employmentTypeLabels = [
        'full_time' => 'Toàn thời gian',
        'part_time' => 'Bán thời gian',
        'seasonal' => 'Thời vụ',
        'temporary' => 'Tạm thời',
    ];

    $ageRange = null;
    if ($job->min_age && $job->max_age) {
        $ageRange = "{$job->min_age} - {$job->max_age} tuổi";
    } elseif ($job->min_age) {
        $ageRange = "Từ {$job->min_age} tuổi";
    } elseif ($job->max_age) {
        $ageRange = "Đến {$job->max_age} tuổi";
    }

    $metaDescription = \Illuminate\Support\Str::limit(
        trim(strip_tags($job->job_description ?? $job->salary_description ?? '')),
        155
    );
@endphp

@section('title', $job->title.' — '.($job->company?->name).' — '.config('app.name', 'vieclam88'))
@if ($metaDescription)
    @section('meta_description', $metaDescription)
@endif
@section('canonical', route('jobs.show', $job->slug))

@if ($isOpen)
    @push('head')
        @php
            $employmentTypeMap = [
                'full_time' => 'FULL_TIME',
                'part_time' => 'PART_TIME',
                'seasonal' => 'TEMPORARY',
                'temporary' => 'TEMPORARY',
            ];

            $jobPosting = [
                '@context' => 'https://schema.org/',
                '@type' => 'JobPosting',
                'title' => $job->title,
                'description' => $job->job_description ?: $job->title,
                'identifier' => [
                    '@type' => 'PropertyValue',
                    'name' => config('app.name', 'vieclam88'),
                    'value' => $job->code,
                ],
                'datePosted' => $job->published_at?->toIso8601String() ?? $job->created_at->toIso8601String(),
                'employmentType' => $employmentTypeMap[$job->employment_type->value] ?? 'OTHER',
                'hiringOrganization' => [
                    '@type' => 'Organization',
                    'name' => $job->company?->name,
                ],
                'jobLocation' => [
                    '@type' => 'Place',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => $primaryLocation?->address_detail,
                        'addressLocality' => $primaryLocation?->name,
                        'addressRegion' => $primaryLocation?->administrativeUnit?->name,
                        'addressCountry' => 'VN',
                    ],
                ],
            ];

            if ($job->expires_at) {
                $jobPosting['validThrough'] = $job->expires_at->toIso8601String();
            }

            if (in_array($job->salary_period, ['month', 'day', 'hour'], true) && ($job->salary_min || $job->salary_max)) {
                $jobPosting['baseSalary'] = [
                    '@type' => 'MonetaryAmount',
                    'currency' => $job->currency,
                    'value' => array_filter([
                        '@type' => 'QuantitativeValue',
                        'minValue' => $job->salary_min,
                        'maxValue' => $job->salary_max,
                        'unitText' => strtoupper($job->salary_period),
                    ], fn ($v) => $v !== null),
                ];
            }
        @endphp
        <script type="application/ld+json">{!! json_encode($jobPosting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endpush
@endif

@section('content')
<div class="container">
    <a href="{{ route('jobs.index') }}" class="d-inline-block mb-3 text-decoration-none">&larr; Việc làm đang tuyển</a>

    <div class="row g-4">
        <div class="col-lg-8">
            @if (! $isOpen)
                <div class="alert alert-warning" role="alert">
                    {{ $statusMessages[$effectiveStatus] ?? 'Tin tuyển dụng hiện không còn nhận hồ sơ' }} — vui lòng
                    liên hệ trực tiếp cơ sở bên dưới hoặc xem việc làm liên quan.
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                <h1 class="h3 mb-0">{{ $job->title }}</h1>
                @if ($job->is_urgent)
                    <span class="badge text-bg-danger text-nowrap">Tuyển gấp</span>
                @endif
            </div>
            <p class="text-secondary fs-5">{{ $job->company?->name }}</p>

            <p class="mb-2">
                @if ($primaryLocation)
                    <span aria-hidden="true">&#128205;</span>
                    {{ $primaryLocation->name }}@if ($primaryLocation->administrativeUnit)
                        , {{ $primaryLocation->administrativeUnit->name }}
                    @endif
                @endif
            </p>

            <p class="fs-4 fw-bold text-primary mb-4">{{ $job->formattedSalary() }}</p>

            <div class="row g-2 mb-4">
                @if (! empty($genderLabels[$job->gender_requirement]))
                    <div class="col-6 col-md-4">
                        <p class="job-meta-item mb-0">
                            <span class="job-meta-item__icon" aria-hidden="true">&#128100;</span>
                            <span>Giới tính: {{ $genderLabels[$job->gender_requirement] }}</span>
                        </p>
                    </div>
                @endif
                @if ($ageRange)
                    <div class="col-6 col-md-4">
                        <p class="job-meta-item mb-0">
                            <span class="job-meta-item__icon" aria-hidden="true">&#127874;</span>
                            <span>Độ tuổi: {{ $ageRange }}</span>
                        </p>
                    </div>
                @endif
                @if ($shiftNames)
                    <div class="col-6 col-md-4">
                        <p class="job-meta-item mb-0">
                            <span class="job-meta-item__icon" aria-hidden="true">&#128337;</span>
                            <span>Ca làm: {{ $shiftNames }}</span>
                        </p>
                    </div>
                @endif
                @if ($job->has_shuttle_bus)
                    <div class="col-6 col-md-4">
                        <p class="job-meta-item mb-0">
                            <span class="job-meta-item__icon" aria-hidden="true">&#128652;</span>
                            <span>Xe đưa đón</span>
                        </p>
                    </div>
                @endif
                @if ($job->has_accommodation)
                    <div class="col-6 col-md-4">
                        <p class="job-meta-item mb-0">
                            <span class="job-meta-item__icon" aria-hidden="true">&#127968;</span>
                            <span>Chỗ ở</span>
                        </p>
                    </div>
                @endif
                @if ($job->has_meal_support)
                    <div class="col-6 col-md-4">
                        <p class="job-meta-item mb-0">
                            <span class="job-meta-item__icon" aria-hidden="true">&#127860;</span>
                            <span>Hỗ trợ ăn ở</span>
                        </p>
                    </div>
                @endif
                @if (! empty($employmentTypeLabels[$job->employment_type->value]))
                    <div class="col-6 col-md-4">
                        <p class="job-meta-item mb-0">
                            <span class="job-meta-item__icon" aria-hidden="true">&#128188;</span>
                            <span>{{ $employmentTypeLabels[$job->employment_type->value] }}</span>
                        </p>
                    </div>
                @endif
            </div>

            @if ($job->job_description)
                <section class="pub-section">
                    <h2 class="h5">Mô tả công việc</h2>
                    <p class="mb-0" style="white-space: pre-line">{{ $job->job_description }}</p>
                </section>
            @endif

            @if ($job->requirements)
                <section class="pub-section">
                    <h2 class="h5">Yêu cầu</h2>
                    <p class="mb-0" style="white-space: pre-line">{{ $job->requirements }}</p>
                </section>
            @endif

            @if ($job->benefits)
                <section class="pub-section">
                    <h2 class="h5">Phúc lợi</h2>
                    <p class="mb-0" style="white-space: pre-line">{{ $job->benefits }}</p>
                </section>
            @endif

            @if ($job->application_documents)
                <section class="pub-section">
                    <h2 class="h5">Hồ sơ cần chuẩn bị</h2>
                    <p class="mb-0" style="white-space: pre-line">{{ $job->application_documents }}</p>
                </section>
            @endif

            @if ($primaryLocation)
                <section class="pub-section">
                    <h2 class="h5">Địa điểm làm việc</h2>
                    <p class="mb-0">
                        {{ $primaryLocation->name }}
                        @if ($primaryLocation->address_detail)
                            <br>{{ $primaryLocation->address_detail }}
                        @endif
                        @if ($primaryLocation->administrativeUnit)
                            <br>{{ $primaryLocation->administrativeUnit->name }}
                        @endif
                    </p>
                </section>
            @endif
        </div>

        <div class="col-lg-4">
            @if ($isOpen)
                <div class="card shadow-sm mb-4" id="apply-card" style="position: sticky; top: 1rem;">
                    <div class="card-body d-flex flex-column gap-2">
                        <button type="button" class="btn btn-primary btn-lg" style="min-height:48px" data-bs-toggle="collapse" data-bs-target="#apply-form" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}" aria-controls="apply-form">
                            Ứng tuyển ngay
                        </button>

                        <div class="collapse mt-2 @if ($errors->any()) show @endif" id="apply-form">
                            <form method="POST" action="{{ route('applications.store', $job->slug) }}" novalidate>
                                @csrf
                                <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
                                <div class="mb-2" style="position:absolute; left:-9999px" aria-hidden="true">
                                    <label for="website">Để trống trường này</label>
                                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                                </div>

                                <div class="mb-2">
                                    <label for="full_name" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('full_name') is-invalid @enderror" style="min-height:44px" id="full_name" name="full_name" value="{{ old('full_name') }}" placeholder="Nguyễn Văn A" required>
                                    @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="mb-2">
                                    <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" style="min-height:44px" id="phone" name="phone" value="{{ old('phone') }}" placeholder="09xxxxxxxx" required>
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="mb-2">
                                    <label for="date_of_birth" class="form-label">Ngày sinh</label>
                                    <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" style="min-height:44px" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}">
                                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <button type="button" class="btn btn-link px-0 mb-2" style="min-height:44px" data-bs-toggle="collapse" data-bs-target="#apply-extra" aria-expanded="false" aria-controls="apply-extra">
                                    Thông tin bổ sung
                                </button>
                                <div class="collapse @if (old('gender') || old('current_ward_id') || old('education_level') || old('experience_summary')) show @endif" id="apply-extra">
                                    <div class="mb-2">
                                        <label class="form-label d-block">Giới tính</label>
                                        @foreach (['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'] as $value => $label)
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input @error('gender') is-invalid @enderror" type="radio" name="gender" id="gender_{{ $value }}" value="{{ $value }}" {{ old('gender') === $value ? 'checked' : '' }}>
                                                <label class="form-check-label" for="gender_{{ $value }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                        @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>

                                    <x-province-ward-select
                                        :provinces="$applicantProvinces"
                                        :wards="$applicantWards"
                                        ward-field="current_ward_id"
                                        ward-label="Nơi ở hiện tại"
                                    />

                                    <div class="mb-2">
                                        <label for="education_level" class="form-label">Học vấn</label>
                                        <input type="text" class="form-control @error('education_level') is-invalid @enderror" style="min-height:44px" id="education_level" name="education_level" placeholder="VD: 12/12, Trung cấp..." value="{{ old('education_level') }}">
                                        @error('education_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="mb-2">
                                        <label for="experience_summary" class="form-label">Kinh nghiệm làm việc</label>
                                        <textarea class="form-control @error('experience_summary') is-invalid @enderror" id="experience_summary" name="experience_summary" rows="3" placeholder="Mô tả ngắn kinh nghiệm làm việc (nếu có)">{{ old('experience_summary') }}</textarea>
                                        @error('experience_summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" id="consent" name="consent" value="1" {{ old('consent') ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="consent">
                                        {{ \App\Support\ConsentNotice::currentText() }}
                                    </label>
                                    @error('consent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @error('submission_token')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>

                                <button type="submit" class="btn btn-primary w-100" style="min-height:48px" data-submit-once>Gửi hồ sơ ứng tuyển</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card shadow-sm mb-4" style="position: sticky; top: 1rem;">
                <div class="card-body d-flex flex-column gap-2">
                    <h2 class="h6 mb-1">Liên hệ</h2>
                    <div class="cta-group">
                        @if ($job->ownerBranch?->phone)
                            <a href="tel:{{ $job->ownerBranch->phone }}" class="btn btn-primary flex-grow-1">
                                Gọi {{ $job->ownerBranch->phone }}
                            </a>
                        @endif
                        @if ($job->ownerBranch?->zalo)
                            <a href="https://zalo.me/{{ $job->ownerBranch->zalo }}" class="btn btn-outline-primary flex-grow-1" target="_blank" rel="noopener">
                                Nhắn Zalo
                            </a>
                        @endif
                    </div>

                    @if ($publicContact)
                        <hr class="my-2">
                        <p class="mb-0 small text-secondary">Đầu mối công ty</p>
                        <p class="mb-0 fw-semibold">{{ $publicContact->name }}@if ($publicContact->position)
                                — {{ $publicContact->position }}
                            @endif
                        </p>
                        @if ($publicContact->phone)
                            <a href="tel:{{ $publicContact->phone }}" class="text-decoration-none">{{ $publicContact->phone }}</a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($relatedJobs->isNotEmpty())
        <section class="pub-section mt-5">
            <div class="pub-section__head">
                <h2 class="h4 pub-section__title">Việc làm liên quan</h2>
            </div>
            <div class="row g-3">
                @foreach ($relatedJobs as $relatedJob)
                    <div class="col-sm-6 col-lg-3">
                        <a href="{{ route('jobs.show', $relatedJob->slug) }}" class="job-card text-decoration-none text-dark d-block">
                            <div class="job-card__body">
                                <h3 class="h6 job-card__title">{{ $relatedJob->title }}</h3>
                                <p class="text-secondary small mb-1">{{ $relatedJob->company?->name }}</p>
                                <p class="fw-semibold text-primary mb-0">{{ $relatedJob->formattedSalary() }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>

@if ($isOpen || $job->ownerBranch?->phone || $job->ownerBranch?->zalo)
    <div class="job-sticky-cta-spacer" aria-hidden="true"></div>
    <div class="job-sticky-cta d-lg-none">
        <div class="container">
            <div class="cta-group">
                @if ($isOpen)
                    <a href="#apply-card" class="btn btn-primary flex-grow-1">Ứng tuyển ngay</a>
                @endif
                @if ($job->ownerBranch?->phone)
                    <a href="tel:{{ $job->ownerBranch->phone }}" class="btn btn-outline-primary" aria-label="Gọi {{ $job->ownerBranch->phone }}">
                        <span aria-hidden="true">&#128222;</span>
                    </a>
                @endif
                @if ($job->ownerBranch?->zalo)
                    <a href="https://zalo.me/{{ $job->ownerBranch->zalo }}" class="btn btn-outline-primary" target="_blank" rel="noopener" aria-label="Nhắn Zalo">
                        <span aria-hidden="true">&#128172;</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif
@endsection
