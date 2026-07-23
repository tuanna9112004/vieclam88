---
name: Implement Slice
description: Triển khai một feature vertical slice nhỏ đã rõ contract, từ authorization/validation đến domain action, UI cần thiết và test. Dùng khi task không thay đổi schema đáng kể.
argument-hint: "<kết quả nghiệp vụ cần đạt>"
disable-model-invocation: true
effort: high
---

Triển khai feature: **$ARGUMENTS**

1. Đọc `docs/PROJECT-STATUS.md`, chọn đúng context trong `docs/CONTEXT-MAP.md` và khóa tối đa 5 acceptance criteria.
2. Nếu task quá rộng, có mâu thuẫn nguồn hoặc cần schema chưa có, dừng và trả lệnh phù hợp `/vibe-task` hoặc `/db-task`; không tự mở rộng.
3. Nêu Task Contract: kết quả, out-of-scope, dependency, file dự kiến, test command.
4. Hoàn thành một slice: Request/Policy → Action/Service → Controller/Route → Blade khi cần → test.
5. Không đặt nghiệp vụ trong Controller/Blade; không tin field actor/branch/stage từ client.
6. Dùng transaction/history/lock đúng contract; không tạo abstraction hoặc Phase 2 dự phòng.
7. Chạy focused test, regression liên quan và build khi cần.
8. Báo `DONE`, `BLOCKED` hoặc `CHANGES REQUIRED`; không commit/push.
