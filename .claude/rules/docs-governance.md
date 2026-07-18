---
paths:
  - "*.md"
  - "docs/**/*.md"
  - ".claude/**/*.md"
---

# Quản trị tài liệu

- Mỗi nội dung có một nguồn sự thật theo `docs/INDEX.md`; file khác chỉ tóm tắt và liên kết, không chép nguyên contract.
- `CLAUDE.md` chỉ giữ bất biến/workflow toàn cục; `PROJECT-STATUS.md` tối đa 40 dòng và không lưu lịch sử dài.
- ADR nằm trong `docs/decisions/`; `docs/DECISIONS.md` chỉ là compatibility pointer. Không dùng ADR như nhật ký phiên.
- Khi đổi rule/path/tên tài liệu, cập nhật `docs/INDEX.md`, `docs/CONTEXT-MAP.md`, skills và mọi tham chiếu liên quan.
- Không đánh dấu frozen/hoàn thành khi còn mâu thuẫn hoặc blocker schema chưa xử lý.
- Sau thay đổi chạy `python scripts/check-claude-config.py` và `git diff --check`.
