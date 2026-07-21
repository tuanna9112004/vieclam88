# Project Status

## Phase / slice hiện tại

**Giai đoạn 1-3 DONE. Giai đoạn 4 (Company/Job) đang thực hiện.**

- `companies` (`09f5278`), `company_locations` (`bd0a823`), `company_contacts` (`db64071`):
  schema + Admin CRUD + Quick Create + policy — DONE.
- `jobs` + `job_locations` (`6d0f5ae`): Job Draft creation (index/create/store/edit/update,
  chưa publish/pause/close/verify/transfer-branch/destroy/restore), Job Branch Contract
  (Staff tự gán `owner_branch_id`, Admin bắt buộc chọn, `update` không đổi được cột này),
  Quick Create Company/Location ngay trong form Job (AJAX, không rời màn hình) — DONE.

## Verification gần nhất

```bash
php artisan test     # PASS 276/276 (tại commit 6d0f5ae)
composer validate     # PASS
npm run build         # PASS
git diff --check      # PASS
```

## Working tree (chưa commit)

`CLAUDE.md` — giữ nguyên, người dùng xác nhận, không tự sửa.

## Blockers

Không có blocker kỹ thuật.

## Bước tiếp theo

1. **NEXT:** Job Publish Predicate (22 điều kiện, ADR-060) + `job_work_shifts`/
   `job_verifications`/`job_status_histories`/`job_branch_histories` — chưa làm.
2. `work_shifts`/`recruitment_sources`/`settings` (Nhóm 2, ADR-069) — chưa làm, cần trước
   khi gắn ca làm vào Job.
