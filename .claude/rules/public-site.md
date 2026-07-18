---
paths:
  - "app/Http/Controllers/Public/**/*.php"
  - "app/Http/Requests/Public/**/*.php"
  - "resources/views/public/**/*.blade.php"
  - "resources/js/public/**/*"
  - "resources/css/public/**/*"
  - "routes/web.php"
  - "tests/Feature/Public/**/*.php"
---

# Public site

Trang chủ: header → search → urgent/new jobs → jobs theo KCN → companies → quy trình → FAQ → footer. Không có form "yêu cầu tư vấn"/Lead (Phase 1 không có Lead, ADR-021) — CTA liên hệ chỉ mở gọi/Zalo trực tiếp.

Job detail: thông tin nhanh → lương → mô tả → yêu cầu → phúc lợi → xe/chỗ ở → hồ sơ → company → related jobs. Mobile có sticky Gọi/Zalo/Ứng tuyển.

- Job `closed`/`paused`: URL chi tiết vẫn `200` (không 404), hiển thị "Đã ngừng tuyển"/"Tạm ngừng tuyển", ẩn form + nút "Ứng tuyển ngay" nhưng **giữ nguyên CTA Gọi/Zalo** (dùng contact cơ sở như bình thường); có thể hiển thị "Việc làm liên quan" đang `published` (`docs/CORE-FLOWS.md` mục 2.1, ADR-043). Không xây tính năng "liên hệ tư vấn chung" mới cho 2 trạng thái này.
- Form ngắn, guest submit được, tuân thủ consent và danh sách dữ liệu cấm.
- Form ứng tuyển render kèm `submission_token` ẩn do server sinh (bắt buộc, NOT NULL — không tin token client tự đặt); mỗi lần mở form (kể cả nhiều tab/nhiều Job) sinh token riêng, session lưu được nhiều token cùng lúc, không ghi đè. Server đối chiếu token với đúng `job_id` khi submit. Không nhận `stage`/`owner_branch_id` từ client — server tự tính (Luồng 3: `docs/CORE-FLOWS.md`). Không có `assigned_to` trong Phase 1 (ADR-021). Không có route đăng ký/đăng nhập cho ứng viên (ADR-028) — mọi CTA trên public site đều dành cho guest.
- CTA Gọi/Zalo luôn dùng phone/zalo của cơ sở phụ trách Job (`owner_branch_id`); `company_contacts` dù `is_public` cũng không thay thế CTA cơ sở, chỉ hiển thị thêm khi được gán làm contact chính thức của Job (`docs/CORE-FLOWS.md` mục 1, ADR-023) — không tự lộ contact công ty khách hàng chưa công khai.
- Không copy thương hiệu/nội dung ảnh tham khảo.
- Chỉ mở đúng ảnh cần dùng trong `docs/ui-reference/phase-1/`.
- Route và acceptance criteria phải khớp `docs/ROUTE-MAP.md` và `docs/ACCEPTANCE-CRITERIA.md`.
