# Runbook Sao Lưu (Backup) & Khôi Phục (Restore) MariaDB/MySQL — Vieclam88

## 1. Tổng quan & Mục tiêu
Runbook này quy định quy trình vận hành tiêu chuẩn cho việc sao lưu (Backup), xác minh tính toàn vẹn (Checksum Verification), quản lý thời hạn lưu trữ (Retention Policy), và khôi phục thử nghiệm (Restore Test) cho CSDL MariaDB / MySQL thuộc dự án Vieclam88.

---

## 2. Các nguyên tắc an toàn bắt buộc (Safety Invariants)
- **Cấm lưu mật khẩu trong git repository:** Mật khẩu và credential CSDL lấy từ biến môi trường ngoài repo (`.env` / Secret Manager / Environment Variables). Không lưu cứng (hardcode) mật khẩu trong file script hoặc markdown.
- **Phân quyền truy cập file dump (`chmod 0600`):** File backup và checksum chỉ cấp quyền cho tài khoản dịch vụ hệ thống sở hữu (`chmod 0600`).
- **Mã hóa & Checksum:** Mọi file sao lưu đều phải sinh file SHA256 checksum (`.sha256`) tương ứng để xác minh trước khi sử dụng.
- **Cấm Restore trực tiếp lên Production:** Thao tác khôi phục thử nghiệm (Restore Test) **BẮT BUỘC** thực hiện trên một CSDL thử nghiệm độc lập (vd: `vieclam88_restore_test`). Tuyệt đối không được thực hiện trên database đang phục vụ khách hàng khi chưa có lệnh chuyển đổi chính thức.

---

## 3. Quy trình Sao Lưu (Backup Process)

### Cách 1: Sử dụng Lệnh Artisan (Khuyên dùng)
```bash
php artisan db:backup --retention=30
```
- **Engine thật:** `mariadb-dump --single-transaction --quick --routines --triggers` (không tự ghép SQL bằng
  `DB::table()->get()`), chạy qua `proc_open` với stdout được hệ điều hành redirect thẳng ra file — PHP không
  giữ toàn bộ nội dung dump trong bộ nhớ.
- **Nén streaming:** File plain SQL được nén sang `.sql.gz` theo từng chunk 256KB (`gzopen`/`gzwrite`), bộ nhớ
  không phụ thuộc kích thước CSDL.
- **Credential:** không truyền qua CLI argument/env; ghi vào `--defaults-extra-file` tạm (`chmod 0600`), xóa
  ngay trong `finally` sau khi chạy xong — không log password/DSN.
- **Thư mục mặc định:** `storage/app/backups/` (`chmod 0700`).
- **Tên file:** `vieclam88_backup_{YYYYMMDD_HHmmss}.sql.gz` + sidecar `.sql.gz.sha256`, cả hai `chmod 0600`.
- **Binary path:** mặc định giả định `mariadb-dump`/`mariadb` có trong PATH server; override qua env
  `MARIADB_DUMP_BINARY` / `MARIADB_CLIENT_BINARY` khi cần (`config/database.php` mục `backup`).
- **Tự động dọn dẹp (retention):** chỉ xóa đúng file khớp prefix `vieclam88_backup_` + đuôi `.sql.gz` hoặc
  `.sql.gz.sha256` và quá hạn `--retention` ngày; không đụng file khác trong thư mục.

---

## 4. Quy trình Khôi Phục Thử Nghiệm (Safe Restore Test)

### Bước 1: Pre-Restore Checklist (Kiểm tra trước khi Restore)
- [ ] Xác minh dung lượng file backup không bằng 0 bytes.
- [ ] Xác minh mã SHA256 checksum khớp 100% với file `.sha256`.
- [ ] Xác nhận tên CSDL đích là CSDL thử nghiệm độc lập (`vieclam88_restore_test`), KHÔNG trùng với CSDL Production.

**Guard tự động (fail-closed) trong lệnh `db:restore-test`:**
- `--target-db` bắt buộc khớp regex identifier an toàn (chữ/số/gạch dưới, tối đa 64 ký tự) và phải có suffix
  `_restore_test` hoặc prefix `vieclam88_restore_test_`.
- Lệnh tự động thu thập tên CSDL từ mọi connection đã cấu hình + connection đang hoạt động; nếu `--target-db`
  trùng bất kỳ CSDL nào trong số đó (nguồn/dev/test/staging/production), lệnh từ chối trước khi chạm tới
  `CREATE DATABASE`.
- Nếu `APP_ENV=production`, lệnh từ chối hoàn toàn, không có cách bypass.
- Khi bị chặn, lệnh trả `Command::FAILURE` (exit code 1) và không có `CREATE`/`DROP DATABASE` nào được thực thi.

### Bước 2: Thực hiện Restore Thử Nghiệm qua Artisan
```bash
php artisan db:restore-test storage/app/backups/vieclam88_backup_20260722_163000.sql.gz --target-db=vieclam88_restore_test
```
- **Engine thật:** file `.sql.gz` được đọc theo chunk 256KB (`gzread`) và feed trực tiếp vào stdin của tiến
  trình `mariadb` client (không tự parse/tách câu lệnh SQL bằng PHP như trước) — xử lý đúng procedure/trigger
  multi-statement mà cách tách chuỗi thủ công không đảm bảo được.
- stdout/stderr của `mariadb` client được hệ điều hành redirect thẳng ra file tạm, không cần đọc đồng thời nên
  không có rủi ro deadlock pipe.
- Credential dùng `--defaults-extra-file` tạm giống quy trình backup, xóa trong `finally`.

### Bước 3: Post-Restore Checklist (Kiểm tra sau khi Restore)
- [ ] Đủ 26 bảng CSDL theo thiết kế schema.
- [ ] Kiểm tra đếm số lượng bản ghi trên các bảng chính (`users`, `jobs`, `applications`, `candidates`).
- [ ] Đã dọn dẹp CSDL thử nghiệm `vieclam88_restore_test` sau khi kiểm tra xong.

---

## 5. Chính sách Lưu Trữ (Retention Policy)
| Loại Backup | Tần Suất | Thời Gian Lưu Trữ (Retention) | Nơi Lưu Trữ |
|---|---|---|---|
| Daily Backup | Hàng ngày (02:00 AM) | 30 ngày | Local Server + Offsite S3 Bucket |
| Monthly Backup | Ngày đầu tháng (03:00 AM) | 12 tháng | Encrypted Cold Storage |
