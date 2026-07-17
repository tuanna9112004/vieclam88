# vieclam88

Hệ thống tuyển dụng cho công ty cung ứng lao động tại các khu công nghiệp miền Bắc:
website công khai và khu vực HR `/hr`, dùng chung Laravel monolith và MariaDB.

## Trạng thái

Đang hoàn thiện đặc tả và cấu hình Claude Code; **chưa có source Laravel**.
Xem `docs/PROJECT-STATUS.md`.

## Bắt đầu nhanh với Claude Code

1. Mở Claude Code tại root repository.
2. Đọc `CLAUDE.md`; Claude sẽ tự nạp rule phù hợp theo file đang làm.
3. Dùng một trong các workflow:
   - `/implement <kết quả cần đạt>`
   - `/db-task <thay đổi database>`
   - `/review-changes`
   - `/handoff`
4. Kiểm tra cấu hình:

```bash
python scripts/check-claude-config.py
```

## Tài liệu

- Điều hướng context: `docs/CONTEXT-MAP.md`.
- Trạng thái: `docs/PROJECT-STATUS.md`.
- Schema: `docs/DATABASE-DICTIONARY.md`, `docs/ERD.md`.
- Route: `docs/ROUTE-MAP.md`.
- Nghiệm thu: `docs/ACCEPTANCE-CRITERIA.md`.
- Quyết định: `docs/DECISIONS.md`.
- Lộ trình: `ROADMAP.md`.

Hướng dẫn cài đặt thực tế sẽ được bổ sung sau khi Laravel project được khởi tạo và các lệnh đã được kiểm chứng.
