---
name: Review Changes
description: Review diff read-only, ưu tiên correctness, data integrity, authorization, race condition, PII và test thiếu. Dùng sau mỗi vertical slice hoặc trước commit.
argument-hint: "[phạm vi hoặc mục tiêu review]"
context: fork
agent: reviewer
disable-model-invocation: true
effort: high
---

Review thay đổi hiện tại cho: **$ARGUMENTS**

1. Đọc `AGENTS.md`, `docs/PROJECT-STATUS.md`, `git status --short`, `git diff --stat` và diff liên quan.
2. Dùng `docs/CONTEXT-MAP.md` để mở tối thiểu rule, contract và acceptance criteria phù hợp.
3. Áp dụng [review-rubric.md](review-rubric.md). Không review style trước correctness.
4. Không sửa file, không commit/push, không đề xuất refactor không liên quan.
5. Tối đa 10 finding; mỗi finding phải có:
   - severity `Critical|High|Medium|Low`;
   - file/dòng hoặc symbol;
   - kịch bản gây lỗi;
   - contract bị vi phạm;
   - cách sửa nhỏ nhất;
   - test cần bổ sung.
6. Loại bỏ finding suy đoán không có đường tái hiện.
7. Kết luận `APPROVE`, `CHANGES REQUIRED` hoặc `INCOMPLETE REVIEW`, kèm phạm vi chưa kiểm.
