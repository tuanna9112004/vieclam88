# Project Status

## Phase / slice hiện tại

Giai đoạn 1-4 (Company/Job) DONE. Nhóm 5: schema DONE (8 bảng), Luồng 3 (guest apply) DONE
end-to-end, **Giai đoạn 8 (HR xử lý Application) DONE** — đã commit + push (`2e7bfc9`).

- Company/Job (HR): CRUD + Draft/Verify/Publish/Pause/Close/Transfer-branch qua Action; còn
  thiếu Blade UI verify/publish/pause/close/transfer-branch.
- Public site: `jobs.index`/`jobs.show` (form ứng tuyển thật), trang chủ, `sitemap.xml`.
- Luồng 3: `CreateApplicationAction` — token/session, lock theo phone, matching Candidate,
  idempotency + Case C; rate limit, mobile ≥44px đã xác nhận qua browser thật.
- Giai đoạn 8: `hr.applications.index/.show/.contacts/.appointments(store+update)/.notes/.stage/.transfer-branch`
  — Branch isolation, actor/workflow_cycle server-side, `lockForUpdate`, transition matrix 5.1 +
  Reopen 5.5, `TransferApplicationBranchAction` (Luồng 6.1 — chuyển cơ sở ngoại lệ chỉ Admin), timeline tổng hợp 5 bảng lịch sử (không bảng mới). Chưa có export, Dashboard KPI,
  Blade form thao tác trên trang show (route đã có, UI chưa build).
- Candidate Duplicate Review (Admin): `hr.duplicate-reviews.index/.show/.resolve` — xem 2 candidate cạnh nhau, `ResolveCandidateDuplicateReviewAction` (Luồng 6.2.2 — không tự merge, đồng bộ `needs_duplicate_review` khi không còn pending), Blade UI đầy đủ.

## Quyết định quan trọng (kèm lý do)

- Bỏ SĐT phụ khỏi form ứng tuyển — tạo 2 lock key khác nhau cho cùng 1 Candidate, gây race unique
  `(candidate_id, job_id)` không được catch đúng (phát hiện qua `/verify-task`).
- Tách `Close`/`ReopenApplicationAction` khỏi `ChangeApplicationStageAction` — Reopen có 8 điều
  kiện riêng (mục 5.5); cả 2 vẫn dùng chung 1 route theo `ROUTE-MAP.md`.
- `close_reason=duplicate` không cho chọn thủ công — chỉ sinh từ merge-conflict (chưa build); tự
  chọn sẽ tạo hồ sơ "đóng vĩnh viễn" sai ngữ cảnh.
- 3 model lịch sử thêm cast `created_at=>datetime` — `$timestamps=false` khiến Eloquent không tự
  cast (lỗi có sẵn, lộ ra khi Timeline lần đầu gọi `->format()`).
- Salary Predicate 2 mode loại trừ nhau (ADR-060). `hr.jobs.transfer-branch` chỉ Admin.

## Verification gần nhất / Blocker

`php artisan test` PASS **661/661**. `check-claude-config.py` OK, `git diff --check` sạch. Đã
commit `2e7bfc9`, push lên `origin/main`. Không có blocker.

## Bước tiếp theo

1. Merge/anonymize Candidate, Duplicate Review resolve (Admin) — phần còn lại Nhóm 5.
2. Export CSV, Dashboard KPI (Giai đoạn 9 — công cụ Admin).
3. Blade UI Job verify/publish/pause/close/transfer-branch; form thao tác trên `hr.applications.show`.
