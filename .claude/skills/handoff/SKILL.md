---
name: handoff
description: Kết thúc session và cập nhật PROJECT-STATUS ngắn gọn, có bằng chứng kiểm tra.
disable-model-invocation: true
effort: low
---

Kết thúc session:

1. Chạy kiểm tra nhỏ nhất liên quan hoặc ghi rõ vì sao chưa thể chạy.
2. Xem `git diff --stat` và `git status --short`.
3. Cập nhật `docs/PROJECT-STATUS.md`, tối đa 40 dòng, chỉ giữ:
   - phase/current slice;
   - đã hoàn thành;
   - verification + kết quả;
   - blockers;
   - tối đa 3 bước tiếp theo.
4. Ghi ADR mới chỉ khi có quyết định kiến trúc thực sự.
5. Không commit/push trừ khi người dùng yêu cầu rõ.
