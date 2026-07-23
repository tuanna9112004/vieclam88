---
name: Session Handoff
description: Kết thúc phiên bằng trạng thái ngắn có bằng chứng, diff hiện tại, blocker và đúng một task tiếp theo. Dùng sau khi verify task hoặc trước compact/chuyển phiên.
argument-hint: "[task vừa làm]"
disable-model-invocation: true
effort: low
---

Tạo handoff cho: **$ARGUMENTS**

1. Chỉ ghi `DONE` khi đã có bằng chứng test/build; nếu chưa, ghi `INCOMPLETE` hoặc `BLOCKED`.
2. Xem `git status --short`, `git diff --stat` và kết quả kiểm tra gần nhất.
3. Cập nhật `docs/PROJECT-STATUS.md` tối đa 40 dòng với:
   - phase/slice hiện tại;
   - completed;
   - verification command + kết quả;
   - blockers;
   - đúng một task `NEXT` và tối đa hai task sau đó.
4. Không chép log dài, diff, kế hoạch cũ hoặc ADR vào status.
5. Chỉ thêm ADR khi có quyết định lâu dài đã được chốt; không sửa `AGENTS.md` để lưu trạng thái phiên.
6. Không commit/push/tag trừ khi người dùng yêu cầu rõ.
