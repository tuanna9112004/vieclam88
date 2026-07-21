# Project Status

## Phase / slice hiện tại

**Giai đoạn 1-3 DONE. Giai đoạn 4 (Company/Job) đang thực hiện.**

- `companies`/`company_locations`/`company_contacts` (`09f5278`/`bd0a823`/`db64071`): schema +
  Admin CRUD + Quick Create + policy — DONE.
- `jobs`+`job_locations` (`6d0f5ae`): Job Draft (index/create/store/edit/update, chưa publish/
  pause/close/verify/transfer-branch/destroy/restore) + Job Branch Contract + Quick Create
  Company/Location trong form Job (AJAX, không rời màn hình) — DONE. Đã push tới `336c078`.

## Quyết định quan trọng (và lý do)

- Tách nhỏ hơn ROADMAP "Nhóm": mỗi bảng làm riêng `/db-task`→`/implement` — giảm rủi ro.
- `company_contacts.status` = `varchar`+backed enum (ADR-055, không DB `enum()`); `is_primary`
  enforce ở Action, không DB constraint (ADR-064, CRUD nội bộ ít đồng thời).
- Job Draft chỉ bắt buộc title/company_id/owner_branch_id/created_by; Staff tự gán
  `owner_branch_id` server-side, Admin bắt buộc chọn, `update` không bao giờ đổi cột này.
- `job_locations` xây ngoài kế hoạch — cần để "Location lọc theo Company" có dữ liệu thật.
- Quick Create tái dùng `Company(Location)Controller` có sẵn (nhánh JSON khi `wantsJson()`),
  không tạo route/Action riêng — tránh duplicate logic. Đã dừng 3 lần khi thiếu dependency.

## Vá kèm phát hiện trong lúc làm

`Controller.php` thiếu `AuthorizesRequests` (500 tại industrial-parks); `Store/UpdateStaffRequest`
thiếu `withoutTrashed()` trên `Rule::exists(Branch)`; guard "không xóa Location khi Job đang
dùng" (ADR-045) bổ sung khi `job_locations` sẵn sàng.

## Verification gần nhất / Blockers

`php artisan test` PASS 276/276 (tại `6d0f5ae`); `composer validate`/`npm run build`/`git diff
--check` PASS. Không blocker. `CLAUDE.md` còn 1 dòng chưa commit (giữ nguyên, đã xác nhận).

## Bước tiếp theo

1. **NEXT:** Job Publish Predicate (ADR-060) + `job_work_shifts`/`job_verifications`/
   `job_status_histories`/`job_branch_histories`.
2. `work_shifts`/`recruitment_sources`/`settings` (Nhóm 2) — cần trước khi gắn ca vào Job.
