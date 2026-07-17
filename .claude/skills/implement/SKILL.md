---
name: implement
description: Triển khai một vertical slice nhỏ của vieclam88, đọc đúng context, viết test và tự xác minh.
argument-hint: "<kết quả cần đạt>"
disable-model-invocation: true
effort: medium
---

Triển khai: **$ARGUMENTS**

1. Đọc `docs/PROJECT-STATUS.md` và `docs/CONTEXT-MAP.md`.
2. Xác định tối đa 5 acceptance criteria; nêu blocker thật sự nếu có.
3. Tìm file liên quan, không quét toàn repo và không đọc tài liệu ngoài context map.
4. Thực hiện một vertical slice hoàn chỉnh; không refactor ngoài phạm vi.
5. Viết/cập nhật test trước hoặc cùng lúc với code.
6. Chạy test nhỏ nhất liên quan; sau đó chạy build hoặc suite rộng hơn nếu cần.
7. Báo cáo: file đổi, lệnh đã chạy, kết quả, phần chưa xong. Không commit/push.
