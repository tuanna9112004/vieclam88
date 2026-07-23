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

## Tiến độ hoàn thành Giai đoạn 9 - 10 & Core Admin Operations (PASS 700/700 tests)

- **Transfer Application Branch (Luồng 6.1):** `TransferApplicationBranchAction` (Admin-only, lockForUpdate, Branch history, chuyển quyền tức thì).
- **Candidate Duplicate Review (Luồng 6.2.2):** `ResolveCandidateDuplicateReviewAction` & `hr.duplicate-reviews` (Admin-only, nguồn sự thật server-side, không tự merge, đồng bộ `needs_duplicate_review`).
- **Candidate Merge (Luồng 6.3):** `MergeCandidateAction` (Admin-only, root resolution, chống self/cycle merge, khóa source candidate, giữ lịch sử, close duplicate application).
- **Candidate Anonymization (ADR-056):** `AnonymizeCandidateAction` (Admin-only, PII contract: mask candidates, contacts, 6 cột application PII, khóa merge/edit/reopen, giữ audit metadata).
- **CSV Export & Audit Log (Mục 9):** `ExportApplicationsCsvAction` & `hr.applications.export` (Stream CSV dòng theo dòng, `CsvSanitizer` chống Formula Injection, Staff Branch isolation, ghi `export_logs`).
- **Staff & Admin Dashboard Phase 1 (Mục 9.1 / ADR-058):** `GetDashboardStatsAction` & `GetAdminDashboardStatsAction` & `hr.dashboard` (11 thẻ KPI cards có link filter, Tỷ lệ chuyển đổi % Application->Started, bộ lọc Khoảng ngày/Cơ sở/Công ty/Job, Top Jobs & Companies breakdown, đếm chuẩn sau transfer/merge/duplicate).
- **Database Baseline Audit (Mục DB):** `DatabaseIntegrityTest` (Audit 26 bảng schema, FK `restrictOnDelete`, unique `(candidate_id, job_id)`, append-only history models, rollback & migrate lifecycle, production seed `DemoSeeder`).

## Remediation Playbook GD 0-11 — R01/R02/R03/R04 DONE (PASS 708/708 tests)

- **R01 — Production-safe DatabaseSeeder:** `DatabaseSeeder` mặc định chỉ seed danh mục (WorkShift/RecruitmentSource/Setting), không còn `User::factory()` tạo `test@example.com`. `DemoSeeder` fail-closed ngoài `local`/`testing`. Admin production vẫn tạo qua `php artisan app:create-admin`.
- **R02 — db:restore-test fail-closed:** `DatabaseRestoreTestCommand` validate `--target-db` bằng regex identifier an toàn, bắt buộc suffix/prefix `_restore_test`, so khớp mọi CSDL đã cấu hình (chặn trùng nguồn/dev/test/staging/production), fail-closed tuyệt đối khi `APP_ENV=production`, cleanup qua try/finally chỉ đụng đúng DB target đã xác minh.
- **R03 — MariaDB backup/restore production-safe:** `DatabaseBackupCommand`/`DatabaseRestoreTestCommand` đổi engine sang `mariadb-dump`/`mariadb` client thật qua `proc_open` (không còn tự ghép SQL bằng `DB::table()->get()`), stream/nén gzip theo chunk 256KB (bộ nhớ không phụ thuộc kích thước DB), credential qua `defaults-extra-file` tạm (chmod 0600, không log), filename `vieclam88_backup_*.sql.gz` (chmod 0600/0700), retention chỉ xóa đúng file khớp pattern. Runbook `MARIADB-BACKUP-RESTORE.md` đã đồng bộ.
- **R04 — Public Job JSON-LD XSS:** thêm `JSON_HEX_TAG/AMP/APOS/QUOT` vào `json_encode` structured data trang `jobs.show`, chặn payload `</script>` trong title/description/company đóng sớm thẻ script; giữ Unicode/dấu tiếng Việt đúng.
- Commit: `1faace1` (R01), `6ce701d` (R02+R03), `4d3fc5a` (R04).

## Compact

Giữ mục tiêu, acceptance criteria, file đổi, lệnh/kết quả, blocker và tối đa 3 bước tiếp theo; bỏ output dài và kế hoạch cũ.
