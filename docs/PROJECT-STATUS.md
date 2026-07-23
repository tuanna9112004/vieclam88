# Project Status

## Phase / slice hiện tại

Phase 1 core (Company/Job/Application/Candidate CRUD + workflow, Dashboard, CSV export,
DB backup/restore, redesign Public+HR sidebar) DONE. Đang tái cấu trúc theo
`docs/VIECLAM88_TASK_REGISTRY_V2.3.md` (chạy từng `TASK x.y` qua `/task-cycle`).

- Administrative Units: go-live blocker nguồn dữ liệu (ADR-070) đã resolve bằng **ADR-079** —
  import từ `provinces.open-api.vn` (v2) qua `php artisan administrative-units:import`.
- `resources/banner.png` (ảnh đối thủ) còn untracked, chưa quyết định giữ/xoá.
- Dev test account (chỉ local): `admin.test@vieclam88.local`/`staff.test@vieclam88.local`, mật khẩu `Vieclam88Test2026`.
- **TASK 0.1 DONE**: baseline kỹ thuật trước migration Phase 2 (28 bảng/102 route/6 luồng/811
  test) — `docs/refactor/00-CURRENT-BASELINE.md`, `docs/refactor/01-ROLLBACK-PLAN.md`.
- **TASK 0.2 DONE**: CLAUDE.md/AGENTS.md/`.claude` trỏ `VIECLAM88_TASK_REGISTRY_V2.3.md` làm nguồn
  KEY/GATE/DONE/NEXT (`TASK-INDEX.md`/`tasks/` nay chỉ lịch sử TASK 0.1); sửa checker phân biệt
  bảng hiện tại/target Phase 2; bổ sung rule private upload/download + backfill idempotent.

## Quyết định quan trọng gần đây

- ADR-079: nguồn dữ liệu hành chính chính thức = `provinces.open-api.vn`, import qua console
  command (không gọi API lúc runtime), không tự động `is_active=false` bản ghi vắng mặt.
- **ADR-080**: công ty đã duyệt "cấu trúc lại" (PDF) làm baseline kiến trúc Phase 2 — role 3 cấp,
  `provinces`/`wards`, bỏ `company_locations`, cột mới trên `jobs`, bảng mới target (batch 1–6).
  **Chưa migrate code/schema** — lộ trình theo `docs/VIECLAM88_TASK_REGISTRY_V2.3.md`.

## Verification gần nhất / Blocker

`php artisan test` PASS **811/817** (6 fail env `DatabaseBackup*`/`DatabaseRestoreTest*` do thiếu
binary `mariadb-dump`/`mariadb`, không phải regression). `check-claude-config.py` +
`check-claude-skills.py` PASS 0 warning. Git: local đồng bộ `origin/main` tới `138308d`; thay đổi
TASK 0.2 đang chờ commit.

## Bước tiếp theo

1. Commit + push thay đổi TASK 0.2 (sau verify/review PASS).
2. Chạy `administrative-units:import` thật trên DB dev (chưa xác nhận lại sau lần bị chặn cURL SSL).
3. TASK tiếp theo theo registry: **TASK 0.3** — chốt baseline test có thể lặp lại (cần xác nhận
   `mariadb-dump`/`mariadb` trên PATH trước).
