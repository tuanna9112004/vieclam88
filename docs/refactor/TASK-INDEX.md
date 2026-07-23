# Task Index — Refactor Playbook

Tra cứu task theo mã `TASK x.y`. Chỉ khai báo task đã có file chi tiết trong
`docs/refactor/tasks/`; các task còn lại của playbook (43 task, Phần 0–13 theo
`docs/VIECLAM88_15_KE_HOACH_SUA_SLASH_COMMANDS_V2.1_TOI_UU.pdf`) **chưa được khai báo** — không tự
suy diễn nội dung khi chưa có file `TASK-x.y.md` tương ứng.

| Mã task | Tên task | Command chính | Dependency | File task |
|---|---|---|---|---|
| TASK 0.1 | Lập baseline kỹ thuật trước khi sửa | `/implement` | Không (task đầu tiên) | [`tasks/TASK-0.1.md`](tasks/TASK-0.1.md) |

## Quy tắc dùng bảng này

- Không đọc hoặc triển khai task khác ngoài dependency trực tiếp ghi ở cột "Dependency".
- Không bắt đầu một `TASK x.y` khi dependency của nó chưa `DONE` theo `docs/PROJECT-STATUS.md`.
- Khi cần khai báo task mới, thêm đúng một dòng vào bảng trên và tạo file tương ứng trong
  `docs/refactor/tasks/` theo cùng cấu trúc với `TASK-0.1.md`.
