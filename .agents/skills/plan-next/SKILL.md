---
name: Plan Next
description: Chọn task nhỏ tiếp theo từ Project Status và Roadmap theo dependency, blocker và mức độ giá trị; không sửa code. Dùng trước một phiên coding mới hoặc khi chưa biết nên làm mục nào tiếp.
argument-hint: "[mục tiêu hoặc giai đoạn]"
disable-model-invocation: true
effort: medium
---

Lập kế hoạch task tiếp theo cho: **$ARGUMENTS**

1. Đọc `docs/PROJECT-STATUS.md`, `ROADMAP.md`, `docs/PHASE-1-SCOPE.md` và `git status --short`.
2. Không chọn task phụ thuộc vào phần chưa tồn tại hoặc blocker chưa giải quyết.
3. Ưu tiên vertical slice nhỏ nhất tạo ra bằng chứng chạy được; không ưu tiên UI trước data/authorization contract.
4. Trả tối đa 5 task theo thứ tự dependency. Mỗi task phải có:
   - mục tiêu một câu;
   - dependency;
   - 3–5 acceptance criteria;
   - context cần đọc;
   - lệnh skill chính xác để bắt đầu;
   - điều kiện `DONE`.
5. Đánh dấu duy nhất một task là `NEXT`.
6. Không edit file, không chạy migration, không commit/push.
