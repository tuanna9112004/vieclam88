# Project Status

## Phase / slice hiện tại

**Giai đoạn 1-4 DONE (Nhóm 1-4 theo `ROADMAP.md`).** Đã bổ sung lớp Public site (Job
list/detail, trang chủ, sitemap) trên schema Nhóm 3-4 có sẵn. Nhóm 5 (candidate/application)
chưa bắt đầu.

- Company/Job (HR): như trước — CRUD + Draft/Verify/Publish/Pause/Close/Transfer-branch đầy đủ
  qua domain Action; còn thiếu Blade UI verify/publish/pause/close/transfer-branch (mới có route).
- **Public site (mới):** `jobs.index` (filter KCN/đơn vị hành chính/công ty/lương/ca/xe đưa
  đón/chỗ ở/từ khóa, sort, phân trang giữ query string, tránh N+1), `jobs.show` (paused/closed/
  expired giữ URL đúng contract, CTA Branch luôn hiện, Company Contact chỉ khi `is_public`,
  JobPosting JSON-LD chỉ khi active, việc làm liên quan), trang chủ, `sitemap.xml`.

## Quyết định quan trọng

- Salary Predicate: 2 mode loại trừ nhau (ADR-060 đã sửa).
- `company_contact_id` re-check cả lúc Store/Update lẫn Publish lẫn hiển thị public.
- `hr.jobs.transfer-branch` chỉ Admin.
- Public site **chưa có** form ứng tuyển thật (Luồng 3) và **chưa có** trang Company public
  (`companies.index/show`) — trang chủ tạm bỏ khối "Top công ty" vì phụ thuộc trang này.

## Verification gần nhất

`php artisan test` PASS **459/459** (416 cũ + 43 mới: JobListTest/JobShowTest/HomeTest/
SeoTest). `npm run build` OK. `check-claude-config.py` OK, `git diff --check` sạch. Chưa chạy
`composer validate` (không có `composer` trong PATH của môi trường chạy việc này).

## Blocker / tồn đọng

Không có — đã commit `712aad3` và push lên `origin/main`.

## Bước tiếp theo

1. Luồng 3 — form ứng tuyển thật (submission_token, consent, honeypot, rate limit) để CTA
   "Ứng tuyển ngay" hoạt động trên `jobs.show`.
2. Public Company pages (`companies.index`/`companies.show`).
3. Nhóm 5 — schema `candidates`/`candidate_contacts` (`/db-task`).
