# Roadmap — vieclam88

Lộ trình theo Giai đoạn 0–4 (Phase 1) + backlog Phase 2. Trạng thái tiến độ chi tiết nhất luôn
nằm ở `docs/PROJECT-STATUS.md` — file này chỉ giữ checklist theo giai đoạn, cập nhật `[x]` khi
hoàn thành, không viết thêm ghi chú tiến độ dài ở đây (tránh trùng với
`docs/PROJECT-STATUS.md`). Nguồn sự thật nghiệp vụ cho mọi giai đoạn: `docs/CORE-FLOWS.md`.

**Không migration nào được viết trước khi Giai đoạn 0 hoàn thành** — xem điều kiện ở cuối mục
Giai đoạn 0.

## Giai đoạn 0 — Chốt nghiệp vụ, database và môi trường

- [x] Audit repository.
- [x] Chuẩn hóa tài liệu (`CLAUDE.md`, `.claude/rules/*.md`, `docs/*.md`).
- [x] Đặc tả 6 luồng nghiệp vụ cốt lõi (`docs/CORE-FLOWS.md`).
- [x] Thiết kế `branches` (cơ sở nội bộ) và phân biệt với `company_locations`/`company_contacts`.
- [x] Duplicate handling contract (case A/B/C + merge conflict) — `docs/CORE-FLOWS.md` mục 6.
- [x] Transition matrix chính thức cho `applications.stage` — `docs/CORE-FLOWS.md` mục 5.1.
- [x] Thiết kế `application_appointments` (callback/interview).
- [x] Điều kiện publish Job (bao gồm `owner_branch_id`, contact công khai).
- [x] Quyền theo cơ sở (staff scope, admin không giới hạn) — `docs/CORE-FLOWS.md` mục 4,
      `.claude/rules/roles-business-rules.md`.
- [ ] Chính sách dữ liệu cá nhân (nội dung consent, thời hạn lưu trữ, quyền yêu cầu xóa/ẩn
      danh của ứng viên) — chưa có tài liệu riêng, cần xác nhận trước Giai đoạn 1.
- [ ] Review và chốt ERD (`docs/ERD.md`) sau khi thêm bảng mới.
- [ ] Review và chốt database dictionary (`docs/DATABASE-DICTIONARY.md`) sau khi thêm bảng mới.
- [ ] Review và chốt foreign key, check constraint và delete policy cho bảng mới.
- [ ] Review và chốt index, unique và quy tắc primary duy nhất cho bảng mới.
- [ ] Xác nhận toàn bộ mục **[CẦN CHỐT]** liệt kê ở `docs/CORE-FLOWS.md` mục 7 (ngưỡng khớp
      tên, nhóm contact result mở khóa `consulted`, có cho mở lại `closed`, tiêu chí merge,
      phạm vi xem Job của staff, scope cơ sở cho `lead_requests`) và 5 enum **[đề xuất]** còn
      tồn đọng trong `docs/DATABASE-DICTIONARY.md`.
- [ ] Cài PHP 8.4, kiểm tra Composer, Node LTS, MariaDB.

**Điều kiện chuyển sang Giai đoạn 1:** tất cả mục **[CẦN CHỐT]** ở `docs/CORE-FLOWS.md` mục 7
đã được xác nhận hoặc chấp nhận mặc định đề xuất bằng văn bản (cập nhật lại
`docs/CORE-FLOWS.md`, xóa khỏi danh sách); ERD + dictionary không còn mâu thuẫn với
`docs/CORE-FLOWS.md`; môi trường code đã cài đặt xong.

Chưa tạo mã nguồn trong giai đoạn này.

## Giai đoạn 1 — Database lõi

- [ ] Khởi tạo Laravel 13.x project, PHP 8.4.x (`.claude/rules/tech-stack.md`).
- [ ] Migration cho toàn bộ 28 bảng theo đúng `docs/DATABASE-DICTIONARY.md`, gồm `branches`,
      `application_branch_histories`, `application_appointments` và các cột mới
      (`users.branch_id`, `jobs.owner_branch_id`, `applications.owner_branch_id` và các cột
      duplicate-review).
- [ ] Enum (PHP backed enum) cho mọi cột trạng thái, khớp transition matrix.
- [ ] Model + relationship khớp `docs/ERD.md`.
- [ ] Factory cho toàn bộ bảng, bao gồm bảng mới.
- [ ] Seeder (`branches`, `work_shifts`, `recruitment_sources`, `administrative_units` dữ liệu
      mẫu — cần ít nhất 2 cơ sở mẫu để test phân quyền theo cơ sở).
- [ ] Database test (foreign key, unique constraint, soft delete, transition matrix, duplicate
      contract, branch scoping).

**Điều kiện hoàn thành:**

```bash
php artisan migrate:fresh --seed
php artisan test
```

## Giai đoạn 2 — Job và website public

- [ ] Authentication admin/staff (`/hr/dang-nhap`), `users.branch_id` bắt buộc khi tạo staff.
- [ ] Company CRUD (`docs/ROUTE-MAP.md` phần "HR công ty").
- [ ] Location CRUD (`company_locations`), Contact CRUD (`company_contacts`).
- [ ] Branch CRUD (`docs/ROUTE-MAP.md` phần "HR danh mục" — `hr.branches.*`).
- [ ] Job CRUD, chọn `owner_branch_id`, điều kiện publish đầy đủ (Luồng 1).
- [ ] Publish/pause/close job, nhân bản job.
- [ ] Public listing (`/viec-lam`), tìm kiếm/lọc (Luồng 2).
- [ ] Job detail (`/viec-lam/{slug}`), CTA Gọi/Zalo ưu tiên contact cơ sở (Luồng 1, mục
      "Quy tắc contact CTA").
- [ ] Job verification (`job_verifications`, transaction "Verify job" trong
      `.claude/rules/data-model.md`).
- [ ] Form ứng tuyển guest (`ApplicationController@store`), toàn bộ transaction Luồng 3
      (`docs/CORE-FLOWS.md` mục 3), duplicate contract case A/B/C.

## Giai đoạn 3 — HR xử lý Application

- [ ] Danh sách hồ sơ theo cơ sở (Luồng 4), cột tối thiểu theo `docs/CORE-FLOWS.md` mục 4.
- [ ] Chi tiết hồ sơ, claim/assign trong phạm vi cơ sở.
- [ ] Contact Log (`application_contact_attempts`).
- [ ] Appointment — tạo lịch gọi lại/phỏng vấn, cập nhật kết quả (Luồng 5 mục 5.3).
- [ ] Đổi stage qua `ChangeApplicationStageAction`, validate transition matrix (Luồng 5).
- [ ] Close reason bắt buộc khi đóng hồ sơ.
- [ ] Chuyển cơ sở ngoại lệ (Luồng 6 mục 6.1), chỉ admin.
- [ ] Merge candidate + xử lý xung đột Application cùng job (Luồng 6 mục 6.3), chỉ admin.
- [ ] Lead request: form công khai + danh sách/chi tiết cho staff (chỉ xem/ghi nhận, không
      chuyển đổi — xem ADR-018).

## Giai đoạn 4 — Hoàn thiện

- [ ] Dashboard cơ bản (KPI đã chốt).
- [ ] Export CSV + `export_logs`.
- [ ] Security review (`.claude/rules/security-seo-testing.md`), bao gồm test 403 xem chéo
      cơ sở và test mass-assignment `owner_branch_id`/`stage`/`assigned_to`.
- [ ] SEO, Responsive.
- [ ] Feature test đầy đủ theo `docs/ACCEPTANCE-CRITERIA.md`.
- [ ] Backup.
- [ ] Cron/Scheduler xác nhận còn tuyển (`.claude/rules/roles-business-rules.md`).
- [ ] SSL, Log rotation.
- [ ] Deploy VPS, cấu hình path `/hr` theo `.claude/rules/tech-stack.md`.

## Candidate account — sau khi guest + HR ổn định

- [ ] Register/Login (`docs/ROUTE-MAP.md` phần "Candidate account").
- [ ] Profile, Favorites.
- [ ] Applied jobs (hiển thị rút gọn, không lộ pipeline nội bộ — `.claude/rules/scope-standards.md`).
- [ ] Link guest candidate vào tài khoản mới đăng ký (`candidates.user_id`).

## Phase 2 — ngoài phạm vi Phase 1 (không code, không thiết kế trước ở Phase 1)

- Lead từ điện thoại/tin nhắn Zalo trực tiếp; chuyển đổi Lead thành Application (ADR-018).
- Tích hợp Zalo API; tự động gọi/gửi tin nhắn.
- Phân công nhân viên tự động, round-robin.
- Candidate Account nâng cao.
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
