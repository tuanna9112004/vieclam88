# Project Status

## Phase / slice hiện tại

Phase 1 core (Company/Job/Application/Candidate CRUD + workflow, Dashboard, CSV export,
DB backup/restore, redesign Public+HR sidebar) DONE. Tái cấu trúc theo
`docs/VIECLAM88_TASK_REGISTRY_V2.3.md` — **Phần 1 (nền tảng địa chỉ provinces/wards) DONE**
(TASK 1.1–1.3), chuyển sang Phần 2 (vai trò/branch/phân quyền).

- **TASK 1.3 DONE**: `branches.ward_id`/`companies.headquarters_ward_id`/
  `candidates.current_ward_id` (nullable). Form branch + ứng tuyển công khai chuyển province→ward
  (`<x-province-ward-select>`), backfill `locations:backfill-ward-fk` (branches/candidates; company
  bỏ qua — thuộc TASK 5.2). Đọc ưu tiên ward, fallback cũ. UI `hr.administrative-units.*` read-only.
- Phần 0 + TASK 1.1 (`provinces`/`wards`) + TASK 1.2 (`administrative_unit_mappings`) DONE.
- **Finding cần theo dõi**: lỗi cô lập test có sẵn (full suite để sót 1 dòng `administrative_units`
  qua `RefreshDatabase` ở vài tổ hợp test) — chưa rõ root cause, cần task riêng.
- `resources/banner.png` (ảnh đối thủ) còn untracked, chưa quyết định giữ/xoá.
- Dev test account (chỉ local): `admin.test@vieclam88.local`/`staff.test@vieclam88.local`, mật khẩu `Vieclam88Test2026`.
- Leftover chưa commit: `00-CURRENT-BASELINE.md` có hunk re-verify route 106→102 (HEAD ghi sai) — dọn riêng.

## Quyết định quan trọng gần đây

- ADR-079: nguồn dữ liệu hành chính = `provinces.open-api.vn`, import qua console command.
- **ADR-080**: Phần 1 (Batch 1, provinces/wards + FK ward branches/candidates/companies) DONE
  TASK 1.1–1.3. `companies.headquarters_ward_id` chỉ có cột, backfill/form thuộc TASK 5.1/5.2 —
  không tạo lại cột. `jobs.work_ward_id` chưa đụng (Task 6).

## Verification gần nhất / Blocker

`php artisan test` PASS **834/840** (+4 test `LocationsBackfillWardFkCommandTest` net, 6 fail env
không đổi), `npm run build` PASS, hai script Claude config PASS 0 warning, `pint --test` sạch trên
file đã sửa. Blocker môi trường không đổi: `mariadb-dump`/`mariadb`/`mysql` **không có** trên PATH
máy dev này — bắt buộc trước khi migrate DB dev/staging/production có dữ liệu thật.

## Bước tiếp theo

1. Cài `mariadb-dump`/`mariadb` vào PATH trước khi migrate DB có dữ liệu thật.
2. Dọn riêng leftover `00-CURRENT-BASELINE.md`; điều tra root cause test isolation ở trên.
3. TASK tiếp theo: **TASK 2.1** — mở rộng role thành ba cấp `super_admin/branch_admin/staff`.
