---
paths:
  - "*.md"
  - "docs/**/*.md"
  - ".claude/**/*.md"
---

# Quy tắc tài liệu

- Mỗi nội dung có một nguồn sự thật; file khác chỉ liên kết, không chép lại.
- `CLAUDE.md` chỉ giữ bất biến và workflow chung, không chứa schema/route chi tiết.
- `PROJECT-STATUS.md` chỉ giữ trạng thái hiện tại, tối đa khoảng 40 dòng.
- Không đánh dấu hoàn thành khi tài liệu vẫn còn `[đề xuất]`, TODO hoặc mâu thuẫn liên quan.
- Dùng đường dẫn đầy đủ từ root, ví dụ `.claude/rules/data-model.md`.
- Sau khi sửa tài liệu, chạy `python scripts/check-claude-config.py`.
