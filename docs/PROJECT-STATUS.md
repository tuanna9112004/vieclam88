# Project Status

## Phase / slice hiện tại

**Giai đoạn 1-4 DONE (Nhóm 1-4 theo `ROADMAP.md`).** Chuẩn bị sang Nhóm 5 — candidate/application.

- `companies`/`company_locations`/`company_contacts`: schema + Admin CRUD + Quick Create + policy.
- `jobs`/`job_locations`/`job_work_shifts`: Draft (index/create/store/edit/update) + Job Branch
  Contract + Quick Create Company/Location/Contact trong form Job.
- `work_shifts`/`recruitment_sources`/`settings` (Nhóm 2): schema + seeder bắt buộc.
- `job_verifications`/`job_status_histories`/`job_branch_histories`: schema + Status×Result
  matrix (ADR-059) + `PublishJobAction` đủ 22 điều kiện (ADR-060) + `pause`/`close`/
  `transfer-branch` qua `ChangeJobStatusAction`/`ChangeJobBranchAction` (action duy nhất, không
  tự sửa `jobs.status`).
- Nhóm 4 (Job) **hoàn tất chức năng** — còn thiếu Blade UI cho verify/publish/pause/close/
  transfer-branch (mới có route backend).

## Quyết định quan trọng

- Salary Predicate: 2 mode loại trừ nhau, khớp `CORE-FLOWS.md`/`ACCEPTANCE-CRITERIA.md` — đã
  sửa `ADR-060` (bản cũ ghi nhầm "1/4 độc lập", phát hiện qua `/verify-task`).
- `company_contact_id` re-check cả lúc Store/Update lẫn lúc Publish.
- `hr.jobs.transfer-branch` chỉ Admin, không có nhánh Staff-cùng-cơ-sở.

## Verification gần nhất

`php artisan test` PASS **416/416** (gồm 1 test row-lock 2-connection thật, 1 test end-to-end
draft→verify→publish→pause→republish→close→transfer-branch). Migration rollback+remigrate an
toàn trên `vieclam88_test`. `composer validate`/`check-claude-*.py`/`git diff --check` sạch.

## Blocker / tồn đọng

- **Chưa commit:** pause/close/transfer-branch + 2 test mới (concurrency, end-to-end).
- **Chưa push** `e66b851` lên `origin/main`.

## Bước tiếp theo

1. Commit + xác nhận push phần Nhóm 4 còn lại.
2. Nhóm 5 — `candidates`/`candidate_contacts` (schema trước, theo `/db-task`).
