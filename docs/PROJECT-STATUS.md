# Project Status

## Phase / slice hiện tại

Phase 1 core (Company/Job/Application/Candidate CRUD + workflow, Dashboard, CSV export,
DB backup/restore, redesign Public+HR sidebar) DONE. Đang tái cấu trúc theo
`docs/VIECLAM88_TASK_REGISTRY_V2.3.md` (chạy từng `TASK x.y` qua `/task-cycle`), hiện ở Phần 1.

- **Phần 0 DONE** (TASK 0.1–0.3) + **TASK 1.1 DONE** (`provinces`/`wards`, `locations:sync`).
- **TASK 1.2 DONE**: `locations:backfill-administrative-units` map `administrative_units` →
  `provinces`/`wards` qua bảng chuyển tiếp `administrative_unit_mappings` (dry-run/batch/resume,
  report JSON+CSV, không cập nhật FK nghiệp vụ — đó là TASK 1.3).
- **Finding cần theo dõi**: lỗi cô lập test có sẵn từ trước (full suite để sót 1 dòng
  `administrative_units` qua `RefreshDatabase` ở vài tổ hợp test). Test TASK 1.2 đã tự vệ (scope
  theo `administrative_unit_id`); root cause chưa xác định, cần task riêng.
- `resources/banner.png` (ảnh đối thủ) còn untracked, chưa quyết định giữ/xoá.
- Dev test account (chỉ local): `admin.test@vieclam88.local`/`staff.test@vieclam88.local`, mật khẩu `Vieclam88Test2026`.
- Leftover chưa commit: `00-CURRENT-BASELINE.md` có hunk re-verify route 106→102 (HEAD ghi sai) — dọn riêng.

## Quyết định quan trọng gần đây

- ADR-079: nguồn dữ liệu hành chính chính thức = `provinces.open-api.vn`, không tự động
  `is_active=false` bản ghi vắng mặt.
- **ADR-080**: baseline kiến trúc Phase 2. Batch 1 (`provinces`/`wards`) đã migrate TASK 1.1, mapping
  cũ→mới đã có ở TASK 1.2; chưa nối FK nghiệp vụ nào (TASK 1.3) — lộ trình theo registry V2.3.

## Verification gần nhất / Blocker

`php artisan test` PASS **830/836** (+11 test `LocationsBackfillAdministrativeUnitsCommandTest`, 6
fail env không đổi), `npm run build` PASS, hai script Claude config PASS 0 warning, `pint --test`
sạch. Blocker môi trường không đổi: `mariadb-dump`/`mariadb`/`mysql` **không có** trên PATH máy dev
này — bắt buộc trước khi migrate DB dev/staging/production có dữ liệu thật.

## Bước tiếp theo

1. Cài `mariadb-dump`/`mariadb` vào PATH trước khi migrate DB có dữ liệu thật.
2. Dọn riêng leftover `00-CURRENT-BASELINE.md`; điều tra root cause test isolation ở trên.
3. TASK tiếp theo theo registry: **TASK 1.3** — thêm FK `ward_id` nullable (expand) vào
   branches/companies/candidates, backfill từ `administrative_unit_mappings`, giữ fallback cũ.
