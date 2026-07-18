# Project Status

## Phase / slice hiện tại

Giai đoạn 0 (chốt nghiệp vụ + database) — chưa có mã nguồn Laravel.

## Đã hoàn thành

- `docs/CORE-FLOWS.md` (mới): nguồn sự thật 6 luồng cốt lõi, transition matrix, duplicate
  contract (case A/B/C + merge conflict), chuyển cơ sở, danh sách **[CẦN CHỐT]**.
- Thêm `branches` (cơ sở nội bộ) + `owner_branch_id` (jobs/applications) + `users.branch_id`;
  scope quyền staff theo cơ sở (ADR-015, 016, 020). Dictionary nay 28 bảng (+
  `application_branch_histories`, `application_appointments`, cột duplicate-review — ADR-017).
- Dời cơ chế chuyển `lead_requests → applications` sang Phase 2 (ADR-018).
- Đồng bộ toàn bộ: ERD, DATABASE-DICTIONARY, ROUTE-MAP, ACCEPTANCE-CRITERIA, DECISIONS
  (ADR-015..020), CLAUDE.md, CONTEXT-MAP, ROADMAP (viết lại theo Giai đoạn 0–4 + Phase 2), rules
  liên quan. Rà soát chéo: không còn mâu thuẫn "staff xem toàn bộ", "25 bảng", lead-convert.

## Verification

`python scripts/check-claude-config.py` → `OK: Claude configuration passed with 1 warning(s)`
(cảnh báo enum **[đề xuất]** chưa chốt, không đổi so với trước).

## Blockers

- 7 mục **[CẦN CHỐT]** ở `docs/CORE-FLOWS.md` mục 7 (ngưỡng khớp tên, nhóm contact result mở
  khóa `consulted`, mở lại `closed`, tiêu chí merge, phạm vi xem Job của staff, scope cơ sở
  cho lead, + 5 enum đề xuất cũ) — chưa migration được tới khi xác nhận.
- Chính sách dữ liệu cá nhân (thời hạn lưu, quyền xóa/ẩn danh) chưa có tài liệu riêng.
- Môi trường code (PHP 8.4, Composer, Node LTS, MariaDB) chưa cài/kiểm tra.

## Bước tiếp theo

1. Công ty xác nhận danh sách **[CẦN CHỐT]** ở `docs/CORE-FLOWS.md` mục 7.
2. Cài PHP 8.4/Composer/Node/MariaDB.
3. Sau khi (1) xong: `composer create-project` Laravel 13.x — Giai đoạn 1, cần xác nhận trước
   khi chạy.
