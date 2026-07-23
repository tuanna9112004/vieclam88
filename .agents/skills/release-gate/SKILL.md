---
name: Release Gate
description: Audit read-only mức sẵn sàng của baseline, staging hoặc production, ưu tiên blocker thật sự về data, security, migration, backup và vận hành.
argument-hint: "<baseline|staging|production>"
context: fork
agent: reviewer
disable-model-invocation: true
effort: high
---

Đánh giá release gate: **$ARGUMENTS**

- Đọc `AGENTS.md`, `docs/PROJECT-STATUS.md`, `ROADMAP.md`, diff hiện tại và tài liệu/checklist đúng gate.
- Không sửa file, không deploy, không chạy destructive command.
- Baseline gate: consistency docs/schema/routes/acceptance/skills checker.
- Staging gate: migration, env separation, debug, seed, scheduler, log, backup, UAT chính.
- Production gate: staging evidence, HTTPS, secrets, rollback/restore, consent/retention/PII, monitoring, demo data và pilot scope.
- Chỉ liệt kê blocker có bằng chứng. Phân loại `BLOCKER`, `RISK`, `NOTE`.
- Kết luận duy nhất `READY` hoặc `NOT READY`, kèm điều kiện cụ thể để đổi trạng thái.
