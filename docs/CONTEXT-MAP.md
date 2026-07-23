# Context Map

Đọc `docs/PROJECT-STATUS.md` trước, sau đó chọn đúng hàng dưới đây. Không quét toàn bộ repository hoặc toàn bộ `docs/`.

| Task | Context bắt buộc | Mở thêm khi cần |
|---|---|---|
| Migration/model chung | `.claude/rules/database-schema.md`, đúng bảng trong `docs/DATABASE-DICTIONARY.md` | `docs/ERD.md`, migration order, ADR được bảng tham chiếu |
| Company/Job | `.claude/rules/job-domain.md`, đúng mục 0–2 trong `docs/CORE-FLOWS.md` | Dictionary/ERD/Route/Acceptance section tương ứng |
| Candidate/Application | `.claude/rules/application-domain.md`, đúng mục 3–7 trong `docs/CORE-FLOWS.md` | Dictionary/ERD/Route/Acceptance section tương ứng |
| HR controller/view | `.claude/rules/hr-admin.md`, `.claude/rules/authorization.md` | Job/Application rule theo entity đang sửa |
| Public site/SEO | `.claude/rules/public-site.md`, `.claude/rules/seo-public.md`, `.claude/rules/ui-guidelines.md` | `UI-REFERENCE.md`, đúng 1–2 ảnh Phase 1 |
| Auth/Policy/Middleware | `.claude/rules/authorization.md`, `.claude/rules/security.md` | Route Map và acceptance criteria liên quan |
| Test | `.claude/rules/testing.md`, acceptance criteria của slice | Rule domain của code được test |
| Sửa tài liệu | `.claude/rules/docs-governance.md`, tài liệu nguồn duy nhất của nội dung | `docs/decisions/INDEX.md` nếu tạo/thay ADR |
| Phạm vi | `docs/PHASE-1-SCOPE.md`, `docs/PHASE-2-BACKLOG.md` | `docs/CORE-FLOWS.md` khi cần xác nhận nghiệp vụ |
| Bootstrap/deploy | `ROADMAP.md` mục Bootstrap, `.claude/rules/architecture.md` | ADR-050, ADR-051 qua `docs/decisions/INDEX.md` |

## Nguồn sự thật nhanh

- Scope: `docs/PHASE-1-SCOPE.md`.
- Flow/state: `docs/CORE-FLOWS.md`.
- Schema: `docs/DATABASE-DICTIONARY.md`; relationship: `docs/ERD.md`.
- Authorization/route: `docs/ROUTE-MAP.md`.
- Completion: `docs/ACCEPTANCE-CRITERIA.md`.
- ADR: `docs/decisions/INDEX.md`.
## Skill Router

| Nhu cầu | Skill |
|---|---|
| Chọn task tiếp theo | `/plan-next` |
| Task coding chưa phân loại | `/vibe-task` |
| Feature không đổi schema lớn | `/implement` |
| Migration/constraint/index | `/db-task` |
| Test/reproduction | `/test-task` |
| Xác minh hoàn thành | `/verify-task` |
| Review diff | `/review-changes` |
| Sửa finding đã duyệt | `/fix-review` |
| Readiness gate | `/release-gate` |
| Kết thúc phiên | `/handoff` |
| `TASK x.y` trong `VIECLAM88_TASK_REGISTRY_V2.3.md` | `/task-cycle TASK x.y` (chạy trọn cycle) |

Chi tiết và ví dụ: `docs/CLAUDE-SKILLS.md`.
