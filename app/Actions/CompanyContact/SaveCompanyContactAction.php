<?php

namespace App\Actions\CompanyContact;

use App\Models\Company;
use App\Models\CompanyContact;
use Illuminate\Support\Facades\DB;

class SaveCompanyContactAction
{
    /**
     * ADR-064 điểm 3: một Company tối đa 1 primary contact đang `status=active`. Đặt
     * `is_primary=true` cho 1 contact tự động bỏ `is_primary` của contact khác đang là primary
     * (cùng company), trong cùng 1 transaction — khóa các bản ghi liên quan để tránh 2 request
     * đồng thời cùng đặt primary tạo ra 2 bản ghi primary cùng lúc.
     *
     * @param  array{name: string, position?: ?string, phone?: ?string, zalo?: ?string, email?: ?string, is_primary?: bool, is_public?: bool, status?: string}  $data
     */
    public function handle(array $data, Company $company, ?CompanyContact $contact = null): CompanyContact
    {
        return DB::transaction(function () use ($data, $company, $contact) {
            if ($contact) {
                $contact = CompanyContact::whereKey($contact->id)->lockForUpdate()->firstOrFail();
            }

            if ($data['is_primary'] ?? false) {
                CompanyContact::where('company_id', $company->id)
                    ->where('is_primary', true)
                    ->when($contact, fn ($query) => $query->whereKeyNot($contact->id))
                    ->lockForUpdate()
                    ->update(['is_primary' => false]);
            }

            if ($contact) {
                $contact->update($data);

                return $contact;
            }

            $data['company_id'] = $company->id;

            return CompanyContact::create($data);
        });
    }
}
