# Project Status

## Phase / slice hiện tại

**Giai đoạn 1-4 DONE.** Nhóm 5 (candidate/application): schema DONE (8 bảng) và **Luồng 3 (guest
apply) DONE end-to-end** — form ứng tuyển thật đã hoạt động trên `jobs.show`.

- Company/Job (HR): CRUD + Draft/Verify/Publish/Pause/Close/Transfer-branch qua Action; còn thiếu
  Blade UI verify/publish/pause/close/transfer-branch.
- Public site: `jobs.index`/`jobs.show` (form ứng tuyển thật, mới), trang chủ, `sitemap.xml`.
- **`CreateApplicationAction`** — token/session (ADR-041) + `PhoneNormalizer` +
  `LockSubmissionByPhoneAction` (ADR-061) + `MatchOrCreateCandidateAction` (mục 6.2) + tạo
  `Application`/history/`candidate_duplicate_reviews` trong 1 transaction, idempotency + Case C.
  Form đủ field tùy chọn CORE-FLOWS gốc (không có SĐT phụ); rate limit có test; mobile input
  ≥44px đã xác nhận qua browser thật (`.claude/launch.json`).

## Quyết định quan trọng

- Salary Predicate: 2 mode loại trừ nhau (ADR-060 đã sửa). `hr.jobs.transfer-branch` chỉ Admin.
- `PhoneNormalizer` chưa có ADR chính thức. Consent dùng text tĩnh — bảng `pages` chưa build.
  "Nơi ở hiện tại" 1 dropdown đơn (không cascading Tỉnh/Quận). Chưa có trang Company public.
- Bỏ SĐT phụ khỏi form (từng gây race unique `(candidate_id, job_id)`); Controller nay catch
  `SubmissionLockTimeoutException` để trả lỗi thân thiện thay vì 500 (`/verify-task` Giai đoạn 7).

## Verification gần nhất

`php artisan test` PASS **575/575**. Đã submit thật qua browser mobile 375px (không tràn ngang,
input ≥44px) và desktop — DB dev cục bộ, cleanup qua tinker, không đụng DB test. `npm run build`
OK. `check-claude-config.py` OK, `git diff --check` sạch.

## Blocker / tồn đọng

Không có — chưa commit (chờ yêu cầu commit rõ ràng).

## Bước tiếp theo

1. Nhóm 5 — `ChangeApplicationStageAction` (Luồng 4/5: transition matrix, workflow cycle,
   Contact/Appointment), Reopen, merge/anonymize Candidate, Duplicate Review resolve (Admin).
2. Trang `pages` CMS + chính sách dữ liệu cá nhân thật; Public Company pages.
