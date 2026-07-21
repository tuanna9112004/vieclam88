<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Sửa việc làm — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5" style="max-width: 560px;">
            <h1 class="h4 mb-4">Sửa việc làm</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('hr.jobs.update', $job) }}" novalidate>
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="title" class="form-label">Tên vị trí</label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $job->title) }}" required>
                </div>

                <div class="mb-3">
                    <label for="company_search" class="form-label">Công ty</label>
                    <input type="text" class="form-control mb-1" id="company_search" placeholder="Gõ để tìm công ty đã có...">
                    <select class="form-select" id="company_id" name="company_id" required>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected(old('company_id', $job->company_id) == $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1" id="toggle-new-company">+ Không tìm thấy? Tạo công ty mới</button>

                    <div id="new-company-panel" class="border rounded p-2 mt-2 d-none">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="new-company-name" placeholder="Tên công ty mới">
                            <button type="button" class="btn btn-outline-primary" id="create-company-btn">Tạo</button>
                        </div>
                        <div class="text-danger small mt-1 d-none" id="new-company-error"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="company_location_id" class="form-label">Địa điểm làm việc</label>
                    <select class="form-select" id="company_location_id" name="company_location_id" disabled>
                        <option value="">-- Đang tải --</option>
                    </select>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1 d-none" id="toggle-new-location">+ Không tìm thấy? Tạo địa điểm mới</button>

                    <div id="new-location-panel" class="border rounded p-2 mt-2 d-none">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="new-location-name" placeholder="Tên địa điểm mới">
                            <button type="button" class="btn btn-outline-primary" id="create-location-btn">Tạo</button>
                        </div>
                        <div class="text-danger small mt-1 d-none" id="new-location-error"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Lưu</button>
            </form>
        </div>

        @php
            $initialLocationId = old('company_location_id', $primaryLocation?->id);
        @endphp

        <script>
            (function () {
                const companySelect = document.getElementById('company_id');
                const locationSelect = document.getElementById('company_location_id');
                const toggleNewLocationBtn = document.getElementById('toggle-new-location');
                const csrfToken = document.querySelector('input[name="_token"]').value;
                const initialLocationId = @json($initialLocationId);

                function loadLocations(companyId, selectedId) {
                    if (!companyId) {
                        locationSelect.innerHTML = '<option value="">-- Chưa chọn công ty --</option>';
                        locationSelect.disabled = true;
                        toggleNewLocationBtn.classList.add('d-none');
                        return;
                    }

                    fetch(`/hr/cong-ty/${companyId}/dia-diem`, { headers: { Accept: 'application/json' } })
                        .then((r) => r.json())
                        .then((locations) => {
                            locationSelect.innerHTML = '<option value="">-- Chưa xác định --</option>';
                            locations.forEach((loc) => {
                                const opt = document.createElement('option');
                                opt.value = loc.id;
                                opt.textContent = loc.name;
                                if (selectedId && String(loc.id) === String(selectedId)) {
                                    opt.selected = true;
                                }
                                locationSelect.appendChild(opt);
                            });
                            locationSelect.disabled = false;
                            toggleNewLocationBtn.classList.remove('d-none');
                        });
                }

                companySelect.addEventListener('change', function () {
                    loadLocations(this.value, null);
                });

                document.getElementById('company_search').addEventListener('input', function () {
                    const term = this.value.toLowerCase();
                    Array.from(companySelect.options).forEach((opt) => {
                        if (!opt.value) return;
                        opt.hidden = !opt.textContent.toLowerCase().includes(term);
                    });
                });

                document.getElementById('toggle-new-company').addEventListener('click', function () {
                    document.getElementById('new-company-panel').classList.toggle('d-none');
                });

                toggleNewLocationBtn.addEventListener('click', function () {
                    document.getElementById('new-location-panel').classList.toggle('d-none');
                });

                document.getElementById('create-company-btn').addEventListener('click', function () {
                    const name = document.getElementById('new-company-name').value.trim();
                    const errorEl = document.getElementById('new-company-error');
                    errorEl.classList.add('d-none');
                    if (!name) return;

                    fetch('{{ route('hr.companies.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ name }),
                    })
                        .then((r) => {
                            if (!r.ok) throw new Error('failed');
                            return r.json();
                        })
                        .then((company) => {
                            const opt = document.createElement('option');
                            opt.value = company.id;
                            opt.textContent = company.name;
                            opt.selected = true;
                            companySelect.appendChild(opt);
                            document.getElementById('new-company-name').value = '';
                            document.getElementById('new-company-panel').classList.add('d-none');
                            loadLocations(company.id, null);
                        })
                        .catch(() => {
                            errorEl.textContent = 'Không tạo được công ty — kiểm tra lại tên.';
                            errorEl.classList.remove('d-none');
                        });
                });

                document.getElementById('create-location-btn').addEventListener('click', function () {
                    const name = document.getElementById('new-location-name').value.trim();
                    const errorEl = document.getElementById('new-location-error');
                    errorEl.classList.add('d-none');
                    if (!name || !companySelect.value) return;

                    fetch(`/hr/cong-ty/${companySelect.value}/dia-diem`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ name }),
                    })
                        .then((r) => {
                            if (!r.ok) throw new Error('failed');
                            return r.json();
                        })
                        .then((location) => {
                            const opt = document.createElement('option');
                            opt.value = location.id;
                            opt.textContent = location.name;
                            opt.selected = true;
                            locationSelect.appendChild(opt);
                            document.getElementById('new-location-name').value = '';
                            document.getElementById('new-location-panel').classList.add('d-none');
                        })
                        .catch(() => {
                            errorEl.textContent = 'Không tạo được địa điểm — kiểm tra lại tên.';
                            errorEl.classList.remove('d-none');
                        });
                });

                loadLocations(companySelect.value, initialLocationId);
            })();
        </script>
    </body>
</html>
