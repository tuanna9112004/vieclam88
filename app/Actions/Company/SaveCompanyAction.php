<?php

namespace App\Actions\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;

class SaveCompanyAction
{
    /**
     * Quick Create Contract (CORE-FLOWS.md mục 0.2, ADR-045): chỉ `name` bắt buộc — slug/
     * public_id server tự sinh, không phải input người dùng. Dữ liệu chưa biết luôn NULL, không
     * lưu placeholder văn bản.
     *
     * @param  array{name: string, short_name?: ?string, description?: ?string, industry?: ?string, website?: ?string}  $data
     */
    public function handle(array $data, User $actor, ?Company $company = null): Company
    {
        $data['slug'] = $this->uniqueSlug($data['name'], $company?->id);

        if ($company) {
            $data['updated_by'] = $actor->id;
            $company->update($data);

            return $company;
        }

        $data['public_id'] = (string) Str::ulid();
        $data['created_by'] = $actor->id;

        return Company::create($data);
    }

    /**
     * Số lượng công ty khác (chưa xóa) đang trùng tên đã chuẩn hóa (không phân biệt hoa/thường,
     * bỏ khoảng trắng thừa) — dùng để cảnh báo, không tự động merge hay chặn tạo/sửa.
     */
    public function countDuplicateNames(string $name, ?int $excludeId = null): int
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));

        return Company::query()
            ->whereRaw("LOWER(REGEXP_REPLACE(TRIM(name), '[[:space:]]+', ' ')) = ?", [$normalized])
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->count();
    }

    protected function uniqueSlug(string $name, ?int $ignoreId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (
            Company::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
