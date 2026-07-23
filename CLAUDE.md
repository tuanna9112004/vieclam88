# vieclam88 — Claude Code

ƯU TIÊN TRẢ LỜI BẰNG TIẾNG VIỆT sau khi hoàn thành prompt
## Mục tiêu và stack

Laravel monolith cho công ty cung ứng lao động miền Bắc: website public và HR tại `/hr`.
Stack cố định: PHP 8.4.x, Laravel 13.x, MariaDB 11.4 LTS, Blade, Bootstrap 5.3, Alpine.js, Vite. Không tự thêm framework/service; thay đổi kiến trúc phải có ADR.

## Phạm vi đã khóa

Phase 1 theo `docs/PHASE-1-SCOPE.md`; nội dung không được nêu mặc định chuyển `docs/PHASE-2-BACKLOG.md`. Không xây Candidate Account, Lead, Favorites, assignment/claim, Zalo API, CTV/hoa hồng, import hàng loạt, AI matching hoặc BI nâng cao.

## Bất biến toàn cục

- `users` chỉ có `staff`/`admin`; ứng viên là `candidates` và luôn ứng tuyển guest.
- `branches` là cơ sở nội bộ; `company_locations` là địa điểm của khách hàng.
- Job/Application sở hữu cơ sở bằng `owner_branch_id`; Staff chỉ truy cập dữ liệu đúng cơ sở, Admin không giới hạn.
- Job/Application đổi trạng thái qua domain Action, có transaction/history; không cập nhật trực tiếp từ Controller.
- Application dùng `submission_token` idempotent, lưu snapshot/consent; duplicate/merge phải tuân thủ merged-family contract.
- Không hard-delete dữ liệu tuyển dụng cốt lõi; constraint quan trọng phải có DB constraint và test.
- Không tạo schema, route hoặc UI dự phòng cho Phase 2.

## Nguồn sự thật

- Điều hướng tài liệu: `docs/INDEX.md`, `docs/CONTEXT-MAP.md`.
- Tiến độ: `docs/PROJECT-STATUS.md`.
- Nghiệp vụ: `docs/CORE-FLOWS.md`; schema: `docs/DATABASE-DICTIONARY.md`; quan hệ: `docs/ERD.md`.
- Route: `docs/ROUTE-MAP.md`; nghiệm thu: `docs/ACCEPTANCE-CRITERIA.md`; ADR: `docs/decisions/INDEX.md`.
- Khi nguồn mâu thuẫn: dừng phần liên quan, ghi blocker; không tự chọn một phương án.

## Quy trình mỗi task

1. Đọc `docs/PROJECT-STATUS.md`, sau đó dùng `docs/CONTEXT-MAP.md` để chọn context tối thiểu.
2. Xác định một vertical slice nhỏ và acceptance criteria cụ thể.
3. Sửa ít file nhất; không refactor hoặc mở rộng phạm vi ngoài task.
4. Viết/cập nhật test cùng thay đổi; chạy focused test trước, suite/build sau.
5. Báo cáo file đổi, lệnh đã chạy, kết quả và phần còn lại. Không tuyên bố hoàn thành khi chưa kiểm chứng.

## An toàn thao tác

Không commit/push, `migrate:fresh`, rollback, xóa file hoặc đổi schema ngoài phạm vi khi chưa được yêu cầu rõ. Không đọc `.env` hoặc dữ liệu private.

## Lệnh chuẩn

```bash
python scripts/check-claude-config.py
php artisan test --filter=<TestName>
php artisan test
npm run build
```

## Skills

`/vibe-task` là điểm vào mặc định; danh mục đầy đủ tại `docs/CLAUDE-SKILLS.md`. Skills nằm trong `.claude/skills/`.

## Lịch sử hoàn thành (rút gọn)

- **GD 9-10 & Core Admin** (PASS 700/700): Transfer Application Branch, Candidate Duplicate Review/Merge/Anonymize, CSV Export (`export_logs`), Staff/Admin Dashboard (11 KPI), Database Baseline Audit.
- **Remediation R01-R04** (PASS 708/708, commit `1faace1`/`6ce701d`/`4d3fc5a`): seeder production-safe, `db:restore-test` fail-closed, MariaDB backup/restore qua `mariadb-dump`/`mariadb` thật, fix Job JSON-LD XSS.
- **Administrative Unit UI, Job duplicate/delete/restore, Pages/FAQ** (PASS 808/814, commit `b6bddf8` đã push): upsert ADR-070 chặn self/direct/deep cycle, Job duplicate không copy verification/history/Application, đóng gap Route Map (`pages`/`faqs` migration 027/028, `hr.settings` allowlist `PhaseOneSettingCatalog`).

## Redesign giao diện Public + HR sidebar (commit `3802bc2` đã push; phần sau đây **CHƯA COMMIT**)

- Public: `layouts/public.blade.php` (header sticky/offcanvas/footer), `.public-shell` design system, job card dùng chung (`public/jobs/_card.blade.php`), hero pill-search + minh họa CSS-only (không ảnh đối thủ).
- Multi-select chip filter (`administrative_unit_id`/`salary`/`industrial_park_id`/`work_shift_id`, Alpine.js, component `components/multi-select.blade.php`); `Job::scopeIn*`/`scopeSalaryBucket`/`scopeWithWorkShift` nhận `int|array`; `JobIndexRequest::prepareForValidation()` chuẩn hoá scalar→array, tương thích ngược.
- HR sidebar: `layouts/hr.blade.php` + `.hr-shell` — đã chuyển toàn bộ 28 view HR (trừ `auth/login`, `auth/password-change` vì chưa qua `EnsurePasswordChanged`).
- Fix: `.job-card` thiếu `position:relative` khiến `stretched-link` phủ toàn trang; DB dev thiếu migrate `pages`/`faqs` (đã `php artisan migrate`, không phải `migrate:fresh`).
- Blocker đã xử lý: `resources/banner.png` là banner quảng cáo thật của đối thủ (viec3mien, có logo/copy của họ) — không dùng trực tiếp theo `ui-guidelines.md`, đã tự vẽ minh họa CSS thay thế; file gốc chưa xoá.
- Tài khoản test (dev only, không phải production): `admin.test@vieclam88.local` / `staff.test@vieclam88.local`, mật khẩu `Vieclam88Test2026`, bắt buộc đổi mật khẩu lần đầu (ADR-067).
- Test: 430/430 HR, 91/91 Public, full suite 808/814 (6 lỗi env `DatabaseBackup*`/`DatabaseRestoreTest*` do thiếu binary `mariadb-dump`/`mariadb`, không phải regression).

## Compact

Giữ mục tiêu, acceptance criteria, file đổi, lệnh/kết quả, blocker và tối đa 3 bước tiếp theo; bỏ output dài và kế hoạch cũ.

**Bước tiếp theo:**
1. Redesign giao diện Dashboard HR (đã yêu cầu, chưa bắt đầu).
2. Review + commit phần HR sidebar/job card/hero/multi-select đang uncommitted (`git status`).
3. Quyết định giữ hay xoá `resources/banner.png` (ảnh đối thủ chưa dùng).
