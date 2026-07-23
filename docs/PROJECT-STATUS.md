# Project Status

## Phase / slice hiện tại

Phase 1 core (Company/Job/Application/Candidate CRUD + workflow, Dashboard, CSV export,
DB backup/restore, redesign Public+HR sidebar) DONE. Đang ở giai đoạn hoàn thiện UI +
dữ liệu hành chính thật trước khi tính go-live.

- Administrative Units: go-live blocker nguồn dữ liệu (ADR-070) đã resolve bằng **ADR-079** —
  import từ `provinces.open-api.vn` (v2) qua `php artisan administrative-units:import`, tái dùng
  `UpsertAdministrativeUnitAction`, không đổi schema/FK/CRUD hiện có.
- Redesign Public + HR sidebar: `layouts/public.blade.php`/`layouts/hr.blade.php`, job card dùng
  chung, multi-select chip filter (`components/multi-select.blade.php`).
- `resources/banner.png` (ảnh đối thủ) còn untracked, chưa quyết định giữ/xoá.
- **TASK 0.1 DONE**: baseline kỹ thuật trước migration Phase 2 (28 bảng/106 route/6 luồng/811 test
  + rollback plan) — `docs/refactor/00-CURRENT-BASELINE.md`, `docs/refactor/01-ROLLBACK-PLAN.md`.

## Quyết định quan trọng gần đây

- ADR-079: nguồn dữ liệu hành chính chính thức = `provinces.open-api.vn`, import qua console
  command (không gọi API lúc runtime), không tự động `is_active=false` bản ghi vắng mặt.
- **ADR-080**: công ty đã duyệt "cấu trúc lại" (PDF) làm baseline kiến trúc Phase 2 — role 3 cấp,
  `provinces`/`wards`, bỏ `company_locations`, cột mới trên `jobs`, bảng mới (`industries`,
  `employment_types`, `job_images`, `candidate_documents`, `activity_logs`). **Chưa migrate
  code/schema** — lộ trình 9 batch ở `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`.

## Verification gần nhất / Blocker

`php artisan test` PASS **811/817** (6 fail env `DatabaseBackup*`/`DatabaseRestoreTest*` do thiếu
binary `mariadb-dump`/`mariadb`, không phải regression — xem `docs/refactor/01-ROLLBACK-PLAN.md`).
Commit `97e97ae` (redesign) **chưa push** — cần user tự `git push origin main`. Thay đổi ADR-079 +
TASK 0.1 **chưa commit**.

## Bước tiếp theo

1. User tự `git push origin main` (commit `97e97ae`) và commit ADR-079 + TASK 0.1 (`docs/refactor/`).
2. Chạy `administrative-units:import` thật trên DB dev (chưa xác nhận lại sau lần bị chặn cURL SSL).
3. Trước Batch 1 thật: xác nhận môi trường có `mariadb-dump`/`mariadb` trên PATH (rollback plan
   mục 2). Sau đó redesign Dashboard HR; quyết định giữ/xoá `resources/banner.png`.
