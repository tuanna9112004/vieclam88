# Review Rubric

Kiểm tra theo thứ tự:

1. **Data loss/integrity** — FK, unique, nullability, delete/restore, history, rollback.
2. **Authorization** — role, branch isolation, direct URL, inactive user, admin exception.
3. **Concurrency/idempotency** — transaction, row/named lock, double submit, stale reads.
4. **State machine** — transition/evidence/workflow cycle/latest verification.
5. **PII/security** — output, logs, snapshot, CSV, mass assignment, CSRF/XSS/rate limit.
6. **Scope** — Phase 2 leakage, unnecessary abstraction/dependency/schema.
7. **Tests** — missing failure path, weak assertion, wrong test level, nondeterminism.
8. **Performance** — N+1, unbounded query, missing pagination/index-backed filter.
9. **Maintainability** — duplicated domain logic, Controller/Blade business logic.
10. **Docs** — only when implementation changes a frozen contract or status.

Severity:

- Critical: mất/rò dữ liệu diện rộng, bypass auth, migration phá production.
- High: sai nghiệp vụ cốt lõi, race tạo dữ liệu trùng, cross-branch access.
- Medium: failure path thiếu, query/maintainability đáng kể nhưng có workaround.
- Low: cải tiến nhỏ, không chặn nghiệm thu task.
