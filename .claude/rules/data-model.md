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
- Không auto-merge chỉ vì trùng phone; khớp mạnh yêu cầu tên **khớp chính xác** sau chuẩn hóa (không dùng ngưỡng tương đồng). Merge phải transaction, giữ dấu vết, admin tự chọn Application giữ lại khi cả hai có application cùng job (duplicate contract đầy đủ: `docs/CORE-FLOWS.md` mục 6.2–6.3).
- Một job là một đợt tuyển; unique `(candidate_id, job_id)`. `applications.submission_token` unique khi có giá trị — chống double-submit cùng 1 lần gửi form, khác với unique candidate+job (chống ứng tuyển lại) — xem `docs/CORE-FLOWS.md` mục 3.
- `applications` lưu `submission_snapshot`, `job_snapshot` và consent tại thời điểm submit; snapshot không dùng để filter/report.
- `applications.owner_branch_id` copy từ `jobs.owner_branch_id` lúc tạo, không suy ra động qua Job; đổi cơ sở đi qua `application_branch_histories`, không tạo Application mới. **Không có cột `assigned_to`** trong Phase 1 (ADR-021).
- Pipeline: `new`, `contacting`, `consulted`, `interview_scheduled`, `interviewed`, `waiting_start`, `started`, `closed`, và `closed → new` (mở lại có kiểm soát, bắt buộc lý do, không được vi phạm unique candidate+job). Transition matrix chính thức và điều kiện bắt buộc từng bước: `docs/CORE-FLOWS.md` mục 5.1. Contact Result dùng đúng 11 giá trị enum chính thức ở mục 5.2, đồng nhất với `docs/DATABASE-DICTIONARY.md`.
- Job status: `draft`, `published`, `paused`, `closed`; chỉ 5 transition hợp lệ (`docs/CORE-FLOWS.md` mục 1), không tự thêm transition khác.
- Lịch hẹn (`application_appointments`, type `callback`/`interview`) tách khỏi `stage`; `interview_scheduled → interviewed` yêu cầu appointment interview `status=completed`. Đổi lịch = hủy bản ghi cũ (`cancelled`/`no_show`) + tạo bản ghi mới, không sửa đè `scheduled_at`.
- Đúng 1 `job_locations.is_primary = true` mỗi job, bảo vệ bằng unique trên cột generated (`docs/DATABASE-DICTIONARY.md` mục `job_locations`), không chỉ validate ở Service.
- Contact attempt, status history, branch history, note, verification và export log là dữ liệu riêng; các bảng `*_histories`/`*_attempts` chỉ INSERT. Phase 1 không có bảng phân công (`application_assignment_histories`) — trách nhiệm theo dõi qua người thực hiện trên từng bảng lịch sử (ADR-019, ADR-021).
- Địa điểm dùng `administrative_units`, `company_locations`, `job_locations`; không lặp province/KCN trên jobs. `branches` (cơ sở nội bộ) là bảng khác, không dùng lẫn với `company_locations` (ADR-015).
- Tiền VND dùng unsigned bigint.
- Không tạo FK mơ hồ hoặc bảng Phase 2. Phase 1 không có `lead_requests`/`favorites` dưới bất kỳ hình thức nào (ADR-021).
- Không hard-delete dữ liệu cốt lõi; RESTRICT cho company→job, job/candidate→application, application→history, branch→job/application. Cascade chỉ cho pivot/phụ trợ; actor lịch sử có thể SET NULL.
- Mọi ràng buộc quan trọng phải có database constraint và test, không chỉ validation — bảng phân lớp "DB bảo vệ vs Service bảo vệ": `docs/DATABASE-DICTIONARY.md` mục "Ràng buộc".

## Transaction bắt buộc

1. Apply: kiểm tra `submission_token` trùng (idempotent, trả lại bản ghi cũ nếu trùng) → normalize/match candidate (duplicate contract) → application (copy `owner_branch_id` từ job) + snapshots → initial status history (`null → new`) → initial branch history (`null → owner_branch_id`).
2. Change stage: lock → validate transition theo matrix (`docs/CORE-FLOWS.md` 5.1) → nếu `closed → new`: kiểm tra không vi phạm unique candidate+job, reset `close_reason`/`closed_at` → history (note bắt buộc khi mở lại) → update.
3. Transfer branch: lock → kiểm tra quyền (admin) → branch history (reason bắt buộc) → update `owner_branch_id`, giữ nguyên các bảng con.
4. Merge candidate: lock both → admin chọn application giữ lại, application còn lại đóng `duplicate` (mục 6.3) → move application khác job → mark merged.
5. Verify job: verification → last_verified/status update.

Không có transaction "Assign"/"Claim" trong Phase 1 (ADR-021). Controller không chứa các
transaction trên; dùng Action/Service.
