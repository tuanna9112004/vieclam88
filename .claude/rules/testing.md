---
paths:
  - "tests/**/*.php"
---

# Testing

- Acceptance source: đúng section trong `docs/ACCEPTANCE-CRITERIA.md`; mỗi thay đổi có focused test trước khi chạy suite rộng.
- Test cả happy path, validation, authorization trực tiếp qua URL, DB constraint, rollback và history/audit side effects.
- Domain concurrency phải có test phù hợp: idempotency, named lock/race, primary uniqueness, row locking/transfer.
- Test Branch A không đọc/sửa dữ liệu Branch B; Admin và Staff phải có expectation riêng.
- Không dùng Factory tạo state/history bất hợp lệ chỉ để test dễ hơn; tạo qua Action khi đang kiểm domain transition.
- Không tuyên bố pass khi lệnh chưa chạy, fail hoặc bị skip ngoài chủ đích; báo chính xác command và kết quả.
