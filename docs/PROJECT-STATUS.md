# Project Status

## Phase / slice hiện tại

**Giai đoạn 1-4 DONE.** Nhóm 5 (candidate/application): schema DONE (8 bảng) và **Luồng 3 (guest
apply) DONE end-to-end** — form ứng tuyển thật đã hoạt động trên `jobs.show`.

- Company/Job (HR): CRUD + Draft/Verify/Publish/Pause/Close/Transfer-branch qua Action; còn thiếu
  Blade UI verify/publish/pause/close/transfer-branch.
- Public site: `jobs.index`/`jobs.show` (form ứng tuyển thật, mới), trang chủ, `sitemap.xml`.
- **Giai đoạn 8 (Xử lý Application) DONE** — `hr.applications.index/.show/.contacts/.appointments
  (store+update)/.notes/.stage` (transition matrix 5.1 + Reopen 5.5). Branch isolation, actor/
  workflow_cycle server-side, `lockForUpdate`, timeline tổng hợp 5 bảng lịch sử (không bảng
  mới). `appointments.update` đánh dấu completed/cancelled/no_show, mở khóa được
  `interview_scheduled→interviewed` qua route thật. Chưa có export, Dashboard KPI.

## Quyết định quan trọng

- Salary Predicate: 2 mode loại trừ nhau (ADR-060 đã sửa). `hr.jobs.transfer-branch` chỉ Admin.
- `PhoneNormalizer` chưa có ADR chính thức. Consent dùng text tĩnh, "Nơi ở hiện tại" 1 dropdown
  đơn (không cascading). Bỏ SĐT phụ khỏi form ứng tuyển (từng gây race unique candidate/job).

## Verification gần nhất

`php artisan test` PASS **661/661**. Đã submit thật qua browser mobile 375px (không tràn ngang,
input ≥44px) và desktop — DB dev cục bộ, cleanup qua tinker, không đụng DB test. `npm run build`
OK. `check-claude-config.py` OK, `git diff --check` sạch.

## Blocker / tồn đọng

Không có — Giai đoạn 8 vừa qua `/verify-task`, đã sửa finding Critical (appointment update) và
chuẩn bị commit.

## Bước tiếp theo

1. Merge/anonymize Candidate, Duplicate Review resolve (Admin).
2. Export CSV/Dashboard KPI; trang `pages` CMS; Public Company pages.
