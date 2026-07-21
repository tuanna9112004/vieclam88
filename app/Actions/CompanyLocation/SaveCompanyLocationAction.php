<?php

namespace App\Actions\CompanyLocation;

use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\IndustrialPark;
use Illuminate\Validation\ValidationException;

class SaveCompanyLocationAction
{
    /**
     * Quick Create Contract (CORE-FLOWS.md mục 0.3, ADR-045): chỉ `name` bắt buộc —
     * `administrative_unit_id`/`address_detail` được phép thiếu, bổ sung sau. Không tin
     * FormRequest đã validate xong — tái xác nhận invariant tỉnh-KCN (ADR-052) ở đây vì đây là
     * lớp phòng vệ cuối trước khi ghi DB (cùng nguyên tắc với SaveIndustrialParkAction).
     *
     * @param  array{name: string, administrative_unit_id?: ?int, industrial_park_id?: ?int, address_detail?: ?string}  $data
     */
    public function handle(array $data, Company $company, ?CompanyLocation $location = null): CompanyLocation
    {
        $this->guardProvinceMatchesIndustrialPark($data);

        if ($location) {
            $location->update($data);

            return $location;
        }

        $data['company_id'] = $company->id;

        return CompanyLocation::create($data);
    }

    /**
     * @param  array{administrative_unit_id?: ?int, industrial_park_id?: ?int}  $data
     */
    protected function guardProvinceMatchesIndustrialPark(array $data): void
    {
        if (empty($data['industrial_park_id'])) {
            return;
        }

        $park = IndustrialPark::find($data['industrial_park_id']);

        if (! $park || (int) ($data['administrative_unit_id'] ?? 0) !== $park->administrative_unit_id) {
            throw ValidationException::withMessages([
                'administrative_unit_id' => 'Tỉnh/thành phải khớp với khu công nghiệp đã chọn.',
            ]);
        }
    }
}
