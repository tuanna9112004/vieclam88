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

Nguồn cột/FK/index: `docs/DATABASE-DICTIONARY.md`; quan hệ: `docs/ERD.md`.

- `users` = account; `candidates` = hồ sơ; `candidate_contacts` = contact. Guest không cần user.
- Không auto-merge chỉ vì trùng phone. Merge phải transaction, giữ dấu vết và giải quyết application trùng.
- Một job là một đợt tuyển; unique `(candidate_id, job_id)`.
- `applications` lưu `submission_snapshot`, `job_snapshot` và consent tại thời điểm submit; snapshot không dùng để filter/report.
- Pipeline: `new`, `contacting`, `consulted`, `interview_scheduled`, `interviewed`, `waiting_start`, `started`, `closed`.
- Contact attempt, assignment, status history, note, verification và export log là dữ liệu riêng; history chỉ INSERT.
- Địa điểm dùng `administrative_units`, `company_locations`, `job_locations`; không lặp province/KCN trên jobs.
- Tiền VND dùng unsigned bigint.
- Không tạo FK mơ hồ hoặc bảng Phase 2.
- Không hard-delete dữ liệu cốt lõi; RESTRICT cho company→job, job/candidate→application, application→history. Cascade chỉ cho pivot/phụ trợ; actor lịch sử có thể SET NULL.
- Mọi ràng buộc quan trọng phải có database constraint và test, không chỉ validation.

## Transaction bắt buộc

1. Apply: normalize/match candidate → application + snapshots → initial history.
2. Change stage: lock → validate transition → history → update.
3. Assign: lock → assignment history → update.
4. Merge candidate: lock both → contacts/app conflict resolution → move → mark merged.
5. Verify job: verification → last_verified/status update.
6. Convert lead: lock → candidate/application/history → mark converted.

Controller không chứa các transaction trên; dùng Action/Service.
