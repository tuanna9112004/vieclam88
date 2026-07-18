# Claude Skills Catalog

Skills dự án nằm tại `.claude/skills/<skill-name>/SKILL.md`. Dùng skill nhỏ đúng mục đích thay vì dán lại prompt dài.

## Luồng mặc định

```text
/plan-next
→ /vibe-task <mục NEXT>
→ /verify-task <mục vừa làm>
→ /review-changes <mục vừa làm>
→ /fix-review <finding được duyệt> (khi cần)
→ /handoff <mục vừa làm>
```

## Danh mục

| Skill | Khi dùng | Có sửa file |
|---|---|---:|
| `/vibe-task <task>` | Điểm vào thông minh: tự phân loại schema/feature/bug/test/docs, khóa scope và làm một vertical slice | Có |
| `/plan-next [giai đoạn]` | Chọn task nhỏ tiếp theo theo dependency và blocker | Không |
| `/implement <feature>` | Feature đã rõ contract, không đổi schema đáng kể | Có |
| `/db-task <schema>` | Migration, constraint, index, model relation, seed/import | Có |
| `/test-task <contract>` | Viết test tái hiện hoặc regression tập trung | Chỉ test mặc định |
| `/verify-task <task>` | Xác minh bằng command và acceptance criteria trước handoff/commit | Không |
| `/review-changes [scope]` | Review diff read-only bằng reviewer agent | Không |
| `/fix-review <finding>` | Sửa finding đã được xác nhận | Có |
| `/release-gate <gate>` | Audit `baseline`, `staging` hoặc `production` | Không |
| `/handoff <task>` | Cập nhật trạng thái phiên ngắn, có bằng chứng | Có status doc |

## Chọn skill

- Chưa biết làm gì tiếp: `/plan-next`.
- Yêu cầu coding tự nhiên, chưa chắc thuộc loại nào: `/vibe-task`.
- Biết chắc thay schema: `/db-task`.
- Feature đã đủ schema và contract: `/implement`.
- Muốn test trước khi sửa bug: `/test-task`.
- Muốn xác nhận “đã xong thật chưa”: `/verify-task`.
- Muốn tìm lỗi trong diff: `/review-changes`.
- Chỉ sửa finding cụ thể: `/fix-review`.
- Trước staging/production: `/release-gate`.

## Quy tắc

- Mỗi lần chỉ làm một vertical slice nghiệm thu độc lập.
- Task quá rộng phải được chia nhỏ, không tự code toàn bộ.
- Skill có side effect đều chỉ chạy khi người dùng gọi trực tiếp.
- Không skill nào tự commit, push, migrate production hoặc xóa dữ liệu.
- Sau khi sửa skill, chạy:

```bash
python scripts/check-claude-skills.py
python scripts/check-claude-config.py
git diff --check
```

## Ví dụ

```text
/plan-next giai đoạn nền tảng
/vibe-task triển khai command app:create-admin kèm feature test
/db-task tạo administrative_units theo Database Dictionary
/test-task tái hiện hai request khác token tạo trùng Application
/verify-task PublishJobAction
/review-changes PublishJobAction
/fix-review High finding về cross-branch authorization
/handoff PublishJobAction
```
