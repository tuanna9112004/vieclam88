# Rollback Plan (TASK 0.1)

> Mô tả cách sao lưu và phục hồi source/DB/storage **trước khi** bắt đầu bất kỳ batch migration
> nào trong `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`. Không tạo migration/code mới ở tài liệu này —
> chỉ mô tả quy trình dùng công cụ đã có sẵn trong repo.

## 1. Backup source code (Git)

- HEAD hiện tại: `97e97ae6b42d03f1bd901f9ce641ba7008c0a783` (branch `main`).
- Trước khi bắt đầu Batch 1 (theo GĐ 2 của tài liệu PDF gốc — "Tạo nhánh tái cấu trúc"), phải:
  1. Commit hoặc stash toàn bộ thay đổi đang dang dở (xem `git status` — hiện có thay đổi
     ADR-079 + `PHASE-2-ARCHITECTURE-PROPOSAL.md` chưa commit).
  2. Tạo branch riêng cho migration (không sửa `main`/production trực tiếp), ví dụ
     `refactor/phase-2-batch-1`.
  3. Đảm bảo remote đã nhận được tag/commit tham chiếu trước khi migrate (rollback code = revert
     merge hoặc checkout lại tag baseline).
- Rollback code: `git revert`/`git checkout <tag-baseline>` — không dùng `git reset --hard` trên
  branch đã chia sẻ; không force-push.

## 2. Backup database (MariaDB)

Cơ chế đã có sẵn, không cần viết mới:

- `php artisan db:backup` ([`app/Console/Commands/DatabaseBackupCommand.php`](../../app/Console/Commands/DatabaseBackupCommand.php)):
  chạy `mariadb-dump --single-transaction --quick --routines --triggers`, nén gzip streaming
  (không giữ toàn bộ dump trong RAM), kèm checksum SHA256, áp dụng retention (mặc định 30 ngày),
  lưu tại `storage/app/backups` (quyền 0600/0700).
- `php artisan db:restore-test` ([`app/Console/Commands/DatabaseRestoreTestCommand.php`](../../app/Console/Commands/DatabaseRestoreTestCommand.php)):
  phục hồi file backup vào **database cô lập riêng** để xác minh (không ghi đè DB đang chạy),
  từ chối target trùng DB nguồn hoặc tên không an toàn.

**Gap đã xác nhận ở môi trường lập baseline này:** `mariadb-dump`/`mariadb` **không có** trên
PATH (`which` không tìm thấy) → 6 test của 2 command trên đang fail
(`DatabaseBackupContentTest`, `DatabaseBackupRestoreCommandTest`) vì thiếu binary, không phải lỗi
logic. **Điều kiện bắt buộc trước khi chạy bất kỳ batch migration thật nào:** xác nhận
`mariadb-dump`/`mariadb` có sẵn trên PATH của môi trường thực thi migration, sau đó chạy lại
`php artisan test --filter=DatabaseBackup` và `php artisan db:backup` thật để xác nhận backup +
restore-test thành công end-to-end trước Batch 1.

## 3. Backup storage/uploads

- Disk mặc định: `local` (`storage/app/private`); disk `public` (`storage/app/public`) — kiểm tra
  tại thời điểm lập baseline: **không có file nghiệp vụ nào** trong `storage/app/public` (Phase 1
  chưa có tính năng upload — `candidate_documents`/`job_images` là bảng target Phase 2, chưa tồn
  tại). Vì vậy hiện tại **không có dữ liệu upload cần backup riêng** ngoài phần đã nằm trong DB.
- Khi Batch 6 (`job_images`, `candidate_documents`) triển khai và có file upload thật, rollback
  plan này phải cập nhật thêm bước sao lưu thư mục storage tương ứng — chưa cần ở task này.

## 4. Quy trình phục hồi khi migration lỗi

Theo kỷ luật Expand → Backfill → Switch → Contract đã chốt ở
`docs/PHASE-2-ARCHITECTURE-PROPOSAL.md` mục "Kế hoạch migration":

1. Mỗi batch chỉ **thêm** bảng/cột mới (Expand), không xóa/sửa cấu trúc cũ — code cũ tiếp tục
   chạy được nếu migration mới bị revert.
2. Rollback một batch = chạy `php artisan migrate:rollback --step=<n>` cho đúng migration của
   batch đó (chưa có batch nào được viết tại thời điểm lập baseline này), cộng với `git checkout`
   lại code trước batch.
3. Không batch nào được xóa bảng/cột cũ (Contract, batch 9) trước khi Switch (batch 8) đã chạy ổn
   định và có theo dõi — nghĩa là trong toàn bộ batch 1–8, dữ liệu cũ vẫn còn nguyên, rollback
   không mất dữ liệu.
4. Trước mỗi lần chạy migration thật trên dữ liệu có giá trị (staging có seed thật, hoặc sau này
   production): bắt buộc chạy `php artisan db:backup` thành công và xác minh
   `php artisan db:restore-test` phục hồi được, đúng nguyên tắc checkpoint "Khôi phục thử backup
   thành công" của tài liệu gốc.

## 5. Điều kiện coi TASK 0.1 hoàn thành

- [x] Có snapshot bảng/route/workflow/test hiện tại — [`00-CURRENT-BASELINE.md`](00-CURRENT-BASELINE.md).
- [x] Có quy trình backup/restore source-DB-storage bằng công cụ hiện có (tài liệu này).
- [ ] **Chưa xác minh được** `db:backup`/`db:restore-test` chạy thật thành công ở môi trường này
      (thiếu binary `mariadb-dump`/`mariadb`) — ghi nhận là điều kiện phải làm trước khi bắt đầu
      Batch 1 thật, không chặn việc lập baseline (chỉ là ghi chép/kiểm kê).
