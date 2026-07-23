# Project Status

## Phase / slice hiện tại

Phase 1 core (Company/Job/Application/Candidate CRUD + workflow, Dashboard, CSV export,
DB backup/restore, redesign Public+HR sidebar) DONE. Đang tái cấu trúc theo
`docs/VIECLAM88_TASK_REGISTRY_V2.3.md` (chạy từng `TASK x.y` qua `/task-cycle`).

- **Phần 0 (khóa baseline) DONE** — TASK 0.1–0.3: 28 bảng/102 route/6 luồng nghiệp vụ; test baseline
  lặp lại được, 811/817 pass; skill/`.claude` trỏ đúng registry V2.3 —
  `docs/refactor/00-CURRENT-BASELINE.md`, `01-ROLLBACK-PLAN.md`, `02-TEST-BASELINE.md`.
- Administrative Units: go-live blocker (ADR-070) đã resolve bằng **ADR-079** — import từ
  `provinces.open-api.vn` qua `php artisan administrative-units:import`.
- `resources/banner.png` (ảnh đối thủ) còn untracked, chưa quyết định giữ/xoá.
- Dev test account (chỉ local): `admin.test@vieclam88.local`/`staff.test@vieclam88.local`, mật khẩu `Vieclam88Test2026`.
- Leftover chưa commit, không thuộc task đang chạy: `00-CURRENT-BASELINE.md` có hunk re-verify route
  106→102 (HEAD ghi sai `138308d`, thật `c4f5e23`) — dọn riêng.

## Quyết định quan trọng gần đây

- ADR-079: nguồn dữ liệu hành chính chính thức = `provinces.open-api.vn`, import qua console
  command (không gọi API lúc runtime), không tự động `is_active=false` bản ghi vắng mặt.
- **ADR-080**: công ty đã duyệt "cấu trúc lại" (PDF) làm baseline kiến trúc Phase 2 — role 3 cấp,
  `provinces`/`wards`, bỏ `company_locations`. **Chưa migrate code/schema** — lộ trình theo
  `docs/VIECLAM88_TASK_REGISTRY_V2.3.md`.

## Verification gần nhất / Blocker

`php artisan test` PASS **811/817** (guard 6/6, `DatabaseIntegrityTest` 7/7 riêng đều PASS),
`npm run build` PASS, hai script Claude config PASS 0 warning — chi tiết
`docs/refactor/02-TEST-BASELINE.md`. Blocker môi trường: `mariadb-dump`/`mariadb`/`mysql` **không
có** trên PATH máy dev này — bắt buộc xử lý trước Batch 1 migration Phase 2 thật.

## Bước tiếp theo

1. Cài `mariadb-dump`/`mariadb` vào PATH, chạy lại `--filter=DatabaseBackup` trước Batch 1.
2. Dọn riêng leftover `00-CURRENT-BASELINE.md` (không gộp vào task khác).
3. TASK tiếp theo theo registry: **TASK 1.1** — tạo bảng `provinces`/`wards` + command
   `locations:sync` (cần binary ở bước 1 trước khi migrate DB có dữ liệu thật).
