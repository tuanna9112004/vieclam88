# Project Status

## Phase / slice hiện tại

**Giai đoạn 0 hoàn thành về plan/spec — Phase 1 Plan Baseline v1.0 đã đóng băng (ADR-057).**
**Không còn migration blocker nào.** Chỉ còn thiếu cài đặt môi trường code trước khi sang Giai
đoạn 1. Chưa có mã nguồn Laravel.

## Đã hoàn thành (vòng FINAL PLAN HARDENING)

- Bootstrap Sequence + Initial Admin (`app:create-admin`, ADR-050); Seeder Classification
  production-safe/demo-test (ADR-051) — sửa lỗi seeder Giai đoạn 1 từng gộp Branch mẫu chung
  câu với danh mục hệ thống thật.
- Validation tỉnh/KCN (ADR-052); quyền xóa/khôi phục Location/Contact về admin-only, thêm route
  restore còn thiếu (ADR-053) — sửa mâu thuẫn Route Map cho phép Staff xóa.
- Job Branch Transfer chỉ `draft`/`paused`, cấm `closed`/đã xóa (ADR-054) — sửa câu sai ở Route
  Map bản trước.
- **Enum Strategy (ADR-055):** 5 cột `[đề xuất]` chuyển DB `enum()` → `varchar` + PHP backed
  enum — **xóa bỏ migration blocker cuối cùng**.
- PII schema tối thiểu cho 6 cột `applications` đã chốt, tách khỏi quyết định retention (ADR-056).
- Tạo `docs/PHASE-1-SCOPE.md`, `docs/PHASE-2-BACKLOG.md` — đóng băng phạm vi (ADR-057).

## Verification

`python scripts/check-claude-config.py` → **0 warning** (không còn `[đề xuất]` trong
`docs/DATABASE-DICTIONARY.md` sau ADR-055).

## Blockers

- **Migration blocker:** không còn.
- **Go-live blockers (không chặn migration):** thời hạn lưu dữ liệu ứng viên, mức mask
  `submission_snapshot` khi anonymize (`docs/CORE-FLOWS.md` mục 7.4, 7.2).
- **Phase 2 decision (không chặn gì):** có bật `job_auto_pause_enabled` hay không (mục 1.3).
- Môi trường code (PHP 8.4, Composer, Node LTS, MariaDB 11.4) chưa cài/kiểm tra — blocker
  **duy nhất** còn lại trước khi chạy `composer create-project`.

## Bước tiếp theo

1. Cài PHP 8.4/Composer/Node LTS/MariaDB 11.4.
2. `composer create-project` Laravel 13.x — Giai đoạn 1 (viết migration theo
   `docs/DATABASE-DICTIONARY.md`), cần xác nhận trước khi chạy.
3. Song song (không chặn 1–2): công ty xác nhận go-live blockers (retention, mask) trước Giai
   đoạn 4.
