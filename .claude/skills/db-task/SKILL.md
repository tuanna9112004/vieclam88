---
name: db-task
description: Thực hiện thay đổi schema/model/database của vieclam88 theo dictionary, ERD và transaction rules.
argument-hint: "<thay đổi database>"
disable-model-invocation: true
effort: high
---

Thực hiện database task: **$ARGUMENTS**

1. Đọc `.claude/rules/data-model.md`, phần liên quan trong `docs/DATABASE-DICTIONARY.md`, `docs/ERD.md` và ADR liên quan.
2. Kiểm tra blocker trong `docs/PROJECT-STATUS.md`; không tự quyết enum/FK còn chưa chốt.
3. Nêu migration order, FK/on-delete, unique/check/index và rollback trước khi sửa.
4. Giữ migration nhỏ, thuận nghịch; không sửa migration đã chạy production nếu có.
5. Đồng bộ Model, Enum, Factory, Seeder và database test liên quan.
6. Chạy focused test; khi schema đủ ổn định mới chạy `migrate:fresh --seed`.
7. Báo cáo mọi khác biệt giữa code và dictionary; không âm thầm thay tài liệu nguồn.
