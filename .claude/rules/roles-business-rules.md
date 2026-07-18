---
paths:
  - "app/**/*.php"
  - "routes/**/*.php"
  - "tests/Feature/**/*.php"
---

# Nghiệp vụ cốt lõi

6 luồng nghiệp vụ cốt lõi (nguồn sự thật): `docs/CORE-FLOWS.md`. Rule này chỉ tóm tắt quy tắc
áp dụng khi viết code, không lặp lại chi tiết luồng.

- Guest được ứng tuyển; public form không thu CCCD, ngân hàng, dân tộc, tôn giáo, tình trạng hôn nhân hoặc hồ sơ sức khỏe chi tiết. Phase 1 không có form "yêu cầu tư vấn"/Lead dưới bất kỳ hình thức nào (ADR-021).
- Chuẩn hóa contact nhưng giữ cả giá trị gốc và normalized value; không tự merge người chỉ vì trùng số — khớp mạnh yêu cầu tên khớp chính xác sau chuẩn hóa (duplicate contract 3 trường hợp: `docs/CORE-FLOWS.md` mục 6.2). `applications.submission_token` chống double-submit cùng 1 lần gửi, tách biệt với unique candidate+job.
- Application phải lưu consent version, hash, time, IP và user agent.
- Staff chỉ xem/xử lý được Application thuộc đúng `owner_branch_id` của mình (truy cập URL cơ sở khác → 403); admin xem toàn bộ cơ sở (ADR-020). Staff có thể xem (read-only) Job đang hoạt động của cơ sở khác nhưng chỉ sửa/publish Job cơ sở mình. **Không có claim/assign trong Phase 1** — bất kỳ staff nào cùng cơ sở đều xử lý được mọi Application của cơ sở đó, không cần "nhận xử lý" hay được gán (ADR-021). Admin được chuyển cơ sở; mọi lần chuyển đều có history riêng (`application_branch_histories`).
- `applications.owner_branch_id` copy từ `jobs.owner_branch_id` lúc tạo, không suy ra động; đổi cơ sở là ngoại lệ có kiểm soát qua `application_branch_histories`, không tạo Application mới (ADR-016).
- Job status: `draft`, `published`, `paused`, `closed`; chỉ `published`, chưa hết hạn, chưa deleted mới public. Chỉ 5 transition hợp lệ: `draft→published`, `published→paused`, `paused→published`, `published→closed`, `paused→closed` (`docs/CORE-FLOWS.md` mục 1). Job bắt buộc có `owner_branch_id` với cơ sở `active` và có `phone`/`zalo` hợp lệ trước khi publish. CTA Gọi/Zalo luôn dùng contact cơ sở, `company_contacts` không thay thế (ADR-023).
- Một job là một đợt tuyển. Đợt mới phải duplicate job, không tái sử dụng job đã `closed` cho đợt mới (khác với `paused → published`, vốn chỉ tạm dừng/tiếp tục trong cùng đợt).
- Job verification: cảnh báo sau 7 ngày, có thể pause sau 14 ngày; giá trị nằm trong settings, scheduler xử lý, mỗi lần có history.
- Đổi `applications.stage` phải theo đúng transition matrix (`docs/CORE-FLOWS.md` mục 5.1, gồm `closed → new` mở lại có kiểm soát), qua `ChangeApplicationStageAction`, không sửa cột trực tiếp từ controller. Contact Result dùng đúng enum chính thức (mục 5.2).
- Candidate không thấy pipeline/notes nội bộ.

Chi tiết trạng thái, cột và transaction: `.claude/rules/data-model.md`, `docs/DATABASE-DICTIONARY.md`.
