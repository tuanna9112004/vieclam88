---
name: Test Task
description: Thiết kế hoặc bổ sung test tập trung cho một contract cụ thể của vieclam88, chọn đúng cấp unit/feature/integration và tránh test giả. Dùng khi cần test trước bug fix, tăng regression hoặc kiểm chứng acceptance criteria.
argument-hint: "<contract, lỗi hoặc use case cần test>"
disable-model-invocation: true
effort: high
---

Tạo hoặc cải thiện test cho: **$ARGUMENTS**

1. Đọc `.claude/rules/testing.md`, acceptance criteria và rule domain liên quan.
2. Xác định hành vi cần chứng minh, không bắt đầu từ mục tiêu “tăng coverage”.
3. Chọn cấp test thấp nhất vẫn chứng minh được contract:
   - unit cho normalization/predicate thuần;
   - feature cho HTTP/auth/policy/use case;
   - integration cho DB constraint, transaction, concurrency;
   - browser/manual checklist chỉ khi UI không thể chứng minh bằng feature test.
4. Với bug: viết test tái hiện và xác nhận test fail đúng nguyên nhân trước khi sửa production code.
5. Test phải deterministic, cô lập database, kiểm tra cả happy path và failure quan trọng.
6. Không mock phần cần chứng minh; không assert implementation detail vô nghĩa.
7. Chạy focused test, rồi regression nhỏ nhất liên quan.
8. Không sửa production code trừ khi `$ARGUMENTS` yêu cầu rõ cả fix; nếu phát hiện bug, trả `CHANGES REQUIRED` cùng test tái hiện. Không commit/push.
