# vieclam88 — Claude Code

## Mục tiêu

Laravel monolith cho công ty cung ứng lao động miền Bắc:
- Public: tìm việc, xem công ty, ứng tuyển guest.
- HR tại `/hr`: quản lý công ty, việc làm, ứng viên và quy trình xử lý.

## Stack cố định

PHP 8.4.x · Laravel 13.x · MariaDB · Blade · Bootstrap 5.3 · Alpine.js · Vite.
Không tự thêm framework/service mới. Mọi thay đổi kiến trúc phải ghi ADR trong `docs/DECISIONS.md`.

## Bất biến nghiệp vụ

6 luồng nghiệp vụ cốt lõi (nguồn sự thật, mọi thứ khác phải khớp): `docs/CORE-FLOWS.md`.

- `users` là tài khoản; `candidates` là hồ sơ; guest vẫn có thể ứng tuyển.
- Không nhận diện người chỉ bằng một số điện thoại; liên hệ nằm ở `candidate_contacts`.
- Một `job` là một đợt tuyển; không tạo trùng `(candidate_id, job_id)`.
- `applications` phải lưu snapshot và consent tại thời điểm gửi.
- `applications.owner_branch_id` copy từ `jobs.owner_branch_id` lúc tạo, không suy ra động; staff chỉ xem/xử lý Application thuộc cơ sở của mình, admin xem toàn bộ. Chuyển cơ sở là ngoại lệ có kiểm soát (lý do + lịch sử), không tạo Application mới.
- Pipeline, lần liên hệ, lịch hẹn và ghi chú là dữ liệu riêng; lịch sử chỉ thêm, không ghi đè.
- Không hard-delete dữ liệu tuyển dụng cốt lõi.
- Phase 1 không có Lead (mọi hình thức), không có phân công/claim hồ sơ cho nhân viên, không có Favorites — thuộc Phase 2. Không làm chức năng ngoài Phase 1 nếu chưa có ADR được chấp nhận.

## Cách làm việc

1. Đọc `docs/PROJECT-STATUS.md` và `docs/CONTEXT-MAP.md`; nếu task đụng tới Job/Application/cơ sở, đọc thêm `docs/CORE-FLOWS.md`.
2. Chỉ đọc tài liệu đúng loại task; không quét toàn bộ `docs/` hoặc toàn bộ ảnh.
3. Mỗi session xử lý một vertical slice nhỏ, có tiêu chí nghiệm thu rõ.
4. Sửa ít file nhất; không refactor ngoài phạm vi.
5. Chạy kiểm tra nhỏ nhất liên quan trước, toàn bộ suite sau khi slice ổn định.
6. Không tuyên bố hoàn thành nếu chưa chạy lệnh kiểm tra và đọc kết quả.
7. Không commit/push, migrate fresh, xóa file hoặc đổi schema khi chưa được yêu cầu rõ.

## Lệnh chuẩn

Khi Laravel đã tồn tại:
```bash
php artisan test --filter=<TestName>
php artisan test
npm run build
```

Tài liệu hiện tại:
```bash
python scripts/check-claude-config.py
```

## Workflow nhanh

- `/implement <kết quả cần đạt>`: triển khai một slice có test.
- `/db-task <thay đổi dữ liệu>`: làm task database theo dictionary/ERD.
- `/review-changes`: review diff trong context riêng.
- `/handoff`: cập nhật trạng thái cuối session.

## Compact Instructions

Giữ lại khi compact: mục tiêu hiện tại, acceptance criteria, file đã đổi, lệnh đã chạy và kết quả, lỗi còn lại, bước tiếp theo. Bỏ output dài, kế hoạch cũ và mô tả file lặp lại.
