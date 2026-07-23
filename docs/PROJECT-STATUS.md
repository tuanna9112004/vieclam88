# Project Status

## Phase / slice hiện tại

Phase 1 core DONE. Tái cấu trúc theo `docs/VIECLAM88_TASK_REGISTRY_V2.3.md`:
Phần 1 (TASK 1.1–1.3, nền tảng provinces/wards) DONE; **TASK 2.1 DONE**.

- Role hiện hành: `super_admin`/`branch_admin`/`staff`; migration backfill `admin→super_admin`,
  rollback fail-closed khi còn Branch Admin; alias `isAdmin()` deprecated giữ một release.
- Super Admin toàn hệ thống; Branch Admin quản lý Staff/Branch và dữ liệu nghiệp vụ đúng cơ sở;
  Staff không quản lý user/branch. Login/session chặn role có branch thiếu/inactive/deleted.
- Dashboard, CSV, Job index và policy/query liên quan đã scope server-side; tạo/chuyển user khóa
  hàng Branch trong transaction để không tạo tài khoản trỏ cơ sở inactive/deleted.
- TASK 1.3: FK ward nullable cho branches/companies/candidates, form province→ward, backfill
  branches/candidates và UI administrative-units read-only.
- Finding cần theo dõi: lỗi cô lập test có sẵn để sót 1 dòng `administrative_units` ở vài tổ hợp.
- Leftover ngoài TASK 2.1, chưa commit: DemoSeeder no-op + integrity test; hunk re-verify
  `00-CURRENT-BASELINE.md`; `resources/banner.png` untracked.

## Quyết định quan trọng gần đây

- ADR-079: nguồn dữ liệu hành chính = `provinces.open-api.vn`.
- ADR-080: Batch địa chỉ TASK 1.1–1.3 DONE; Batch role TASK 2.1 DONE.
- Rollback TASK 2.1 phải chuyển hết `branch_admin` về `staff` có chủ đích trước khi chạy `down`.

## Verification gần nhất / Blocker

Focused TASK 2.1 PASS **161/161** (450 assertions); full suite **858/864** (2504 assertions),
đúng 6 lỗi môi trường baseline; `npm run build`, Pint, route inspection, migration `--pretend`,
hai script Claude config và reviewer đều PASS/APPROVE. Blocker môi trường không đổi:
`mariadb-dump`/`mariadb`/`mysql` chưa có trên PATH — bắt buộc trước migrate/backup/restore DB thật.
Concurrency locking chưa stress đa connection MariaDB trên máy này.

## Bước tiếp theo

**TASK 2.2 — Chuẩn hóa branch và seed 4 cơ sở.**
