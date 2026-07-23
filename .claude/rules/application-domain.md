---
paths:
  - "app/**/*Application*.php"
  - "app/**/*Candidate*.php"
  - "app/**/*Appointment*.php"
  - "app/**/*ContactAttempt*.php"
  - "app/**/*DuplicateReview*.php"
  - "database/**/*application*.php"
  - "database/**/*candidate*.php"
  - "tests/**/*Application*.php"
  - "tests/**/*Candidate*.php"
---

# Candidate và Application domain

Nguồn: `docs/CORE-FLOWS.md` mục 3–7 và các bảng Candidate/Application trong Dictionary.

- Ứng viên luôn guest; giữ contact gốc + normalized, không fuzzy/AI và không tự merge chỉ vì trùng phone.
- Cùng token phải idempotent. Khác token cùng phone phải serialize theo named lock đã chốt, query lại sau lock và không log phone thô.
- Matching phải xét tất cả Candidate cùng phone, resolve/dedupe root; không dùng `first()`. Review mơ hồ tạo `candidate_duplicate_reviews`, không tự merge.
- Trước insert phải kiểm tra cùng Job trên toàn merged family; Application sở hữu cơ sở bằng snapshot `owner_branch_id` từ Job.
- Stage chỉ đổi qua Action theo transition matrix; bằng chứng Contact/Appointment phải thuộc `workflow_cycle` hiện tại.
- Contact result, stage, appointment, note và history là dữ liệu riêng; đổi lịch = hủy bản cũ + tạo bản mới.
- Reopen tăng cycle, cần reason; dữ liệu cycle cũ chỉ hiển thị, không mở khóa cycle mới.
- Application lưu submission/job snapshot và consent; anonymize theo mục 7, không làm mất dữ liệu thống kê/audit được phép giữ.
- Target Phase 2 đã duyệt (ADR-080, CHƯA migrate): `candidate_documents` (CV PDF/avatar) — xem `docs/DATABASE-DICTIONARY.md` mục 9.35, code đúng `TASK x.y` (Phần 9) theo `docs/refactor/TASK-INDEX.md`. PDF không đổi luồng chống trùng/workflow_cycle/duplicate review ở trên.
