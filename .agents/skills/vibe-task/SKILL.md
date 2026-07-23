---
name: Vibe Task
description: Thực hiện một mục coding nhỏ của vieclam88 bằng cách tự phân loại task, nạp context tối thiểu, khóa phạm vi, triển khai một vertical slice và xác minh bằng test. Dùng khi người dùng yêu cầu code, sửa lỗi hoặc hoàn thiện một mục cụ thể.
argument-hint: "<mục nhỏ hoặc kết quả cần đạt>"
disable-model-invocation: true
effort: high
---

Thực hiện task: **$ARGUMENTS**

## 1. Phân loại trước khi sửa

1. Đọc `docs/PROJECT-STATUS.md` và đúng hàng trong `docs/CONTEXT-MAP.md`.
2. Dùng [task-router.md](task-router.md) để xác định một loại: schema, feature, bug, test, docs hoặc review.
3. Kiểm tra task thuộc Phase 1, dependency đã có và working tree không chứa thay đổi xung đột.
4. Nếu yêu cầu chứa nhiều vertical slice, **không code**; chia nhỏ và trả lệnh `/vibe-task` cho slice đầu tiên.
5. Nếu tài liệu nguồn mâu thuẫn hoặc thiếu quyết định ảnh hưởng schema/authorization, trả `BLOCKED`; không tự chọn phương án.

## 2. Khóa Task Contract

Trước khi edit, nêu ngắn gọn:

- Kết quả duy nhất phải đạt.
- In scope / out of scope.
- Tối đa 5 acceptance criteria.
- Dependency và rủi ro chính.
- File dự kiến sửa và lệnh xác minh.

Không triển khai cho đến khi Task Contract đủ rõ từ tài liệu hiện có. Không hỏi lại khi có thể suy ra chắc chắn từ nguồn sự thật.

## 3. Thực thi một vertical slice

- Sửa ít file nhất nhưng hoàn thành xuyên suốt lớp cần thiết: schema/model → request/policy → action/service → controller/route → Blade → test.
- Không bắt buộc mọi lớp nếu task không cần; không tạo abstraction, base class, factory hoặc schema dự phòng.
- Business logic không nằm trong Controller/Blade.
- Dữ liệu nhạy cảm, branch ownership, state transition, idempotency và transaction phải theo rule tự nạp.
- Bug task phải tái hiện lỗi bằng test trước khi sửa khi khả thi.
- Không refactor ngoài phạm vi, không đưa Phase 2 vào Phase 1, không commit/push.

## 4. Quality Gates

Áp dụng [quality-gates.md](quality-gates.md):

1. Focused test của task.
2. Regression test nhỏ nhất có liên quan.
3. Build/static check khi thay frontend/config.
4. Kiểm tra diff không có file ngoài Task Contract.
5. Đồng bộ tài liệu chỉ khi contract hoặc trạng thái triển khai thực sự thay đổi.

## 5. Kết thúc có bằng chứng

Dùng [report-template.md](report-template.md). Chỉ trả một trạng thái:

- `DONE`: acceptance criteria đạt và đã có bằng chứng.
- `BLOCKED`: thiếu dependency/quyết định/quyền chạy lệnh.
- `CHANGES REQUIRED`: code đã thay đổi nhưng còn gate chưa đạt.

Không dùng “gần xong”, “cơ bản hoàn thành” hoặc tuyên bố pass khi chưa chạy kiểm tra.
