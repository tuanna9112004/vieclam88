# Task Router

Chọn đúng một mode chính. Khi task giao nhiều mode độc lập, tách task.

| Mode | Dấu hiệu | Context tối thiểu | Kết quả bắt buộc |
|---|---|---|---|
| Schema | migration, table, column, FK, index, enum DB | `database-schema.md`, Dictionary đúng bảng, ERD | Migration thuận nghịch + integrity test |
| Feature | tạo/sửa workflow, route, màn hình, action | Rule domain + Route Map + Acceptance section | Một vertical slice có authorization và test |
| Bug | lỗi, sai dữ liệu, 403 sai, duplicate, race | File tái hiện + rule domain + test liên quan | Reproduction test → fix → regression pass |
| Test | thêm test, tăng coverage, kiểm tra contract | `testing.md` + acceptance criteria | Test có giá trị, deterministic, không sửa nghiệp vụ |
| Docs | cập nhật plan, ADR, status, route/schema docs | `docs-governance.md` + nguồn sự thật duy nhất | Tài liệu đồng bộ + semantic checker pass |
| Review | kiểm tra diff, bảo mật, readiness | `/review-changes` hoặc `/release-gate` | Findings có bằng chứng, không edit |

## Điều hướng domain

- Job/Company/Location/Verification: `job-domain.md`.
- Candidate/Application/Duplicate/Merge/Pipeline: `application-domain.md`.
- HR/Policy/Middleware: `authorization.md`, `hr-admin.md`, `security.md`.
- Public/SEO/UI: `public-site.md`, `seo-public.md`, `ui-guidelines.md`.

## Cắt task quá rộng

Task quá rộng khi có một trong các dấu hiệu:

- Nhiều nhóm migration không cùng dependency trực tiếp.
- Vừa làm public site, HR admin và pipeline.
- Hơn một use case có thể nghiệm thu độc lập.
- Không thể mô tả kết quả duy nhất trong một câu.
- Dự kiến cần hơn 5 acceptance criteria cốt lõi.

Khi quá rộng, trả 3–7 slice theo dependency và đề xuất chính xác lệnh cho slice đầu tiên.
