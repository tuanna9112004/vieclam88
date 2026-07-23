---
paths:
  - "database/**/*.php"
  - "app/Models/**/*.php"
  - "app/Enums/**/*.php"
  - "tests/**/*Database*.php"
  - "tests/Unit/Models/**/*.php"
---

# Database và model

- Nguồn duy nhất: đúng bảng trong `docs/DATABASE-DICTIONARY.md`; quan hệ ở `docs/ERD.md`; thứ tự migration ở cuối Dictionary.
- Không đổi nullability/FK/index/constraint để code thuận tiện. Khác biệt phải được báo cáo và đồng bộ tài liệu trước.
- Constraint quan trọng phải có DB constraint và test; validation ứng dụng không thay thế unique/FK/check.
- `jobs.owner_branch_id` và `applications.owner_branch_id` theo contract; `applications.submission_token` NOT NULL UNIQUE; giữ `UNIQUE(candidate_id, job_id)`.
- Enum state machine cốt lõi dùng DB enum theo Dictionary; enum phụ dùng varchar + PHP backed enum.
- Soft delete/on-delete tuân thủ mục “Chính sách xóa”; history/attempt/log chỉ insert, không cascade mất lịch sử.
- Factory không được tạo state/history trái domain; ưu tiên tạo lịch sử qua Action trong test.
- Job-specific: `.claude/rules/job-domain.md`; Candidate/Application: `.claude/rules/application-domain.md`.
- Bảng target Phase 2 (ADR-080, CHƯA có migration): `docs/DATABASE-DICTIONARY.md` mục 9.29–9.36, đánh dấu rõ "target" — chỉ viết migration/model đúng `TASK x.y` đang thực thi theo `docs/VIECLAM88_TASK_REGISTRY_V2.3.md` (không theo thứ tự Batch cũ, xem `docs/refactor/BATCH-TASK-MAP.md`).
