# Roadmap — vieclam88

Lộ trình 8 giai đoạn. Trạng thái tiến độ chi tiết nhất luôn nằm ở `docs/PROJECT-STATUS.md` —
file này chỉ giữ checklist theo giai đoạn, cập nhật `[x]` khi hoàn thành, không viết thêm ghi
chú tiến độ dài ở đây (tránh trùng với `docs/PROJECT-STATUS.md`).

## Giai đoạn 0 — Chuẩn hóa tài liệu và môi trường

- [x] Audit repository.
- [x] Chuẩn hóa tài liệu (`CLAUDE.md`, `.claude/rules/*.md`, `docs/*.md`).
- [ ] Review và chốt ERD (`docs/ERD.md`).
- [ ] Review và chốt database dictionary (`docs/DATABASE-DICTIONARY.md`).
- [ ] Review và chốt foreign key, check constraint và delete policy.
- [ ] Review và chốt index, unique và quy tắc primary duy nhất.
- [ ] Cài PHP 8.4.
- [ ] Kiểm tra Composer.
- [ ] Kiểm tra Node LTS.
- [ ] Kiểm tra MariaDB.
- [ ] Xác nhận các enum đánh dấu **[đề xuất]** trong `docs/DATABASE-DICTIONARY.md`
      (`jobs.employment_type`, `jobs.close_reason`, `pages.status`, `settings.type`,
      `company_contacts.status`).

Chưa tạo mã nguồn trong giai đoạn này.

## Giai đoạn 1 — Database foundation

- [ ] Khởi tạo Laravel 13.x project, PHP 8.4.x (`.claude/rules/tech-stack.md`).
- [ ] Migration cho 25 bảng theo đúng `docs/DATABASE-DICTIONARY.md`.
- [ ] Enum (PHP backed enum) cho mọi cột trạng thái.
- [ ] Model + relationship khớp `docs/ERD.md`.
- [ ] Factory.
- [ ] Seeder (`work_shifts`, `recruitment_sources`, `administrative_units` dữ liệu mẫu).
- [ ] Database test (foreign key, unique constraint, soft delete).

**Điều kiện hoàn thành:**

```bash
php artisan migrate:fresh --seed
php artisan test
```

## Giai đoạn 2 — Company và job

- [ ] Authentication admin/staff (`/hr/dang-nhap`).
- [ ] Company CRUD (`docs/ROUTE-MAP.md` phần "HR công ty").
- [ ] Location CRUD (`company_locations`).
- [ ] Contact CRUD (`company_contacts`).
- [ ] Job CRUD (`docs/ROUTE-MAP.md` phần "HR việc làm").
- [ ] Publish/pause/close job, nhân bản job.
- [ ] Public listing (`/viec-lam`).
- [ ] Job detail (`/viec-lam/{slug}`).
- [ ] Job verification (`job_verifications`, transaction "Verify job" trong `.claude/rules/data-model.md`).

## Giai đoạn 3 — Candidate, application và lead

- [ ] Candidate matching (phát hiện trùng theo `candidate_contacts` + họ tên + ngày sinh).
- [ ] Contact normalization (`normalized_value`).
- [ ] Guest application (transaction "Apply" trong `.claude/rules/data-model.md`).
- [ ] Snapshot (`submission_snapshot`, `job_snapshot`).
- [ ] Consent (`consent_version`, `consent_text_hash`, `consented_at`, `consent_ip`).
- [ ] Lead request (`/lien-he/tu-van`).
- [ ] Duplicate prevention (unique `candidate_id + job_id`).
- [ ] Merge candidate (transaction "Merge candidate" trong `.claude/rules/data-model.md`).

## Giai đoạn 4 — HR workflow

- [ ] Application list + filter (`docs/ROUTE-MAP.md` phần "HR hồ sơ và lead").
- [ ] Assignment (tự nhận + admin gán lại, transaction "Assign" trong `.claude/rules/data-model.md`).
- [ ] Stage (đổi giai đoạn, transaction "Change stage" trong `.claude/rules/data-model.md`).
- [ ] History (status, assignment, contact attempts).
- [ ] Contact attempts.
- [ ] Notes (`application_notes`, không public — `.claude/rules/hr-admin.md`).
- [ ] CSV export + `export_logs` (`.claude/rules/hr-admin.md`).
- [ ] Dashboard cơ bản (KPI phải được chốt trước khi code — xem `.claude/rules/hr-admin.md`).

## Giai đoạn 5 — Public site

- [ ] Home (`.claude/rules/public-site.md`).
- [ ] Search + filter (`.claude/rules/scope-standards.md`).
- [ ] Company pages.
- [ ] Industrial park pages.
- [ ] FAQ.
- [ ] Contact + lead form.
- [ ] SEO (`.claude/rules/security-seo-testing.md`).
- [ ] Responsive.

## Giai đoạn 6 — Candidate account

- [ ] Register/Login (`docs/ROUTE-MAP.md` phần "Candidate account").
- [ ] Profile.
- [ ] Favorites.
- [ ] Applied jobs (hiển thị rút gọn, không lộ pipeline nội bộ — `.claude/rules/scope-standards.md`).
- [ ] Link guest candidate vào tài khoản mới đăng ký (`candidates.user_id`).

## Giai đoạn 7 — Test và deploy

- [ ] Feature test đầy đủ theo `docs/ACCEPTANCE-CRITERIA.md`.
- [ ] Security review (`.claude/rules/security-seo-testing.md`).
- [ ] SEO review (`.claude/rules/security-seo-testing.md`).
- [ ] Responsive review.
- [ ] Backup.
- [ ] Cron/Scheduler (xác nhận còn tuyển — `.claude/rules/roles-business-rules.md`).
- [ ] SSL.
- [ ] Log rotation.
- [ ] Deploy VPS, cấu hình path `/hr` theo `.claude/rules/tech-stack.md`.

## Ghi chú áp dụng lộ trình

- Có phụ thuộc thứ tự: Giai đoạn 1 (schema) phải xong trước 2–6; Giai đoạn 2 (company/job)
  nên xong trước Giai đoạn 3 (application cần job tồn tại); Giai đoạn 4 (HR workflow) cần
  Giai đoạn 3 xong (cần có application để xử lý).
- Mỗi giai đoạn nên là 1 hoặc vài session riêng.
- Cập nhật `docs/PROJECT-STATUS.md` sau khi hoàn thành mỗi giai đoạn, theo quy trình ở
  `.claude/skills/handoff/SKILL.md`.
- Không mở rộng sang các hạng mục "Ngoài phạm vi"
  (`.claude/rules/scope-standards.md`) trong lộ trình này.
