---
paths:
  - "app/**/*.php"
  - "routes/**/*.php"
  - "tests/Feature/**/*.php"
---

# Nghiệp vụ cốt lõi

6 luồng nghiệp vụ cốt lõi (nguồn sự thật): `docs/CORE-FLOWS.md`. Rule này chỉ tóm tắt quy tắc
áp dụng khi viết code, không lặp lại chi tiết luồng.

- Guest được ứng tuyển; public form không thu CCCD, ngân hàng, dân tộc, tôn giáo, tình trạng hôn nhân hoặc hồ sơ sức khỏe chi tiết.
- Chuẩn hóa contact nhưng giữ cả giá trị gốc và normalized value; không tự merge người chỉ vì trùng số (duplicate contract 3 trường hợp: `docs/CORE-FLOWS.md` mục 6.2).
- Application phải lưu consent version, hash, time, IP và user agent. `lead_requests` cũng lưu consent nhưng **không** có cơ chế chuyển đổi thành candidate/application trong Phase 1 (ADR-018).
- Staff chỉ xem/xử lý được Application thuộc đúng `owner_branch_id` của mình (truy cập URL cơ sở khác → 403); admin xem toàn bộ cơ sở (ADR-020). Staff được claim hồ sơ chưa gán trong cơ sở mình; không bắt buộc phải claim mới xử lý được. Admin được reassign/chuyển cơ sở. Mọi assignment và mọi chuyển cơ sở đều có history riêng (`application_assignment_histories`, `application_branch_histories`).
- `applications.owner_branch_id` copy từ `jobs.owner_branch_id` lúc tạo, không suy ra động; đổi cơ sở là ngoại lệ có kiểm soát qua `application_branch_histories`, không tạo Application mới (ADR-016).
- Job status: `draft`, `published`, `paused`, `closed`; chỉ `published`, chưa hết hạn, chưa deleted mới public. Job bắt buộc có `owner_branch_id` và contact công khai hợp lệ trước khi publish.
- Một job là một đợt tuyển. Đợt mới phải duplicate job, không reopen job cũ.
- Job verification: cảnh báo sau 7 ngày, có thể pause sau 14 ngày; giá trị nằm trong settings, scheduler xử lý, mỗi lần có history.
- Đổi `applications.stage` phải theo đúng transition matrix (`docs/CORE-FLOWS.md` mục 5.1), qua `ChangeApplicationStageAction`, không sửa cột trực tiếp từ controller.
- Candidate không thấy pipeline/notes nội bộ.

Chi tiết trạng thái, cột và transaction: `.claude/rules/data-model.md`, `docs/DATABASE-DICTIONARY.md`.
