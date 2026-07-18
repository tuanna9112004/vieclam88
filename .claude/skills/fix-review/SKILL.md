---
name: Fix Review
description: Xác minh và sửa một nhóm finding đã được review, giữ phạm vi hẹp và bổ sung regression test. Dùng sau /review-changes khi người dùng đồng ý sửa các finding cụ thể.
argument-hint: "<finding hoặc mức ưu tiên cần sửa>"
disable-model-invocation: true
effort: high
---

Sửa review findings: **$ARGUMENTS**

1. Xác minh từng finding trên code hiện tại; không sửa finding đã lỗi thời hoặc không tái hiện được.
2. Chỉ xử lý finding được nêu. Nếu các finding độc lập, tối đa một nhóm cùng nguyên nhân gốc trong một lần.
3. Trước edit, ghi mapping: finding → root cause → test tái hiện → file dự kiến sửa.
4. Sửa nguyên nhân gốc, không chỉ che triệu chứng; không refactor ngoài phạm vi.
5. Bổ sung regression test cho Critical/High và mọi lỗi correctness/data/auth.
6. Chạy focused tests và regression liên quan.
7. Báo finding nào `FIXED`, `NOT REPRODUCIBLE`, `BLOCKED`; không tự commit/push.
