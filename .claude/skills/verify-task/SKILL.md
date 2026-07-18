---
name: Verify Task
description: Xác minh một task đã hoàn thành bằng test, build, route/schema inspection và đối chiếu acceptance criteria; không sửa file. Dùng trước handoff hoặc commit.
argument-hint: "<task hoặc acceptance criteria cần xác minh>"
disable-model-invocation: true
effort: high
---

Xác minh: **$ARGUMENTS**

1. Đọc Task Contract/acceptance criteria và diff liên quan; không tin mô tả “đã xong”.
2. Lập ma trận criteria → bằng chứng cần có.
3. Chạy focused test trước; chỉ chạy suite/build rộng hơn khi thay đổi có thể ảnh hưởng chéo.
4. Kiểm tra khi phù hợp:
   - route và middleware;
   - migration status/schema constraint;
   - policy/branch isolation;
   - transaction/history;
   - public data/PII;
   - frontend build và manual checklist.
5. Không edit file, không migrate dữ liệu thật, không commit/push.
6. Kết luận duy nhất:
   - `PASS`: mọi criterion có bằng chứng;
   - `FAIL`: có criterion sai;
   - `INCOMPLETE`: thiếu quyền/môi trường/bằng chứng.
7. Liệt kê command, exit result và criterion chưa chứng minh được.
