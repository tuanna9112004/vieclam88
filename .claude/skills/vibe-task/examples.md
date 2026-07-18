# Ví dụ dùng Vibe Task

## Task hợp lệ

```text
/vibe-task triển khai command app:create-admin kèm feature test
/vibe-task sửa lỗi Staff Branch Bắc Ninh xem được Application Vĩnh Phúc
/vibe-task tạo migration và model branches theo Database Dictionary
/vibe-task hoàn thiện PublishJobAction theo Publish Predicate
```

## Task quá rộng

```text
/vibe-task hoàn thành toàn bộ website Phase 1
```

Kết quả mong đợi: không code; chia thành các vertical slice và chọn slice đầu tiên theo dependency.

## Task bị blocker

```text
/vibe-task tạo migration khi Dictionary và ERD mâu thuẫn nullability
```

Kết quả mong đợi: `BLOCKED`, chỉ rõ nguồn mâu thuẫn; không tự chọn schema.
