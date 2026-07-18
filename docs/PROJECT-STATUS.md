# Project Status

## Phase / slice hiện tại

Giai đoạn 0 (chốt plan/spec) — vòng siết phạm vi lần 2. Chưa có mã nguồn Laravel.

## Đã hoàn thành

- Siết phạm vi Phase 1 (ADR-021): bỏ hẳn Lead (`lead_requests`, form tư vấn), bỏ
  assignment/claim (`application_assignment_histories`, `applications.assigned_to`), bỏ
  Favorites khỏi database. 28 → 25 bảng, renumber `docs/DATABASE-DICTIONARY.md`.
- Idempotency contract `applications.submission_token` (ADR-022); CTA Gọi/Zalo luôn ưu tiên
  contact cơ sở, không dùng `company_contacts` thay thế (ADR-023).
- Chốt Application transition matrix mở rộng (`closed → new` có kiểm soát) + Contact Result
  enum chính thức 11 giá trị, đồng nhất CORE-FLOWS/dictionary (ADR-024). Chốt Job transition
  matrix tường minh (5 transition) + quy tắc đổi lịch appointment = tạo mới (ADR-025).
- Duplicate contract: case A đổi thành khớp tên chính xác (bỏ ngưỡng tương đồng); merge do
  admin chọn thủ công (ADR-026).
- Thêm khung chính sách dữ liệu cá nhân tối thiểu vào `docs/CORE-FLOWS.md` mục 7 (ADR-027);
  thời hạn lưu + anonymize snapshot vẫn **[CẦN CHỐT]**.
- Thêm bảng phân lớp constraint "DB bảo vệ vs Service bảo vệ" trong `DATABASE-DICTIONARY.md`.
- Đồng bộ toàn bộ: CORE-FLOWS, ERD, DATABASE-DICTIONARY, ROUTE-MAP, ACCEPTANCE-CRITERIA,
  ROADMAP, DECISIONS (ADR-021..027), CLAUDE.md, rules liên quan (data-model, roles-business,
  hr-admin, public-site, scope-standards, security-seo-testing). Rà soát chéo: không còn
  Lead/assignment/favorites nào được yêu cầu ở tài liệu Phase 1.

## Verification

`python scripts/check-claude-config.py` → xem kết quả lần chạy gần nhất bên dưới trong phiên
làm việc; cảnh báo enum **[đề xuất]** (5 mục) là cảnh báo có chủ đích, chưa chốt.

## Blockers

- 3 mục **[CẦN CHỐT]** ở `docs/CORE-FLOWS.md` mục 8: thời hạn lưu dữ liệu ứng viên, anonymize
  `submission_snapshot`/`job_snapshot` hay không, 5 enum **[đề xuất]** còn tồn đọng.
- Môi trường code (PHP 8.4, Composer, Node LTS, MariaDB) chưa cài/kiểm tra.

## Bước tiếp theo

1. Công ty xác nhận 3 mục **[CẦN CHỐT]** ở `docs/CORE-FLOWS.md` mục 8.
2. Cài PHP 8.4/Composer/Node/MariaDB.
3. Sau khi (1) xong: `composer create-project` Laravel 13.x — Giai đoạn 1, cần xác nhận trước
   khi chạy.
