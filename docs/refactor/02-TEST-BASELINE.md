# Test Baseline có thể lặp lại (TASK 0.3)

> Quality gate trước khi bắt đầu bất kỳ batch migration Phase 2 nào (Phần 1 trở đi ở
> `docs/VIECLAM88_TASK_REGISTRY_V2.3.md`). Không tạo/sửa migration hay code nghiệp vụ ở task này —
> chỉ chạy và ghi nhận kết quả thật. Số liệu tổng (817/811/6) đã có ở
> [`00-CURRENT-BASELINE.md`](00-CURRENT-BASELINE.md) mục 5 (TASK 0.1, 2026-07-23); tài liệu này bổ
> sung: lệnh tái hiện chính xác, phân rã theo tầng (guard → integrity → full suite → build), và
> phân loại lỗi regression/môi trường theo yêu cầu riêng của TASK 0.3.

## 1. Môi trường chạy baseline

- Ngày chạy: 2026-07-24, HEAD `c4f5e23` (branch `main`, sau TASK 0.2).
- `.env.testing` đã tồn tại từ trước (không cần tạo mới): `APP_ENV=testing`,
  `DB_DATABASE=vieclam88_test` — khác database dev `vieclam88`, đúng yêu cầu `TestDatabaseGuard`.
- `PHP 8.4.23 (cli)`, `Laravel Framework 13.20.0`.
- Xác nhận lại bằng `which`: **không có** `mariadb-dump`/`mariadb`/`mysqldump`/`mysql` trên PATH
  của môi trường này (giống hiện trạng đã ghi ở `01-ROLLBACK-PLAN.md` mục 2, chưa đổi).

## 2. Lệnh tái hiện (theo đúng thứ tự)

```bash
php artisan test --filter=TestDatabaseGuard
php artisan test --filter=DatabaseIntegrityTest
php artisan test
npm run build
python scripts/check-claude-config.py
python scripts/check-claude-skills.py
```

## 3. Kết quả thật

| Bước | Lệnh | Kết quả |
|---|---|---|
| Guard tests | `--filter=TestDatabaseGuard` | `tests: 6, passed: 6, assertions: 7` — fail-closed guard (chặn `APP_ENV` khác `testing` và `DB_DATABASE` trùng/rỗng/bằng `vieclam88`) vẫn hoạt động đúng. |
| Database integrity | `--filter=DatabaseIntegrityTest` | `tests: 7, passed: 7, assertions: 46`. |
| Full suite | `php artisan test` | `tests: 817, passed: 811, assertions: 2372, failed: 2, errors: 4` (6 fail, 0 skip). |
| Frontend build | `npm run build` | PASS — `62 modules transformed`, build 388ms, không lỗi. |
| Claude config checker | `check-claude-config.py` | `OK: ... 0 warning(s)`. |
| Claude skills checker | `check-claude-skills.py` | `OK: 11 Claude skills passed with 0 warning(s)`. |

## 4. Phân loại 6 lỗi còn lại — môi trường, không phải regression

Toàn bộ 6 lỗi thuộc đúng 2 file, cùng nguyên nhân gốc:

| Test | Loại | Nguyên nhân |
|---|---|---|
| `DatabaseBackupContentTest::test_db_backup_produces_valid_gzip_containing_expected_sql_and_is_memory_bounded` | failure | Cần chạy `mariadb-dump` thật để tạo gzip — binary không có trên PATH. |
| `DatabaseBackupRestoreCommandTest::test_db_backup_command_creates_backup_sql_file_and_sha256_checksum` | failure | Như trên. |
| `DatabaseBackupContentTest::test_db_restore_test_command_restores_into_isolated_database_and_verifies` | error | Phụ thuộc file backup được tạo ở bước trên (không tồn tại vì bước trên fail trước) → `getPathname()` trên `false`. |
| `DatabaseBackupRestoreCommandTest::test_db_restore_test_rejects_target_matching_the_currently_connected_source_database` | error | Như trên. |
| `DatabaseBackupRestoreCommandTest::test_db_restore_test_rejects_target_with_unsafe_sql_characters` | error | Như trên. |
| `DatabaseBackupRestoreCommandTest::test_db_restore_test_rejects_target_missing_required_suffix_or_prefix` | error | Như trên. |

Kết luận: **thiếu dependency hệ thống** (`mariadb-dump`/`mariadb` không cài/không trên PATH của máy
lập baseline này), không phải lỗi logic hay regression từ thay đổi TASK 0.1/0.2. Không sửa test để
che lỗi này — giữ nguyên assertion thật theo `.claude/rules/testing.md`. Điều kiện gỡ blocker: cài
MariaDB client tools và thêm vào PATH trước khi chạy `db:backup`/`db:restore-test` thật hoặc trước
Batch 1 migration Phase 2 thật (đã ghi ở `01-ROLLBACK-PLAN.md` mục 2 — không lặp lại chi tiết ở
đây).

## 5. Điều kiện coi TASK 0.3 hoàn thành

- [x] Có lệnh tái hiện chính xác (mục 2).
- [x] Có số test pass/fail/skip chính xác từ lần chạy thật (mục 3: 811 pass / 6 fail / 0 skip /
      817 tổng).
- [x] Lỗi môi trường tách khỏi regression, có bảng phân loại + nguyên nhân gốc (mục 4).
- [x] Không dùng database thật/production — `.env.testing` trỏ `vieclam88_test`, guard fail-closed
      xác nhận bằng test thật.
- [x] Không test nào bị bỏ/disable để đạt PASS — 6 lỗi giữ nguyên trạng thái fail thật.
