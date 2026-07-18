# Context Map

Chỉ đọc nhóm tài liệu cần cho task hiện tại.

| Task | Đọc bắt buộc | Chỉ đọc khi cần chi tiết |
|---|---|---|
| Migration/schema/model | `docs/CORE-FLOWS.md`, `.claude/rules/data-model.md`, `docs/PROJECT-STATUS.md` | `docs/DATABASE-DICTIONARY.md`, `docs/ERD.md`, ADR liên quan |
| Public site | `.claude/rules/public-site.md`, `.claude/rules/ui-guidelines.md` | `docs/ROUTE-MAP.md`, `UI-REFERENCE.md`, đúng ảnh cần dùng |
| HR workflow (Job, Application, cơ sở) | `docs/CORE-FLOWS.md`, `.claude/rules/hr-admin.md`, `.claude/rules/roles-business-rules.md` | `docs/ROUTE-MAP.md`, `docs/ACCEPTANCE-CRITERIA.md` |
| Auth/authorization/security | `.claude/rules/security-seo-testing.md`, `.claude/rules/roles-business-rules.md` | route map và acceptance criteria liên quan |
| Feature/bug thông thường | file code liên quan + test gần nhất | rule tự nạp theo path; không đọc toàn bộ dictionary |
| Sửa tài liệu | `.claude/rules/docs-governance.md` | tài liệu nguồn duy nhất của nội dung đang sửa |
| UI theo ảnh | đúng 1–2 ảnh trong `docs/ui-reference/phase-1/` | không mở `out-of-scope/` trừ khi audit phạm vi |

## Nguồn sự thật

- 6 luồng nghiệp vụ cốt lõi (Job publish, tìm việc, ứng tuyển, xử lý hồ sơ, trạng thái, chuyển
  cơ sở/duplicate): `docs/CORE-FLOWS.md`. Mọi tài liệu khác phải khớp file này.
- Schema/cột/FK/index: `docs/DATABASE-DICTIONARY.md`.
- Quan hệ tổng quan: `docs/ERD.md`.
- Nghiệp vụ và quyền: `.claude/rules/roles-business-rules.md`.
- Route: `docs/ROUTE-MAP.md`.
- Điều kiện hoàn thành: `docs/ACCEPTANCE-CRITERIA.md`.
- Quyết định đã chốt: `docs/DECISIONS.md`.
- Tiến độ hiện tại: `docs/PROJECT-STATUS.md`.

Nếu hai tài liệu mâu thuẫn: dừng phần liên quan, ghi blocker vào `PROJECT-STATUS.md`, không tự chọn một phương án.
