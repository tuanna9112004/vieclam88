<?php

namespace App\Http\Requests\Hr\IndustrialPark;

use App\Models\AdministrativeUnit;
use App\Models\IndustrialPark;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndustrialParkRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var IndustrialPark $industrialPark */
        $industrialPark = $this->route('industrialPark');

        return $this->user()->can('update', $industrialPark);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'administrative_unit_id' => ['required', Rule::exists(AdministrativeUnit::class, 'id')],
            'name' => ['required', 'string', 'max:150'],
            'official_name' => ['nullable', 'string', 'max:200'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Đơn vị hành chính hiện tại của KCN có thể đã inactive (thay đổi độc lập, ngoài tầm
     * kiểm soát của request này) — nếu bắt buộc active vô điều kiện, Admin sẽ không còn cách
     * nào tắt is_active của chính KCN đó (kẹt validation). Vì vậy: giữ nguyên đơn vị hiện tại
     * dù đã inactive chỉ được chấp nhận khi đồng thời tắt is_active; đổi sang đơn vị khác luôn
     * bắt buộc đơn vị đích đang active (không nới lỏng cho trường hợp chuyển đơn vị).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('administrative_unit_id')) {
                return;
            }

            /** @var IndustrialPark $industrialPark */
            $industrialPark = $this->route('industrialPark');
            $targetUnitId = (int) $this->input('administrative_unit_id');
            $isKeepingCurrentUnit = $targetUnitId === $industrialPark->administrative_unit_id;

            if ($isKeepingCurrentUnit) {
                $currentUnitIsActive = AdministrativeUnit::whereKey($targetUnitId)->value('is_active');

                if (! $currentUnitIsActive && $this->boolean('is_active')) {
                    $validator->errors()->add(
                        'administrative_unit_id',
                        'Đơn vị hành chính hiện tại đã ngừng hoạt động — chỉ có thể tắt hoạt động của khu công nghiệp, không thể giữ hoặc bật lại.'
                    );
                }

                return;
            }

            $targetUnitIsActive = AdministrativeUnit::whereKey($targetUnitId)->value('is_active');

            if (! $targetUnitIsActive) {
                $validator->errors()->add(
                    'administrative_unit_id',
                    'Không thể chuyển khu công nghiệp sang một đơn vị hành chính đã ngừng hoạt động.'
                );
            }
        });
    }
}
