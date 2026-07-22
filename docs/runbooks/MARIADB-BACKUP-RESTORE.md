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
- **Thư mục mặc định:** `storage/app/backups/`
- **Tự động sinh:** File `.sql` và file `.sql.gz` / `.sql.sha256`.
- **Tự động dọn dẹp:** Xóa các bản sao lưu cũ vượt quá 30 ngày.

### Cách 2: Sử dụng Command CLI Hệ thống
```bash
mariadb-dump --user=$DB_USERNAME --password=$DB_PASSWORD --host=$DB_HOST --port=$DB_PORT \
  --single-transaction --quick --quote-names --routines --triggers --events \
  --default-character-set=utf8mb4 $DB_DATABASE > backup_vieclam88_$(date +%Y%m%d_%H%M%S).sql

# Tạo SHA256 Checksum
sha256sum backup_vieclam88_*.sql > backup_vieclam88_*.sql.sha256

# Phân quyền 600
chmod 600 backup_vieclam88_*
```

---

## 4. Quy trình Khôi Phục Thử Nghiệm (Safe Restore Test)

### Bước 1: Pre-Restore Checklist (Kiểm tra trước khi Restore)
- [ ] Xác minh dung lượng file backup không bằng 0 bytes.
- [ ] Xác minh mã SHA256 checksum khớp 100% với file `.sha256`.
- [ ] Xác nhận tên CSDL đích là CSDL thử nghiệm độc lập (`vieclam88_restore_test`), KHÔNG trùng với CSDL Production.

### Bước 2: Thực hiện Restore Thử Nghiệm qua Artisan
```bash
php artisan db:restore-test storage/app/backups/backup_vieclam88_20260722_163000.sql --target-db=vieclam88_restore_test
```

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
