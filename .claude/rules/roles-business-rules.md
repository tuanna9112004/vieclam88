---
paths:
  - "app/**/*.php"
  - "routes/**/*.php"
  - "tests/Feature/**/*.php"
---

# Nghiệp vụ cốt lõi

- Guest được ứng tuyển; public form không thu CCCD, ngân hàng, dân tộc, tôn giáo, tình trạng hôn nhân hoặc hồ sơ sức khỏe chi tiết.
- Chuẩn hóa contact nhưng giữ cả giá trị gốc và normalized value; không tự merge người chỉ vì trùng số.
- Application/lead phải lưu consent version, hash, time, IP và user agent.
- Staff Phase 1 xem toàn bộ application; được claim hồ sơ chưa gán. Admin được reassign. Mọi assignment có history.
- Job status: `draft`, `published`, `paused`, `closed`; chỉ `published`, chưa hết hạn, chưa deleted mới public.
- Một job là một đợt tuyển. Đợt mới phải duplicate job, không reopen job cũ.
- Job verification: cảnh báo sau 7 ngày, có thể pause sau 14 ngày; giá trị nằm trong settings, scheduler xử lý, mỗi lần có history.
- Candidate không thấy pipeline/notes nội bộ.

Chi tiết trạng thái, cột và transaction: `.claude/rules/data-model.md`, `docs/DATABASE-DICTIONARY.md`.
