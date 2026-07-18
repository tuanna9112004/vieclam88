# Roadmap — vieclam88

Lộ trình theo Giai đoạn 0–4 (Phase 1) + backlog Phase 2. Trạng thái tiến độ chi tiết nhất luôn
nằm ở `docs/PROJECT-STATUS.md` — file này chỉ giữ checklist theo giai đoạn, cập nhật `[x]` khi
hoàn thành, không viết thêm ghi chú tiến độ dài ở đây (tránh trùng với
`docs/PROJECT-STATUS.md`). Nguồn sự thật nghiệp vụ cho mọi giai đoạn: `docs/CORE-FLOWS.md`.

**Không migration nào được viết trước khi Giai đoạn 0 hoàn thành** — xem điều kiện ở cuối mục
Giai đoạn 0.

## Giai đoạn 0 — Chốt plan/spec

- [x] Sáu Critical Business Flows (`docs/CORE-FLOWS.md`).
- [x] Phạm vi Phase 1 chính thức — không Lead, không assignment/claim, không Favorites
      (ADR-021).
- [x] Branch (cơ sở nội bộ), phân biệt với `company_locations`/`company_contacts` (ADR-015).
- [x] Duplicate contract (case A/B/C + merge — admin chọn thủ công) — `docs/CORE-FLOWS.md`
      mục 6.
- [x] Idempotency contract (`applications.submission_token`) — `docs/CORE-FLOWS.md` mục 3.
- [x] Transition matrix Application (bao gồm `closed → new` mở lại có kiểm soát) —
      `docs/CORE-FLOWS.md` mục 5.1.
- [x] Contact Result enum chính thức (11 giá trị) — `docs/CORE-FLOWS.md` mục 5.2.
- [x] Appointment — quy tắc đổi lịch (tạo mới, không sửa đè) — `docs/CORE-FLOWS.md` mục 5.3.
- [x] Job transition matrix chính thức — `docs/CORE-FLOWS.md` mục 1.
- [ ] Data retention — thời hạn lưu dữ liệu ứng viên và chính sách anonymize snapshot vẫn
      **[CẦN CHỐT]** (`docs/CORE-FLOWS.md` mục 7–8), chưa có quyết định từ công ty.
- [x] Constraint và index — đã chốt tầng DB vs Service (`docs/DATABASE-DICTIONARY.md` mục
      "Ràng buộc: DB bảo vệ vs Service + test bảo vệ").
- [ ] Xác nhận 5 enum **[đề xuất]** còn tồn đọng (`docs/DATABASE-DICTIONARY.md`).
- [ ] Cài PHP 8.4, kiểm tra Composer, Node LTS, MariaDB.

**Điều kiện chuyển sang Giai đoạn 1:** thời hạn lưu dữ liệu + chính sách anonymize snapshot +
5 enum **[đề xuất]** đã được xác nhận hoặc chấp nhận mặc định đề xuất bằng văn bản (cập nhật lại
`docs/CORE-FLOWS.md`/`docs/DATABASE-DICTIONARY.md`, xóa khỏi danh sách [CẦN CHỐT]); môi trường
code đã cài đặt xong.

Chưa tạo mã nguồn trong giai đoạn này.

## Giai đoạn 1 — Database lõi

- [ ] Khởi tạo Laravel 13.x project, PHP 8.4.x (`.claude/rules/tech-stack.md`).
- [ ] Migration cho toàn bộ 25 bảng theo đúng `docs/DATABASE-DICTIONARY.md`: Branch, User
      (staff bắt buộc có branch), Company/location/contact, Job, Candidate, Application
      (snapshot, consent, `submission_token`), Status history, Branch history, Contact log,
      Appointment, Note, Export log. **Không tạo** `lead_requests`, `favorites`,
      `application_assignment_histories`, cột `applications.assigned_to` (ADR-021).
- [ ] Enum (PHP backed enum) cho mọi cột trạng thái, khớp transition matrix (Job và
      Application) và Contact Result enum.
- [ ] Model + relationship khớp `docs/ERD.md`.
- [ ] Factory cho toàn bộ 25 bảng.
- [ ] Seeder (`branches`, `work_shifts`, `recruitment_sources`, `administrative_units` dữ liệu
      mẫu — cần ít nhất 2 cơ sở mẫu để test phân quyền theo cơ sở).
- [ ] Database test (foreign key, unique constraint gồm `submission_token`, soft delete,
      transition matrix Job/Application, duplicate contract, branch scoping). "Audit log" của
      Phase 1 = audit trail theo từng bảng lịch sử (ADR-019), không phải bảng `audit_logs`
      tổng quát riêng — không tạo bảng này.

**Điều kiện hoàn thành:**

```bash
php artisan migrate:fresh --seed
php artisan test
```

## Giai đoạn 2 — Website public và Job

- [ ] Authentication admin/staff (`/hr/dang-nhap`), `users.branch_id` bắt buộc khi tạo staff.
- [ ] Company CRUD, Location CRUD, Contact CRUD, Branch CRUD (`docs/ROUTE-MAP.md`).
- [ ] Job CRUD, chọn `owner_branch_id`, điều kiện publish đầy đủ (Luồng 1).
- [ ] Publish/pause/reopen (`paused → published`)/close job theo transition matrix, nhân bản
      job.
- [ ] Danh sách và chi tiết Job public (`/viec-lam`), tìm kiếm/lọc (Luồng 2).
- [ ] CTA Gọi/Zalo trên Job — luôn dùng contact của `owner_branch_id`, không dùng
      `company_contacts` làm CTA thay thế (ADR-023).
- [ ] Job verification (`job_verifications`, transaction "Verify job").
- [ ] Form ứng tuyển guest (`ApplicationController@store`), toàn bộ transaction Luồng 3
      (`docs/CORE-FLOWS.md` mục 3) gồm `submission_token`, duplicate contract case A/B/C.

## Giai đoạn 3 — HR xử lý hồ sơ

- [ ] Danh sách hồ sơ theo cơ sở (Luồng 4), cột tối thiểu theo `docs/CORE-FLOWS.md` mục 4.
- [ ] Chi tiết hồ sơ — bất kỳ staff nào cùng cơ sở đều xử lý được, không có "nhận xử lý"/"gán
      nhân viên" (ADR-021).
- [ ] Contact Log (`application_contact_attempts`), enum kết quả chính thức.
- [ ] Appointment — callback và interview, tạo/cập nhật kết quả, đổi lịch = hủy cũ + tạo mới
      (Luồng 5 mục 5.3).
- [ ] Đổi stage qua `ChangeApplicationStageAction`, validate transition matrix, gồm `closed →
      new` mở lại có kiểm soát (Luồng 5).
- [ ] Close reason bắt buộc khi đóng hồ sơ.
- [ ] Chuyển cơ sở ngoại lệ (Luồng 6 mục 6.1), chỉ admin.
- [ ] Merge candidate (admin chọn Application giữ lại) + xử lý xung đột Application cùng job
      (Luồng 6 mục 6.3), chỉ admin.

## Giai đoạn 4 — Hoàn thiện

- [ ] Dashboard cơ bản (KPI đã chốt).
- [ ] Export CSV + `export_logs`.
- [ ] Security review (`.claude/rules/security-seo-testing.md`), bao gồm test 403 xem chéo
      cơ sở và test mass-assignment `owner_branch_id`/`stage`.
- [ ] SEO, Responsive.
- [ ] Feature test đầy đủ theo `docs/ACCEPTANCE-CRITERIA.md`.
- [ ] Backup.
- [ ] Cron/Scheduler xác nhận còn tuyển (`.claude/rules/roles-business-rules.md`).
- [ ] SSL, Log rotation.
- [ ] Deploy VPS, cấu hình path `/hr` theo `.claude/rules/tech-stack.md`.

## Candidate account — sau khi guest + HR ổn định

Chỉ tài khoản cơ bản; **không có Favorites** (Phase 2, ADR-021).

- [ ] Register/Login (`docs/ROUTE-MAP.md` phần "Candidate account").
- [ ] Profile.
- [ ] Applied jobs (hiển thị rút gọn, không lộ pipeline nội bộ — `.claude/rules/scope-standards.md`).
- [ ] Link guest candidate vào tài khoản mới đăng ký (`candidates.user_id`).

## Phase 2 — ngoài phạm vi Phase 1 (không code, không thiết kế trước ở Phase 1)

- Lead (mọi kênh: điện thoại, Zalo, form "yêu cầu tư vấn" trên website); chuyển đổi Lead thành
  Application.
- Assignment: "Nhận xử lý" (claim), gán nhân viên (assign), tự động phân công, round-robin,
  vai trò "trưởng cơ sở" phân công, `application_assignment_histories`.
- Favorites.
- Candidate Account nâng cao (dashboard nâng cao, theo dõi trạng thái pipeline qua tài khoản).
- Tích hợp Zalo API; tự động gọi/gửi tin nhắn.
- Cộng tác viên, hoa hồng, referral.
- AI matching, CRM đa kênh.

## Ghi chú áp dụng lộ trình

- Có phụ thuộc thứ tự: Giai đoạn 1 (schema) phải xong trước 2–4; Giai đoạn 2 (job/public) nên
  xong trước Giai đoạn 3 (application cần job tồn tại); Giai đoạn 3 (HR workflow) cần Giai
  đoạn 2 xong (cần có application để xử lý).
- Mỗi giai đoạn nên là 1 hoặc vài session riêng.
- Cập nhật `docs/PROJECT-STATUS.md` sau khi hoàn thành mỗi giai đoạn, theo quy trình ở
  `.claude/skills/handoff/SKILL.md`.
- Không mở rộng sang các hạng mục "Ngoài phạm vi" (`.claude/rules/scope-standards.md`) hoặc
  Phase 2 trong lộ trình này.
