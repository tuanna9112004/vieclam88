---
name: Task Cycle
description: Chạy trọn một TASK VIECLAM88 theo cycle resolve -> contract -> implement -> test -> verify -> independent review -> safe-fix -> commit/push -> handoff.
argument-hint: "TASK x.y [--resume|--audit]"
disable-model-invocation: true
effort: high
---

Thực hiện cycle: **$ARGUMENTS**

## 1. Resolve đúng task

1. Đọc `CLAUDE.md`, `docs/PROJECT-STATUS.md` và `docs/VIECLAM88_TASK_REGISTRY_V2.3.md`.
2. Tìm đúng heading `TASK x.y`; đọc đúng KEY, GATE, DONE, NEXT và dependency trực tiếp.
3. Đối chiếu source thực tế. Source thắng số liệu snapshot; target không được coi là current.
4. Không tìm thấy task, task mâu thuẫn hoặc dependency chưa đạt => `BLOCKED`, không edit.

## 2. Khóa Task Contract

Nêu ngắn: một kết quả, in/out of scope, tối đa 5 acceptance criteria, file dự kiến, lệnh kiểm tra và rollback. Không mở rộng task.

## 3. Route và thực thi

- DOCS/CONFIG -> áp dụng rules của `/vibe-task` mode docs.
- SCHEMA/BACKFILL/SEED/CATALOG -> áp dụng rules của `/db-task`.
- FEATURE/WORKFLOW/UI/QUERY/FILE/AUTH/REPORT/EXPORT/CUTOVER -> áp dụng rules của `/implement` và domain rules liên quan.
- TEST -> áp dụng `/test-task`.
- RELEASE GATE/STAGING/PRODUCTION/CONTRACT -> áp dụng `/release-gate` và runbook rules.

Không cần gọi lồng slash command; thực hiện theo rule tương ứng. Không sửa migration cũ, không dùng lệnh destructive, không refactor ngoài scope.

## 4. Gate tự động

1. Chạy focused test, regression nhỏ nhất, build/static/schema/audit phù hợp.
2. Verify criteria bằng command + exit result.
3. Giao review cho `.claude/agents/reviewer.md` nếu có. Reviewer read-only và trả `APPROVE` hoặc `CHANGES REQUIRED`.
4. Tự sửa tối đa 2 vòng chỉ với finding LOW/MEDIUM rõ ràng, trong scope và không destructive; sau mỗi vòng chạy lại gate.
5. Finding CRITICAL/HIGH, ambiguity, thiếu môi trường hoặc quá 2 vòng => dừng `BLOCKED/CHANGES REQUIRED`, không commit/push.
6. `--audit`: chỉ thực hiện bước 1-3, không edit/commit/push.
7. `--resume`: chỉ xử lý findings/diff còn lại của đúng task.

## 5. Hoàn tất có điều kiện

Chỉ khi `VERIFICATION=PASS` và `REVIEW=APPROVE`:

1. Nếu là PHASE-END, đối chiếu phase gate trong registry.
2. Cập nhật `docs/PROJECT-STATUS.md` ngắn gọn với evidence, blocker và đúng một NEXT.
3. Chạy `git status --short`, `git diff --check`; stage chính xác file thuộc task, không dùng `git add .`.
4. Tạo một commit theo mode và task ID; push current branch nếu remote hợp lệ.
5. Trả handoff: task status, commands/results, files, commit hash, push status, working tree, NEXT.

Nếu gate chưa đạt: không ghi DONE, không commit/push.

## Output bắt buộc

`TASK: x.y | STATUS: DONE|CHANGES REQUIRED|BLOCKED|AUDIT PASS|AUDIT FAIL`

`CONTRACT | CHANGES | EVIDENCE | REVIEW | COMMIT/PUSH | NEXT`
