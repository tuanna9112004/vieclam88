# Project Status

## Phase / slice hiện tại

Phase 1 core (Company/Job/Application/Candidate CRUD + workflow, Dashboard, CSV export,
DB backup/restore, redesign Public+HR sidebar) DONE. Đang tái cấu trúc theo
`docs/VIECLAM88_TASK_REGISTRY_V2.3.md` (chạy từng `TASK x.y` qua `/task-cycle`), hiện ở Phần 1.

- **Phần 0 (khóa baseline) DONE** — TASK 0.1–0.3: 28 bảng/102 route/6 luồng; test baseline lặp lại
  811/817 pass; skill/`.claude` trỏ đúng registry V2.3.
- **TASK 1.1 DONE**: migration `provinces`/`wards` (additive, không đụng `administrative_units`),
  command `locations:sync` tái dùng chung service fetch/normalize với `administrative-units:import`.
- Administrative Units: go-live blocker (ADR-070) đã resolve bằng **ADR-079** — import từ
  `provinces.open-api.vn` qua `php artisan administrative-units:import`.
- `resources/banner.png` (ảnh đối thủ) còn untracked, chưa quyết định giữ/xoá.
- Dev test account (chỉ local): `admin.test@vieclam88.local`/`staff.test@vieclam88.local`, mật khẩu `Vieclam88Test2026`.
- Leftover chưa commit: `00-CURRENT-BASELINE.md` có hunk re-verify route 106→102 (HEAD ghi sai) — dọn riêng.

## Quyết định quan trọng gần đây

- ADR-079: nguồn dữ liệu hành chính chính thức = `provinces.open-api.vn`, import qua console
  command (không gọi API lúc runtime), không tự động `is_active=false` bản ghi vắng mặt.
- **ADR-080**: baseline kiến trúc Phase 2 — role 3 cấp, `provinces`/`wards`, bỏ
  `company_locations`. Batch 1 (`provinces`/`wards`) **đã migrate ở TASK 1.1**, chưa nối vào luồng
  nghiệp vụ Phase 1 nào; các batch còn lại chưa migrate — lộ trình theo registry V2.3.

## Verification gần nhất / Blocker

`php artisan test` PASS **819/825** (+8 test `LocationsSyncCommandTest`, 6 fail env không đổi),
`npm run build` PASS, hai script Claude config PASS 0 warning, `pint --test` sạch. Blocker môi
trường không đổi: `mariadb-dump`/`mariadb`/`mysql` **không có** trên PATH máy dev này — bắt buộc xử
lý trước khi migrate DB dev/staging/production có dữ liệu thật.

## Bước tiếp theo

1. Cài `mariadb-dump`/`mariadb` vào PATH trước khi migrate DB có dữ liệu thật (dev/staging).
2. Dọn riêng leftover `00-CURRENT-BASELINE.md` (không gộp vào task khác).
3. TASK tiếp theo theo registry: **TASK 1.2** — backfill `administrative_units` → `provinces`/
   `wards` mới (dry-run + report, không đoán dữ liệu ambiguous).
