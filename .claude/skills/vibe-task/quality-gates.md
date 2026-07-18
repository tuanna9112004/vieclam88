# Quality Gates

## Mọi task

- Scope đúng Phase 1; không có field/route/UI dự phòng.
- Không bỏ qua Policy, server-side validation hoặc database constraint quan trọng.
- Không để logic nghiệp vụ trong Controller/Blade.
- Error path có test hoặc được ghi rõ lý do chưa test.
- Không sửa file ngoài Task Contract nếu không có dependency bắt buộc.

## Task ghi dữ liệu

- Transaction boundary rõ.
- Row/named lock đúng contract khi có race condition.
- History/audit được ghi trong cùng transaction khi bắt buộc.
- Rollback không để dữ liệu nửa chừng.
- Client không điều khiển actor, owner branch, stage hoặc field nhạy cảm.

## Task phân quyền

- Happy path đúng role/branch.
- Cross-branch/direct URL bị chặn ở backend.
- Admin exception được test riêng.
- User inactive/soft-deleted entity không vượt quyền.

## Task public/UI

- Chỉ dữ liệu public hợp lệ được render.
- Không lộ Company Contact/PII nội bộ.
- Escape output; form có CSRF và validation feedback.
- Mobile không vỡ ở viewport nhỏ.
- Không tạo N+1 rõ ràng.

## Bằng chứng tối thiểu

- Command đã chạy và exit status.
- Tên test/build đã pass.
- `git diff --stat` và danh sách file đổi.
- Gate chưa chạy phải được ghi là chưa xác minh, không được suy đoán pass.
