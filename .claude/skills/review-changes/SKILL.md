---
name: review-changes
description: Review git diff trong context riêng để tìm lỗi nghiệp vụ, dữ liệu, bảo mật và test thiếu.
context: fork
agent: reviewer
disable-model-invocation: true
effort: high
---

Review thay đổi hiện tại.

- Đọc `CLAUDE.md`, `docs/PROJECT-STATUS.md` và `git diff --stat`/`git diff`.
- Chỉ mở rule/tài liệu liên quan đến file đã đổi.
- Ưu tiên: lỗi dữ liệu, authorization, transaction, regression, test thiếu, scope creep.
- Không sửa file.
- Trả về tối đa 10 phát hiện, xếp theo Critical/High/Medium/Low, có file và dòng khi có thể.
- Nếu không thấy lỗi đáng kể, nói rõ phần đã kiểm tra và rủi ro còn lại.
