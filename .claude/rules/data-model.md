---
paths:
  - "database/**/*.php"
  - "app/Models/**/*.php"
  - "app/Enums/**/*.php"
  - "app/{Actions,Services}/**/*.php"
  - "tests/**/*Database*.php"
  - "tests/Feature/**/*Application*.php"
  - "tests/Feature/**/*Candidate*.php"
---

# Database invariants

Nguồn cột/FK/index: `docs/DATABASE-DICTIONARY.md`; quan hệ: `docs/ERD.md`; 6 luồng nghiệp vụ
mà các invariant này phục vụ: `docs/CORE-FLOWS.md`.

- `users` = account; `candidates` = hồ sơ; `candidate_contacts` = contact. Guest không cần user.
- Không auto-merge chỉ vì trùng phone. Merge phải transaction, giữ dấu vết và giải quyết application trùng (duplicate contract đầy đủ: `docs/CORE-FLOWS.md` mục 6.2–6.3).
- Một job là một đợt tuyển; unique `(candidate_id, job_id)`.
- `applications` lưu `submission_snapshot`, `job_snapshot` và consent tại thời điểm submit; snapshot không dùng để filter/report.
- `applications.owner_branch_id` copy từ `jobs.owner_branch_id` lúc tạo, không suy ra động qua Job; đổi cơ sở đi qua `application_branch_histories`, không tạo Application mới.
- Pipeline: `new`, `contacting`, `consulted`, `interview_scheduled`, `interviewed`, `waiting_start`, `started`, `closed`. Transition matrix chính thức và điều kiện bắt buộc từng bước: `docs/CORE-FLOWS.md` mục 5.1.
- Lịch hẹn (`application_appointments`, type `callback`/`interview`) tách khỏi `stage`; `interview_scheduled → interviewed` yêu cầu appointment interview `status=completed`.
- Contact attempt, assignment, status history, branch history, note, verification và export log là dữ liệu riêng; các bảng `*_histories`/`*_attempts` chỉ INSERT.
- Địa điểm dùng `administrative_units`, `company_locations`, `job_locations`; không lặp province/KCN trên jobs. `branches` (cơ sở nội bộ) là bảng khác, không dùng lẫn với `company_locations` (ADR-015).
- Tiền VND dùng unsigned bigint.
- Không tạo FK mơ hồ hoặc bảng Phase 2. `lead_requests` Phase 1 không có FK/cột chuyển đổi sang `applications` (ADR-018).
- Không hard-delete dữ liệu cốt lõi; RESTRICT cho company→job, job/candidate→application, application→history, branch→job/application. Cascade chỉ cho pivot/phụ trợ; actor lịch sử có thể SET NULL.
- Mọi ràng buộc quan trọng phải có database constraint và test, không chỉ validation.

## Transaction bắt buộc

1. Apply: normalize/match candidate (duplicate contract) → application (copy `owner_branch_id` từ job) + snapshots → initial status history (`null → new`) → initial branch history (`null → owner_branch_id`).
2. Change stage: lock → validate transition theo matrix (`docs/CORE-FLOWS.md` 5.1) → history → update.
3. Assign: lock → kiểm tra staff cùng `owner_branch_id` → assignment history → update.
4. Transfer branch: lock → kiểm tra quyền (admin) → branch history (reason bắt buộc) → update `owner_branch_id`, giữ nguyên các bảng con.
5. Merge candidate: lock both → contacts/app conflict resolution (mục 6.3) → move → mark merged.
6. Verify job: verification → last_verified/status update.

`Convert lead` (lead → candidate/application) không còn trong Phase 1 — dời sang Phase 2
(ADR-018). Controller không chứa các transaction trên; dùng Action/Service.
