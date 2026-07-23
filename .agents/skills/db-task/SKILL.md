---
name: Database Slice
description: Thực hiện một thay đổi schema/model/database theo Database Baseline, đúng dependency và có integrity test. Dùng cho migration, constraint, index, relationship hoặc seed/import.
argument-hint: "<bảng hoặc thay đổi database>"
disable-model-invocation: true
effort: high
---

Thực hiện database task: **$ARGUMENTS**

1. Đọc `docs/PROJECT-STATUS.md`, `.Codex/rules/database-schema.md`, đúng bảng trong Dictionary và quan hệ trong ERD.
2. Đọc rule domain/ADR được tham chiếu; không tải toàn bộ docs khi task chỉ liên quan một nhóm bảng.
3. Trước edit, nêu:
   - migration order/dependency;
   - type/nullability/default;
   - FK và on-delete;
   - unique/check/index;
   - soft delete/history/PII;
   - rollback và test integrity.
4. Nếu Dictionary, ERD và flow mâu thuẫn, trả `BLOCKED`; không tự chọn schema.
5. Migration phải nhỏ và thuận nghịch. Không sửa migration đã chạy staging/production; tạo migration mới khi repository đã có dữ liệu dùng thử.
6. Đồng bộ Model/Enum/Factory/Seeder tối thiểu; history data phải được tạo qua domain action khi factory có thể phá invariant.
7. Viết test cho constraint, relationship, rollback/error path và query/index quan trọng khi có contract.
8. Không tự chạy `migrate:fresh`, migrate staging/production, xóa data hoặc commit/push.
9. Kết thúc bằng `DONE`, `BLOCKED` hoặc `CHANGES REQUIRED` cùng command/kết quả.
