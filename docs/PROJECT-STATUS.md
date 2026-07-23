# Project Status

## Phase / slice hiện tại

Phase 1 core DONE. Tái cấu trúc theo `docs/VIECLAM88_TASK_REGISTRY_V2.3.md`:
Phần 1 (TASK 1.1–1.3) DONE; **TASK 2.1–2.2 DONE**.

- Role hiện hành: `super_admin`/`branch_admin`/`staff`; login/session và query quan trọng đã
  chặn branch thiếu/inactive/deleted, scope server-side theo cơ sở.
- **TASK 2.2:** `BranchSeeder` idempotent theo natural key `VP/PT/HB/BGBN`, đúng bốn tên chuẩn,
  không hardcode ID/ward và được gọi từ `DatabaseSeeder`.
- Re-seed chuẩn hóa code/name nhưng giữ trạng thái, CTA, ward và địa chỉ hiện có; canonical
  soft-deleted được restore nhưng không tự kích hoạt lại nếu đang inactive.
- Branch legacy không bị xóa/merge; mỗi lần seed xuất JSON/CSV (đã chống formula injection)
  vào `storage/app/reports` để rà soát duplicate/gần giống thủ công.
- TASK 1.3: FK ward nullable cho branches/companies/candidates, form province→ward và backfill
  branches/candidates đã hoàn tất.
- Finding theo dõi: lỗi cô lập test có sẵn để sót 1 dòng `administrative_units` ở vài tổ hợp.

## Quyết định quan trọng gần đây

- ADR-079: nguồn dữ liệu hành chính = `provinces.open-api.vn`.
- ADR-080: Batch địa chỉ TASK 1.1–1.3, role TASK 2.1 và branch seed TASK 2.2 DONE.
- Seed branch không tự đoán ward, không tự merge legacy và không mở lại branch inactive.

## Verification gần nhất / Blocker

Focused TASK 2.2 PASS **32/32** (139 assertions); full suite **863/869** (2536 assertions),
đúng 6 lỗi môi trường baseline; `npm run build`, Pint, diff-check, hai script Claude config và
reviewer đều PASS/APPROVE. Blocker môi trường không đổi: `mariadb-dump`/`mariadb`/`mysql`
chưa có trên PATH — bắt buộc trước migrate/backup/restore DB thật. Concurrent seed chưa stress
đa connection MariaDB, nhưng natural key có unique constraint và lookup được khóa hàng.

## Bước tiếp theo

**TASK 2.3 — Cứng hóa branch isolation cho ba role.**
