# VIECLAM88 TASK REGISTRY V2.3 - ONE CYCLE

Nguồn vận hành gọn cho Claude Code. Source thực tế luôn ưu tiên hơn số liệu snapshot trong tài liệu.

## 3 key duy nhất

- `RUN`: `/task-cycle TASK x.y` - chạy trọn cycle, tự gate, commit/push/handoff khi PASS + APPROVE.
- `RESUME`: `/task-cycle TASK x.y --resume` - tiếp tục đúng findings/diff còn lại; không mở rộng scope.
- `AUDIT`: `/task-cycle TASK x.y --audit` - chỉ verify + review read-only; không sửa/commit/push.

## Global invariants

- Chỉ một task, một vertical slice, một commit; không kéo task kế tiếp.
- Không sửa bất kỳ migration đã tồn tại khi task bắt đầu; schema mới dùng migration additive.
- Không chạy migrate:fresh, db:wipe, reset/rollback destructive hoặc xóa dữ liệu thật.
- Source thực tế thắng số liệu snapshot; target không được mô tả là current.
- Giữ submission_token, duplicate/merge, histories, contact/appointment, branch transfer và branch policies.
- Chỉ commit/push khi verification PASS, reviewer APPROVE và diff đúng scope.
- Finding CRITICAL/HIGH, ambiguity hoặc dependency thiếu => BLOCKED, không commit.

# PHẦN 0 - KHÓA BASELINE VÀ TẠO ĐƯỜNG LUI

**PHASE GATE:** Chốt hiện trạng, backup/rollback và test baseline trước khi đổi nghiệp vụ.

## TASK 0.1 - Lập baseline kỹ thuật trước khi sửa

- **RUN:** `/task-cycle TASK 0.1`
- **MODE:** `DOCS`
- **KEY:** Tạo baseline kỹ thuật cho repository hiện tại, chưa thay đổi nghiệp vụ.; Kiểm kê migration, model, route, policy, FormRequest, Action, seeder, view và test hiện tại.; Tạo `docs/refactor/00-CURRENT-BASELINE.md` ghi:; version PHP/Laravel/Composer/Node/npm và database thực tế hoặc mục tiêu;; danh sách đầy đủ migration, bảng và module được kiểm kê trực tiếp từ source tại thời điểm chạy;; route public và /hr;; các workflow đang được bảo vệ;; số test hiện có;; các xung đột với Baseline 1.1.; Tạo `docs/refactor/01-ROLLBACK-PLAN.md` mô tả backup source, DB, storage và cách phục hồi.; Không sửa code nghiệp vụ và không tạo migration.; Cập nhật `docs/INDEX.md` và `docs/PROJECT-STATUS.md` để dẫn tới bộ tài liệu refactor.; Ghi branch, commit và working tree thật; nếu thiếu .git, vendor hoặc dependency thì báo INCOMPLETE, không khai PASS.
- **GATE:** Có danh sách đầy đủ bảng, route và module hiện tại.; Nêu rõ các thành phần phải giữ: idempotency, duplicate/merge, workflow history, branch policy.; Có kế hoạch backup source, DB và storage/app.; Có tiêu chí rollback cụ thể, không chỉ viết "khôi phục backup".; Không có file production code hoặc migration bị sửa.; check-claude-config và check-claude-skills vẫn PASS.
- **DONE:** Baseline và rollback plan được đối chiếu với source, reviewer PASS và không có thay đổi nghiệp vụ.
- **NEXT:** TASK 0.2 - Đồng bộ CLAUDE.md/.claude với kiến trúc mục tiêu nhưng không nói sai hiện trạng

## TASK 0.2 - Đồng bộ CLAUDE.md/.claude với kiến trúc mục tiêu nhưng không nói sai hiện trạng

- **RUN:** `/task-cycle TASK 0.2`
- **MODE:** `CONFIG`
- **KEY:** Audit và hoàn thiện CLAUDE.md/.claude cho chương trình refactor, không viết lại cấu hình đã có.; Phân biệt CURRENT, TARGET và gate hiện tại; không tuyên bố schema target đã tồn tại.; Kiểm tra và bổ sung nhỏ nhất các rule/skill còn thiếu: migration additive, backfill idempotent, branch authorization, private upload/download và release gate.; Bảo đảm slash skills, đường dẫn docs và semantic checker khớp source; sửa checker ADR/table classification nếu phát hiện cảnh báo sai.; Không lặp toàn bộ PDF trong CLAUDE.md và không lưu trạng thái phiên trong CLAUDE.md.
- **GATE:** Current và target không bị trộn lẫn.; Claude không thể hiểu nhầm rằng phải sửa migration cũ.; Có skill migration/backfill an toàn.; Có rule private upload và branch isolation.; Không có đường dẫn chết.; Hai script cấu hình PASS.
- **DONE:** Claude config PASS, không còn chỉ dẫn mâu thuẫn và mỗi giai đoạn sau có rule/skill rõ ràng.
- **NEXT:** TASK 0.3 - Chốt baseline test có thể lặp lại

## TASK 0.3 - Chốt baseline test có thể lặp lại

- **RUN:** `/task-cycle TASK 0.3`
- **MODE:** `TEST`
- **KEY:** Chốt quality gate trước refactor.; Thiết lập `.env.testing` mẫu an toàn nếu repository chưa có.; Xác nhận TestDatabaseGuard vẫn chặn DB không phải testing.; Chạy focused guard tests, database integrity tests, toàn suite và frontend build.; Phân loại lỗi thành regression, thiếu dependency hoặc thiếu binary hệ thống.; Không sửa test chỉ để làm xanh nếu test đang phản ánh đúng nghiệp vụ.; Ghi kết quả thật vào `docs/refactor/02-TEST-BASELINE.md`.
- **GATE:** Có lệnh tái hiện baseline.; Có số test pass/fail/skipped chính xác.; Lỗi môi trường được tách khỏi regression.; Không dùng database thật/production.; Không bỏ hoặc disable test để đạt PASS.
- **DONE:** Có một baseline test đáng tin cậy; mọi lỗi còn lại được ghi rõ là blocker môi trường hoặc regression.
- **NEXT:** TASK 1.1 - Tạo bảng mới và command đồng bộ địa chỉ
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 1 - NỀN TẢNG ĐỊA CHỈ PROVINCES / WARDS

**PHASE GATE:** Expand - backfill - switch địa chỉ mới, không phá dữ liệu cũ.

## TASK 1.1 - Tạo bảng mới và command đồng bộ địa chỉ

- **RUN:** `/task-cycle TASK 1.1`
- **MODE:** `SCHEMA`
- **KEY:** Thêm `provinces` và `wards` theo Baseline 1.1, không xóa `administrative_units`. Tạo: migration `create_provinces_table`;; migration `create_wards_table`;; model/factory Province, Ward;; command `locations:sync` đọc API v2 và upsert theo `code`; tái sử dụng/tách logic fetch-normalize hiện có từ `administrative-units:import`, không tạo hai implementation API lệch nhau;; service/action tách riêng việc fetch, normalize và upsert;; test Http::fake, idempotency, province-ward relation, bản ghi inactive không xuất hiện trong lựa chọn mới. Quy tắc: `provinces.code` và `wards.code` unique;; `wards.province_id` NOT NULL;; không tự xóa hoặc deactivate bản ghi vắng mặt nếu chưa có quyết định rõ;; runtime website chỉ đọc DB local, không gọi API;; không cho phép CRUD tay từ UI.; Giữ `administrative-units:import` hoạt động trong giai đoạn chuyển tiếp; runtime website chỉ đọc database local.
- **GATE:** Migration chạy trên DB trống và DB hiện có.; Sync chạy hai lần không trùng.; Ward luôn thuộc đúng province.; API timeout/invalid payload rollback transaction hợp lý.; Không đụng administrative_units.; Có index is_active, province_id, code unique.
- **DONE:** locations:sync idempotent, test PASS và bảng cũ vẫn nguyên vẹn.
- **NEXT:** TASK 1.2 - Backfill từ administrative_units sang province/ward mới

## TASK 1.2 - Backfill từ administrative_units sang province/ward mới

- **RUN:** `/task-cycle TASK 1.2`
- **MODE:** `BACKFILL`
- **KEY:** Viết command backfill địa chỉ cũ sang cấu trúc mới. Tạo `locations:backfill-administrative-units`:; map theo `official_code` trước, không map bằng tên nếu có thể tránh;; hỗ trợ dry-run, batch size, resume và report CSV/JSON;; phân loại mapped, ambiguous, missing, invalid-parent;; không đoán ward khi dữ liệu cũ chỉ là province hoặc legacy district;; lưu mapping vào bảng chuyển tiếp `administrative_unit_mappings` hoặc file mapping có version nếu cần;; chạy lại không tạo dữ liệu trùng;; không cập nhật FK của bảng nghiệp vụ ở task này.
- **GATE:** Có --dry-run không ghi DB.; Có report bản ghi không map được.; Chạy lại an toàn.; Không đoán sai ward từ text địa chỉ.; Có test mapping chính xác và ambiguous.; Tổng số mapped + unresolved bằng tổng đầu vào trong phạm vi command.
- **DONE:** Có mapping/report hoàn chỉnh, không mất hoặc đoán dữ liệu.
- **NEXT:** TASK 1.3 - Chuyển các bảng nghiệp vụ sang FK ward theo kiểu expand

## TASK 1.3 - Chuyển các bảng nghiệp vụ sang FK ward theo kiểu expand

- **RUN:** `/task-cycle TASK 1.3`
- **MODE:** `SWITCH`
- **KEY:** Thêm FK ward mới vào các bảng đang cần địa chỉ, chưa xóa FK cũ. Thêm nullable trước:; `branches.ward_id`;; `companies.headquarters_ward_id`;; `candidates.current_ward_id`;; chuẩn bị `jobs.work_ward_id` ở Task 6, không thêm trùng tại đây. Yêu cầu:; backfill từ mapping Task 1.2;; code đọc mới ưu tiên ward, fallback administrative_unit trong giai đoạn chuyển tiếp;; form mới chọn province -> ward nhưng chỉ lưu ward_id;; province được suy ra từ ward, không lưu province_id trùng trong nghiệp vụ;; validation chỉ nhận ward active;; giữ UI quản trị đơn vị hành chính cũ ở trạng thái read-only/deprecated trong giai đoạn chuyển tiếp.
- **GATE:** Không có bảng nghiệp vụ lưu province và ward mâu thuẫn.; Dữ liệu cũ vẫn hiển thị qua fallback.; Dữ liệu mới chỉ ghi ward_id.; Ward inactive không được chọn mới.; Có report bản ghi chưa backfill.; Test form và relation PASS.
- **DONE:** Mọi luồng tạo/sửa branch/company/candidate dùng ward mới, dữ liệu cũ vẫn đọc được.
- **NEXT:** TASK 2.1 - Mở rộng role thành ba cấp
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 2 - VAI TRÒ, CHI NHÁNH VÀ PHÂN QUYỀN

**PHASE GATE:** Ba role và branch isolation phải được bảo vệ bằng policy, middleware và test.

## TASK 2.1 - Mở rộng role thành ba cấp

- **RUN:** `/task-cycle TASK 2.1`
- **MODE:** `AUTH+SCHEMA`
- **KEY:** Chuyển role `admin/staff` thành `super_admin/branch_admin/staff` an toàn.; Không sửa migration users cũ; tạo migration đổi kiểu role theo chiến lược MariaDB an toàn.; Backfill `admin -> super_admin` mặc định, trừ khi có mapping branch admin được xác nhận.; Cập nhật User helpers: isSuperAdmin, isBranchAdmin, isStaff, canManageBranch.; Duy trì alias `isAdmin()` tạm thời nếu cần để không phá code trong một release, nhưng đánh dấu deprecated và có kế hoạch bỏ.; Cập nhật middleware, route role list, policies, form request, factory và test.; Quy tắc: super_admin: branch_id NULL;; branch_admin/staff: branch_id NOT NULL ở validation/service;; không để user role branch có branch đã xóa/inactive khi đăng nhập hoặc thao tác.
- **GATE:** User cũ không bị khóa ngoài ý muốn.; Super admin có toàn quyền hiện có.; Branch admin chỉ thao tác branch mình.; Staff không có quyền quản lý user/branch.; Middleware route chấp nhận đúng ba role.; Test 403 chéo branch đầy đủ.
- **DONE:** Không còn route/policy nào chỉ hiểu hai role, ma trận quyền PASS.
- **NEXT:** TASK 2.2 - Chuẩn hóa branch và seed 4 cơ sở

## TASK 2.2 - Chuẩn hóa branch và seed 4 cơ sở

- **RUN:** `/task-cycle TASK 2.2`
- **MODE:** `SEED`
- **KEY:** Chuẩn hóa `branches` và seed dữ liệu cơ sở. Giữ các trường hiện có, dùng `ward_id` từ Task 1.3 làm địa chỉ chuẩn. Tạo BranchSeeder idempotent theo code:; VP - Chi nhánh Vĩnh Phúc; PT - Văn phòng đại diện Phú Thọ; HB - Chi nhánh Hòa Bình; BGBN - Chi nhánh Bắc Giang - Bắc Ninh Không hardcode ID. Không ghi ward giả nếu chưa xác minh code; cho phép branch seed ở trạng thái cần hoàn thiện địa chỉ hoặc dùng mapping cấu hình rõ ràng. Không xóa branch cũ; xuất report branch trùng/gần giống để merge thủ công.
- **GATE:** Seeder chạy hai lần không trùng.; 4 code unique tồn tại.; Không hardcode PK.; Branch cũ không bị xóa.; Có report duplicate/legacy branch.; CTA phone/zalo hiện có không bị mất.
- **DONE:** 4 branch chuẩn tồn tại idempotent, không ảnh hưởng dữ liệu branch cũ.
- **NEXT:** TASK 2.3 - Cứng hóa branch isolation cho ba role

## TASK 2.3 - Cứng hóa branch isolation cho ba role

- **RUN:** `/task-cycle TASK 2.3`
- **MODE:** `AUTH`
- **KEY:** Cập nhật toàn bộ branch authorization sau đổi role. Rà soát:; JobPolicy, ApplicationPolicy, CandidatePolicy, UserPolicy, BranchPolicy;; controller index query scoping;; dashboard, CSV export, duplicate review, candidate merged family;; route trực tiếp theo ID và các endpoint JSON quick-create. Quy tắc: super_admin: toàn hệ thống;; branch_admin: quản lý staff/job/application của branch mình;; staff: xử lý job/application theo quyền hiện có, không quản lý staff;; candidate chỉ xem được khi merged family có application thuộc branch;; mọi query list và export phải scope ở server.
- **GATE:** Không chỉ ẩn nút; URL trực tiếp cũng 403.; CSV export không lộ branch khác.; Dashboard branch admin không cộng dữ liệu toàn hệ thống.; Candidate merged family vẫn không lộ chéo branch.; Super admin không bị scope nhầm.; Có test authorization matrix.
- **DONE:** Ma trận phân quyền 3 vai trò được test tự động và không có lộ dữ liệu chéo.
- **NEXT:** TASK 3.1 - Expand schema KCN
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 3 - KHU CÔNG NGHIỆP VÀ QUAN HỆ VỚI WARD

**PHASE GATE:** Chuẩn hóa KCN theo branch và mapping N-N với ward.

## TASK 3.1 - Expand schema KCN

- **RUN:** `/task-cycle TASK 3.1`
- **MODE:** `SCHEMA`
- **KEY:** Mở rộng `industrial_parks` theo Baseline. Thêm additive:; `branch_id` nullable trước, FK branches;; `code` nullable unique trước khi backfill;; giữ `administrative_unit_id` tạm thời để fallback;; tạo bảng `industrial_park_wards` gồm industrial_park_id, ward_id, is_primary, unique composite;; DB constraint/logic đảm bảo tối đa một primary ward mỗi KCN;; model relations, factories và integrity tests.
- **GATE:** KCN cũ vẫn đọc được.; Pivot không có duplicate pair.; Một KCN có thể nhiều ward.; Một ward có thể nhiều KCN.; Tối đa một is_primary=true mỗi KCN.; Không xóa administrative_unit_id trong task này.
- **DONE:** Quan hệ N-N hoạt động và ràng buộc primary được bảo vệ ở DB hoặc transaction có test concurrency.
- **NEXT:** TASK 3.2 - Seed 10 KCN gắn đúng branch

## TASK 3.2 - Seed 10 KCN gắn đúng branch

- **RUN:** `/task-cycle TASK 3.2`
- **MODE:** `SEED`
- **KEY:** Tạo IndustrialParkSeeder idempotent. Dùng branch code, không dùng ID: VP:; BINH_XUYEN_I - KCN Bình Xuyên I; BINH_XUYEN_II - KCN Bình Xuyên II; THANG_LONG_III - KCN Thăng Long III (Vĩnh Phúc); KHAI_QUANG - KCN Khai Quang; BA_THIEN_II - KCN Bá Thiện II PT:; PHU_HA - KCN Phú Hà; CAM_KHE - KCN Cẩm Khê HB:; LUONG_SON - KCN Lương Sơn BGBN:; VAN_TRUNG - KCN Vân Trung; QUANG_CHAU - KCN Quang Châu; Dùng updateOrCreate/upsert theo code.; Không seed "Vân Chung".; Không tự đoán ward; pivot ward seed phải dùng mapping code đã xác minh và tách riêng.; Không xóa KCN cũ; report duplicate theo normalized name.
- **GATE:** Đủ 10 code, đúng tên và branch.; Seeder hai lần không trùng.; Không hardcode ID.; Không tạo ward giả.; KCN cũ có cùng tên được xử lý có kiểm soát.; Test seed idempotency PASS.
- **DONE:** 10 KCN chuẩn được seed đúng branch và idempotent.
- **NEXT:** TASK 3.3 - Cập nhật HR KCN và validation ward

## TASK 3.3 - Cập nhật HR KCN và validation ward

- **RUN:** `/task-cycle TASK 3.3`
- **MODE:** `FEATURE+AUTH`
- **KEY:** Cập nhật quản trị KCN theo schema mới.; HR form cho chọn branch quản lý và một/nhiều ward.; Province chỉ dùng để lọc danh sách ward, không lưu trùng vào KCN.; Cho đặt một ward primary.; KCN đã có job không được hard-delete; chỉ `is_active=false`.; Branch admin chỉ quản lý KCN branch mình nếu policy cho phép; super_admin quản lý toàn bộ.; Backend xác thực ward active, không tin frontend.; Giữ route/response tương thích trong giai đoạn chuyển tiếp.
- **GATE:** UI chọn ward theo province.; Backend chặn ward inactive/không tồn tại.; Primary ward hợp lệ.; Policy branch đúng.; KCN có job không bị xóa cứng.; Public KCN cũ không 404 do chuyển schema.
- **DONE:** HR KCN quản lý được branch + ward an toàn, không còn phụ thuộc bắt buộc vào một administrative_unit.
- **NEXT:** TASK 4.1 - Tạo industries
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 4 - DANH MỤC CHUYÊN NGÀNH VÀ LOẠI HÌNH CÔNG VIỆC

**PHASE GATE:** Danh mục chuẩn cho tag, lọc, tìm kiếm và báo cáo.

## TASK 4.1 - Tạo industries

- **RUN:** `/task-cycle TASK 4.1`
- **MODE:** `CATALOG`
- **KEY:** Tạo danh mục chuyên ngành. Tạo migration/model/factory/seeder/admin CRUD cho `industries`:; name, slug unique, description nullable, sort_order, is_active;; không hard-delete khi đã có job;; slug ổn định và unique kể cả soft/inactive record;; seed tối thiểu các ngành thực tế: Sản xuất điện tử, Công nghệ thông tin, Marketing, Bảo vệ, Lao động phổ thông, Khác.; Có search/filter và test idempotent.
- **GATE:** Seed chạy lại không trùng.; Slug unique.; Inactive không xuất hiện trong form tạo mới.; Record đang được job tham chiếu không xóa cứng.; CRUD có authorization.
- **DONE:** Industry là danh mục ổn định, sẵn sàng nối Job.
- **NEXT:** TASK 4.2 - Tạo employment_types và backfill enum cũ

## TASK 4.2 - Tạo employment_types và backfill enum cũ

- **RUN:** `/task-cycle TASK 4.2`
- **MODE:** `CATALOG+BACKFILL`
- **KEY:** Chuyển loại hình công việc từ string sang danh mục. Tạo `employment_types`:; name, slug unique, description, sort_order, is_active. Seed đúng 5 giá trị:; full-time - Việc chính thức; part-time - Việc làm bán thời gian; temporary - Nhân viên thời vụ; freelance - Nghề tự do; internship - Thực tập Thêm `jobs.employment_type_id` nullable trước. Backfill từ `jobs.employment_type` hiện tại bằng mapping rõ ràng. Giữ cột string cũ trong giai đoạn chuyển tiếp; model đọc FK trước, fallback enum cũ. Không đổi tất cả UI trong task này nếu chưa nối Job ở Phần 6.
- **GATE:** 5 seed đúng slug/tên.; Mapping enum cũ không mất dữ liệu.; Có report giá trị không map được.; Seeder/backfill idempotent.; Cột cũ chưa bị xóa.; Model không gây N+1 ở danh sách.
- **DONE:** Mọi job cũ có employment_type_id hợp lệ hoặc nằm trong report cần xử lý.
- **NEXT:** TASK 5.1 - Expand company với địa chỉ trụ sở và đại diện
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 5 - CÔNG TY VÀ KHÁCH HÀNG

**PHASE GATE:** Một pháp nhân là một company; địa điểm tuyển thuộc Job.

## TASK 5.1 - Expand company với địa chỉ trụ sở và đại diện

- **RUN:** `/task-cycle TASK 5.1`
- **MODE:** `SCHEMA`
- **KEY:** Thêm thông tin công ty theo Baseline mà chưa xóa company_locations/contacts. Thêm nullable trước:; headquarters_ward_id FK wards;; headquarters_address_detail;; legal_representative_name;; legal_representative_phone và normalized field nếu cần;; status mapping active/inactive/blocked hoặc giữ status hiện tại với ADR rõ ràng. Cập nhật model, FormRequest, Action và HR form. Quick create vẫn chỉ cần name để không phá Job Draft Contract; các trường HQ/đại diện bắt buộc khi publish job company nếu nghiệp vụ chốt như vậy, không bắt buộc ở quick create.
- **GATE:** Quick create công ty vẫn hoạt động.; Công ty đầy đủ lưu ward HQ và đại diện.; Phone được normalize.; Ward active validation.; Không xóa company_locations/contacts.; Company cũ vẫn hiển thị.
- **DONE:** Company hỗ trợ HQ/đại diện mới mà luồng quick create cũ vẫn PASS.
- **NEXT:** TASK 5.2 - Backfill company HQ và chống trùng

## TASK 5.2 - Backfill company HQ và chống trùng

- **RUN:** `/task-cycle TASK 5.2`
- **MODE:** `BACKFILL`
- **KEY:** Backfill company từ location/contact cũ. Tạo command dry-run + execute:; Chọn primary/active company_location có dữ liệu rõ nhất làm HQ candidate;; map administrative_unit cũ sang ward qua mapping Task 1.2;; chọn primary active company_contact làm legal representative candidate;; không tự ghi khi có nhiều location/contact ngang nhau; đưa vào report ambiguous;; tạo report duplicate company theo normalized name + representative phone + HQ ward;; không merge tự động.
- **GATE:** Dry-run không ghi DB.; Ambiguous được báo cáo.; Chạy lại idempotent.; Không merge company tự động.; Tổng công ty được đối chiếu.; Dữ liệu contact/location cũ không bị đổi.
- **DONE:** Company cũ được backfill an toàn hoặc có report xác minh thủ công đầy đủ.
- **NEXT:** TASK 5.3 - Chuyển Company CRUD sang cấu trúc mới

## TASK 5.3 - Chuyển Company CRUD sang cấu trúc mới

- **RUN:** `/task-cycle TASK 5.3`
- **MODE:** `FEATURE`
- **KEY:** Chuyển luồng tạo/sửa Company sang HQ trực tiếp.; Form chính không bắt người dùng tạo company_location để lưu trụ sở.; Trang công ty public lấy HQ mới, fallback location cũ trong release chuyển tiếp.; Company có thể có nhiều job ở nhiều ward/KCN; không sinh company mới theo địa điểm job.; Giữ routes company-locations/contacts ở chế độ legacy/read-only hoặc ẩn khỏi luồng chính; chưa xóa controller/table.; Thêm cảnh báo gần trùng, không chặn chỉ theo tên.
- **GATE:** Một company tạo được nhiều job khác địa điểm.; Public company hiển thị địa chỉ mới/fallback đúng.; Không bắt location để quick create.; Không tạo company trùng theo địa điểm.; Legacy data không 404.; Test company management và public page PASS.
- **DONE:** Company đại diện pháp nhân, không còn là container bắt buộc của địa điểm job.
- **NEXT:** TASK 6.1 - Expand schema Job mới
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 6 - TÁI CẤU TRÚC LÕI JOBS

**PHASE GATE:** Mở rộng Job theo Baseline 1.1 nhưng giữ workflow hiện có.

## TASK 6.1 - Expand schema Job mới

- **RUN:** `/task-cycle TASK 6.1`
- **MODE:** `SCHEMA`
- **KEY:** Thêm schema Job mục tiêu bằng migration additive. Giữ các cột đang đúng: owner_branch_id, title, quantity, min_age, max_age, job_description, requirements, benefits, history fields. Thêm: job_type: company/direct;; company_id chuyển nullable an toàn ở migration riêng;; employer_display_name nullable;; industry_id nullable trước;; employment_type_id đã có từ Task 4.2;; work_ward_id nullable trước;; industrial_park_id nullable;; work_address_detail nullable;; salary_type: range/negotiable;; total_income_text LONGTEXT nullable trước. Không xóa ngay: employment_type string;; salary_period/salary_description/salary_base;; job_locations/company_locations dependency;; application_documents. Thêm index phục vụ public filter và check constraints hợp lý. Không đặt NOT NULL cho dữ liệu mới trước backfill.
- **GATE:** Migration additive, rollback không mất cột cũ.; company_id nullable được xử lý đúng FK.; Index có cho owner_branch/status, industry, employment type, ward, KCN, salary.; Không trùng constraint cũ.; DB trống và DB có dữ liệu đều migrate được.; Model fillable/casts/relations cập nhật nhưng có fallback.
- **DONE:** Schema mới tồn tại song song, toàn bộ Job cũ vẫn chạy bằng fallback.
- **NEXT:** TASK 6.2 - Backfill Job từ cấu trúc cũ

## TASK 6.2 - Backfill Job từ cấu trúc cũ

- **RUN:** `/task-cycle TASK 6.2`
- **MODE:** `BACKFILL`
- **KEY:** Viết `jobs:backfill-v1-1` có dry-run/batch/resume. Mapping: job_type=company cho job có company_id;; industry_id: map company.industry hoặc dữ liệu text theo bảng mapping; không rõ -> Industry "Khác" + report;; employment_type_id từ Task 4.2;; work_ward_id/industrial_park_id/work_address_detail từ primary job_location -> company_location -> mapping ward;; salary_type=negotiable khi salary_period=negotiable; ngược lại range khi có số;; total_income_text ghép có kiểm soát từ salary_description/benefits, không làm mất nội dung gốc;; không chọn ward nếu location cũ chỉ có province/legacy district. Report: missing ward;; KCN không khớp ward;; missing industry/employment type;; salary không parse được;; nhiều primary location hoặc không có primary.
- **GATE:** Dry-run, batch, resume, idempotent.; Không sửa cột cũ.; Không đoán ward/KCN sai.; Count job trước/sau khớp.; Report đủ nhóm lỗi.; Có test dữ liệu edge case.
- **DONE:** Mọi job được backfill hoặc nằm trong danh sách xử lý thủ công có lý do rõ ràng.
- **NEXT:** TASK 6.3 - Chuyển SaveJobDraftAction và FormRequest sang Job mới

## TASK 6.3 - Chuyển SaveJobDraftAction và FormRequest sang Job mới

- **RUN:** `/task-cycle TASK 6.3`
- **MODE:** `FEATURE`
- **KEY:** Chuyển luồng create/update Job sang schema mới. Cập nhật:; StoreJobRequest, UpdateJobRequest;; SaveJobDraftAction;; JobController create/edit;; Blade form HR;; Job model scopes/relations. Quy tắc: job_type=company => company_id required, employer_display_name null;; job_type=direct => company_id null, employer_display_name required;; owner_branch_id: super_admin chọn; branch_admin/staff mặc định branch mình theo quyền;; work_ward_id cho phép thiếu ở draft nhưng bắt buộc khi publish;; industrial_park_id optional; nếu có, ward phải thuộc pivot;; industry/employment type cho phép thiếu ở draft, bắt buộc publish;; quantity > 0 khi publish;; min_age <= max_age;; salary_type validation loại trừ;; không còn tạo/sync JobLocation khi lưu Job mới;; giữ read fallback cho job legacy chưa chuyển.
- **GATE:** Tạo được company job và direct job.; Direct job không cần company.; Công ty Hà Nội có 2 job ở 2 tỉnh.; KCN/ward mismatch bị chặn backend.; Draft cho phép thiếu field theo contract.; Update không đổi owner_branch trái route transfer.; Existing JobManagement tests được cập nhật hợp lý, không xóa coverage.
- **DONE:** Luồng HR ghi hoàn toàn vào schema Job mới và mọi invariant được backend bảo vệ.
- **NEXT:** TASK 6.4 - Cập nhật Publish Predicate và lifecycle

## TASK 6.4 - Cập nhật Publish Predicate và lifecycle

- **RUN:** `/task-cycle TASK 6.4`
- **MODE:** `WORKFLOW`
- **KEY:** Viết lại điều kiện publish dựa trên schema mới nhưng giữ workflow/history/locking hiện có. Publish bắt buộc: status hợp lệ;; branch active và có CTA;; job_type/company consistency;; industry active;; employment type active;; quantity > 0;; work_ward active;; nếu KCN thì ward thuộc KCN, KCN active và branch mapping hợp lệ theo quy tắc đã chốt;; job_description, total_income_text, requirements không trống;; salary_type hợp lệ:; range: salary_min/salary_max > 0 và min <= max;; negotiable: hai cột null;; ít nhất 1 work shift nếu vẫn giữ nghiệp vụ hiện tại;; verification còn hạn hoặc super_admin override có lý do;; company contact chỉ kiểm tra nếu vẫn dùng. Không phá ChangeJobStatusAction, status histories, row lock hoặc verification history.
- **GATE:** Mọi lỗi publish trả 422 có field/message rõ.; Authorization vẫn 403, không trộn 422.; Direct job publish được đúng điều kiện.; Job company inactive bị chặn.; Ward/KCN mismatch bị chặn.; Salary mode loại trừ đúng.; Concurrency/status history tests PASS.
- **DONE:** Job mới chỉ published khi đủ dữ liệu và toàn bộ lifecycle cũ vẫn an toàn.
- **NEXT:** TASK 6.5 - Ngừng đọc/ghi job_locations trong luồng chính

## TASK 6.5 - Ngừng đọc/ghi job_locations trong luồng chính

- **RUN:** `/task-cycle TASK 6.5`
- **MODE:** `CUTOVER`
- **KEY:** Chuyển active read sang địa chỉ trực tiếp trên jobs.; Public/Hr query, cards, detail, filters, snapshots và export dùng work_ward/industrial_park mới.; Với record legacy chưa backfill, chỉ fallback `job_locations` có telemetry/log, không tạo write mới.; GuardJobReferencesAction đổi sang guard company optional, branch, ward, KCN, industry, employment type.; DuplicateJobAction phải copy field mới nhưng không copy application/history/verification; ảnh xử lý theo quyết định Task 7.; Không drop bảng cũ.
- **GATE:** Job mới không cần job_locations để hiển thị/publish.; Legacy job vẫn đọc được trong transition.; Có log/count fallback để biết khi nào contract.; Duplicate job đúng field mới.; Không N+1 relations mới.; Tests public/HR PASS.
- **DONE:** Luồng chính không phụ thuộc job_locations; chỉ còn fallback có theo dõi cho dữ liệu cũ.
- **NEXT:** TASK 7.1 - Schema, storage và security cho job_images
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 7 - NHIỀU ẢNH CHO VIỆC LÀM

**PHASE GATE:** Gallery ảnh an toàn, đúng quyền, storage nhất quán và hỗ trợ SEO.

## TASK 7.1 - Schema, storage và security cho job_images

- **RUN:** `/task-cycle TASK 7.1`
- **MODE:** `FILE+SCHEMA`
- **KEY:** Tạo `job_images` và tầng lưu file. Schema:; id, job_id, file_path, original_name, mime_type, file_size, alt_text, is_primary, sort_order, timestamps.; unique/constraint tối đa một primary/job. Rules:; tối đa 10 ảnh/job;; JPG/JPEG/PNG/WEBP;; tối đa 5 MB/ảnh, cấu hình tập trung;; kiểm tra MIME thật;; tên file nội bộ ngẫu nhiên;; lưu disk phù hợp; ảnh public có URL qua storage đã kiểm soát;; transaction DB + cleanup file khi lỗi;; không xóa file trước khi DB operation thành công. Tạo model, factory, policy/action và test file fake.
- **GATE:** Không upload PHP/SVG/HTML giả ảnh.; Limit số lượng/dung lượng.; Một primary/job.; Rollback không để file rác.; Xóa/replace kiểm tra quyền branch.; Storage fake tests PASS.
- **DONE:** Upload layer an toàn, không file rác và policy đúng branch.
- **NEXT:** TASK 7.2 - HR gallery: upload, sắp xếp, chọn ảnh đại diện

## TASK 7.2 - HR gallery: upload, sắp xếp, chọn ảnh đại diện

- **RUN:** `/task-cycle TASK 7.2`
- **MODE:** `FEATURE`
- **KEY:** Tích hợp quản lý ảnh vào trang create/edit Job.; Dùng multipart form.; Cho upload nhiều ảnh, xem preview, đặt alt text, chọn primary, sắp xếp và xóa.; Không dùng drag/drop phức tạp nếu không cần; có thể dùng sort_order số/nút lên xuống với Alpine.; Khi tạo Job thất bại, không lưu ảnh mồ côi.; Khi duplicate Job, mặc định không copy binary; có thể không copy ảnh và ghi rõ UI.; Hiển thị lỗi từng file.
- **GATE:** Upload 1-n ảnh.; Đặt primary/sort đúng.; Xóa ảnh có confirm và authorization.; Validation error giữ dữ liệu form hợp lý.; Không có orphan file.; Mobile HR dùng được.
- **DONE:** HR quản lý gallery đầy đủ và DB/storage luôn nhất quán.
- **NEXT:** TASK 7.3 - Public gallery và SEO ảnh

## TASK 7.3 - Public gallery và SEO ảnh

- **RUN:** `/task-cycle TASK 7.3`
- **MODE:** `PUBLIC+SEO`
- **KEY:** Hiển thị ảnh Job công khai.; Job card dùng primary image nếu có, fallback logo/placeholder hiện tại.; Job detail hiển thị gallery theo sort_order.; Alt text từ job_images.alt_text, fallback an toàn từ title.; Eager load tối thiểu để tránh N+1.; Ảnh responsive, lazy-load, kích thước ổn định tránh layout shift.; JSON-LD/OG image chỉ dùng URL đã escape và hợp lệ.
- **GATE:** Job không ảnh vẫn đẹp.; Primary đúng ở card/OG.; Gallery đúng thứ tự.; Không XSS qua alt/original_name/path.; Không N+1.; Public Job tests và SEO tests PASS.
- **DONE:** Ảnh hiển thị nhanh, đúng thứ tự, an toàn và không làm hỏng SEO.
- **NEXT:** TASK 8.1 - Cập nhật query/filter public
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 8 - BỘ LỌC VÀ GIAO DIỆN VIỆC LÀM MỚI

**PHASE GATE:** Chuyển public query/UI sang schema mới, giữ URL, CTA và SEO.

## TASK 8.1 - Cập nhật query/filter public

- **RUN:** `/task-cycle TASK 8.1`
- **MODE:** `QUERY`
- **KEY:** Refactor JobIndexRequest, scopes và controller theo schema mới. Bộ lọc:; q tìm title và employer/company phù hợp;; province (join ward->province);; ward;; industrial_park_id;; industry_id;; employment_type_id;; company_id;; salary bucket và lựa chọn negotiable;; work shift, shuttle, accommodation nếu vẫn giữ;; sort latest, salary_desc, urgent. Yêu cầu:; multi-select OR trong cùng nhóm, AND giữa các nhóm;; chỉ active categories/KCN/address cho form;; chỉ publiclyListed;; whitelist sort;; index/query plan hợp lý;; không dùng company_location/job_location cho job mới.
- **GATE:** Filter từng trường và kết hợp.; Province suy ra từ ward.; Tag employment type hiện đúng.; Negotiable không lọt vào bucket số.; Query không N+1.; Invalid ID/value không gây 500.
- **DONE:** Bộ lọc phản ánh đúng dữ liệu mới và có test tổ hợp.
- **NEXT:** TASK 8.2 - Cập nhật job card/detail và related jobs

## TASK 8.2 - Cập nhật job card/detail và related jobs

- **RUN:** `/task-cycle TASK 8.2`
- **MODE:** `PUBLIC UI`
- **KEY:** Cập nhật giao diện public Job. Card hiển thị nhanh: title;; company hoặc employer_display_name;; employment type tag;; industry;; province/ward/KCN;; khoảng lương hoặc thỏa thuận;; primary image;; tuyển gấp/quyền lợi hiện có. Detail: gallery;; mô tả công việc;; tổng thu nhập;; yêu cầu + giấy tờ;; quantity, age, employment type, industry;; địa chỉ chính xác;; CTA branch. Related jobs ưu tiên cùng industry, employment type, province/company; không phụ thuộc company ngành text cũ.
- **GATE:** Direct job không hiện company trống.; Company job hiện đúng công ty.; Address/KCN không mâu thuẫn.; Nội dung text giữ line break và escape XSS.; Related jobs hợp lý.; Mobile và accessibility đạt chuẩn hiện có.
- **DONE:** Public site hiển thị đầy đủ nghiệp vụ mới, không regression CTA/SEO/mobile.
- **NEXT:** TASK 9.1 - Expand candidates theo form đã chốt
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 9 - ỨNG VIÊN, FORM BỔ SUNG VÀ CV PDF

**PHASE GATE:** Mở rộng Candidate và guest form, CV private, giữ duplicate/merge/idempotency.

## TASK 9.1 - Expand candidates theo form đã chốt

- **RUN:** `/task-cycle TASK 9.1`
- **MODE:** `SCHEMA+PII`
- **KEY:** Thêm trường Candidate mới, giữ duplicate/merge/anonymize hiện có. Thêm nullable trước:; current_ward_id;; current_address_detail hoặc tái sử dụng address_detail có tài liệu rõ;; marital_status;; foreign_language;; ethnicity;; citizen_id_number (mã hóa/cast encrypted nếu phù hợp);; citizen_id_issued_date;; citizen_id_issued_place;; work_experience hoặc dùng experience_summary có mapping rõ;; personal_introduction. Số điện thoại chính vẫn nằm ở candidate_contacts và là khóa match. Backfill current_ward_id qua Task 1.2. Cập nhật anonymization để xóa/mask tất cả PII mới; merge phải xử lý conflict không ghi đè mù quáng.
- **GATE:** Không tạo phone cột trùng trong candidates.; PII mới được anonymize.; Merge conflict được xử lý rõ.; CCCD không hiển thị toàn bộ trong list.; Ward active validation.; Existing duplicate/merge tests vẫn PASS.
- **DONE:** Candidate chứa đủ field mới mà duplicate/merge/anonymize vẫn an toàn.
- **NEXT:** TASK 9.2 - Tạo candidate_documents và private download

## TASK 9.2 - Tạo candidate_documents và private download

- **RUN:** `/task-cycle TASK 9.2`
- **MODE:** `FILE+SECURITY`
- **KEY:** Tạo candidate_documents cho CV/avatar. Schema:; candidate_id NOT NULL;; application_id nullable;; document_type: cv/avatar;; file_path, original_name, mime_type, file_size, uploaded_at. Rules:; CV: PDF thật, tối đa 5 MB, 1 file/lần nộp;; avatar: JPG/PNG/WEBP tối đa 5 MB;; private disk, không URL public trực tiếp;; download/view qua controller có Policy theo application/candidate branch;; response Content-Disposition an toàn;; không log nội dung/file path nhạy cảm;; cleanup file khi transaction lỗi/xóa hợp lệ.
- **GATE:** CV PDF giả bị chặn.; URL trực tiếp không truy cập được.; Staff khác branch 403.; Super admin/đúng branch tải được.; File name header chống injection.; Storage fake tests PASS.
- **DONE:** Tài liệu được lưu private và chỉ người có quyền mới truy cập.
- **NEXT:** TASK 9.3 - Xây form ứng tuyển cơ bản + bổ sung

## TASK 9.3 - Xây form ứng tuyển cơ bản + bổ sung

- **RUN:** `/task-cycle TASK 9.3`
- **MODE:** `PUBLIC FORM`
- **KEY:** Cập nhật form "Ứng tuyển ngay" trên job detail. Thông tin cơ bản bắt buộc: full_name;; gender;; date_of_birth;; province UI;; current_ward_id;; address_detail optional;; phone;; alternate_phone optional. Thông tin bổ sung: avatar;; marital_status;; education_level;; foreign_language;; ethnicity;; CCCD/date/place;; work_experience;; personal_introduction;; CV PDF. Giữ:; guest application;; submission_token;; honeypot;; consent;; throttle. Form multipart, dropdown province->ward, backend chỉ lưu/validate ward.
- **GATE:** Basic fields đúng bắt buộc.; Extra fields optional.; CV/ảnh upload đúng rule.; Validation lỗi mở đúng collapse và giữ old input không nhạy cảm.; Không repopulate CCCD/file nguy hiểm.; Mobile, label, keyboard và 48px controls.
- **DONE:** Form mới hoạt động đầy đủ và không làm yếu idempotency/consent/security.
- **NEXT:** TASK 9.4 - Cập nhật CreateApplicationAction và snapshots

## TASK 9.4 - Cập nhật CreateApplicationAction và snapshots

- **RUN:** `/task-cycle TASK 9.4`
- **MODE:** `WORKFLOW`
- **KEY:** Tích hợp field mới và tài liệu vào CreateApplicationAction. Giữ nguyên: phone lock;; DB transaction;; submission token idempotency;; merged family same-job contract;; duplicate review;; status/branch histories. Thêm: match/update candidate theo nguyên tắc: chỉ fill field trống tự động; conflict quan trọng phải flag/review, không ghi đè mù;; alternate phone tạo CandidateContact phụ;; snapshot field form cần thiết nhưng không lưu plaintext CCCD nếu không cần;; job_snapshot dùng industry/employment type/new address/new salary;; CV gắn application_id; avatar có thể application_id nullable;; file handling có compensation cleanup nếu transaction thất bại;; source website giữ nguyên.
- **GATE:** Double submit vẫn trả cùng application.; Cùng phone + job khác tạo application mới.; Cùng phone + cùng job không tạo trùng.; Field cũ không bị ghi đè trái quy tắc.; CV gắn đúng application.; Transaction/file cleanup đúng.; Existing Public Application tests vẫn bảo toàn coverage.
- **DONE:** Luồng tạo application mới giữ toàn bộ bảo vệ cũ và lưu đúng dữ liệu/tài liệu mới.
- **NEXT:** TASK 9.5 - HR xem hồ sơ và tài liệu ứng viên

## TASK 9.5 - HR xem hồ sơ và tài liệu ứng viên

- **RUN:** `/task-cycle TASK 9.5`
- **MODE:** `HR UI`
- **KEY:** Cập nhật HR Candidate/Application show.; Hiển thị field cơ bản/bổ sung có phân nhóm.; CCCD mask mặc định; chỉ role được phép mới reveal nếu thật sự cần, có activity log.; Danh sách CV/avatar; download qua private controller.; Hiển thị ward/province.; Giữ timeline, notes, contacts, appointments, duplicate review và merged family.; Không lộ dữ liệu branch khác.
- **GATE:** Mask CCCD.; Download policy đúng.; Timeline cũ vẫn đủ.; Không N+1 document/ward.; Candidate merged/anonymized hiển thị đúng.; Mobile HR dùng được.
- **DONE:** Nhân viên xử lý được hồ sơ đầy đủ mà PII/file vẫn được bảo vệ.
- **NEXT:** TASK 10.1 - Giữ workflow hiện tại, chỉ cập nhật snapshot/labels
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 10 - APPLICATION WORKFLOW VÀ ACTIVITY LOG

**PHASE GATE:** Giữ workflow Application; thêm audit log có kiểm soát PII.

## TASK 10.1 - Giữ workflow hiện tại, chỉ cập nhật snapshot/labels

- **RUN:** `/task-cycle TASK 10.1`
- **MODE:** `WORKFLOW`
- **KEY:** Rà soát Application workflow sau thay đổi Job/Candidate. Không đổi tên/bảng hiện có nếu không cần:; application_status_histories;; application_contact_attempts;; application_appointments;; application_notes;; application_branch_histories. Cập nhật:; job/address/category snapshot;; label hiển thị trạng thái theo nghiệp vụ đã chốt;; branch owner copy từ owner_branch_id Job mới;; filters theo industry, employment type, KCN, ward/province nếu cần. Không bỏ reopen, workflow_cycle, transfer validation, contact result hay appointment reschedule contract.
- **GATE:** Pipeline new -> started vẫn chạy.; Reopen/close/transfer histories đúng.; Job direct/company đều tạo application.; Branch snapshot không suy ra động.; Existing workflow tests PASS.; Không migration đổi tên lịch sử không cần thiết.
- **DONE:** Workflow ứng viên hiện tại giữ nguyên tính đúng đắn trên schema mới.
- **NEXT:** TASK 10.2 - Tạo activity_logs cho thao tác quản trị quan trọng

## TASK 10.2 - Tạo activity_logs cho thao tác quản trị quan trọng

- **RUN:** `/task-cycle TASK 10.2`
- **MODE:** `AUDIT LOG`
- **KEY:** Thêm activity_logs tổng quát, không thay thế domain histories. Log tối thiểu:; actor user;; action code;; subject type/id;; branch context;; old_values/new_values đã redact;; reason/metadata;; IP/user agent nếu phù hợp;; created_at append-only. Áp dụng cho: role/branch user change;; company update/restore;; job create/update/publish/pause/close/transfer;; KCN mapping change;; candidate reveal/anonymize/merge;; document download/delete. Không lưu password, token, raw CCCD hoặc nội dung CV.
- **GATE:** Append-only.; PII redaction.; Domain histories vẫn tồn tại riêng.; Actor/subject/branch rõ.; Không log gây rollback nghiệp vụ ngoài ý muốn; có chiến lược fail-open/fail-closed rõ.; Test các action quan trọng.
- **DONE:** Mọi thao tác quản trị quan trọng có log an toàn, không trùng vai trò với history domain.
- **NEXT:** TASK 11.1 - Cập nhật KPI theo dimensions mới
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 11 - DASHBOARD VÀ BÁO CÁO

**PHASE GATE:** KPI/export đúng dimensions mới, không double count và không lộ branch.

## TASK 11.1 - Cập nhật KPI theo dimensions mới

- **RUN:** `/task-cycle TASK 11.1`
- **MODE:** `REPORT`
- **KEY:** Mở rộng dashboard/report trên schema mới. Thêm filter/KPI: branch;; company/direct;; job;; province/ward;; KCN;; industry;; employment type;; source;; application stage;; staff/actor nơi nghiệp vụ hiện có hỗ trợ. Giữ branch scope và KPI cũ. Không tạo BI phức tạp. Định nghĩa công thức bằng query/test rõ ràng, tránh double count candidate merged/application reopened.
- **GATE:** Super admin toàn hệ thống; branch roles chỉ branch mình.; Không double count workflow cycle.; Direct job được tính đúng.; Filter category/address/KCN đúng.; Query hiệu năng có index/eager aggregate.; Dashboard tests cập nhật.
- **DONE:** Số liệu mới đúng, được test bằng dataset đối chiếu và không lộ branch.
- **NEXT:** TASK 11.2 - Cập nhật CSV export

## TASK 11.2 - Cập nhật CSV export

- **RUN:** `/task-cycle TASK 11.2`
- **MODE:** `EXPORT`
- **KEY:** Cập nhật application CSV export theo dữ liệu mới. Bổ sung cột cần thiết:; job type/company/employer;; industry;; employment type;; province/ward/KCN;; salary display;; candidate ward;; trạng thái/source/branch. Không xuất CCCD đầy đủ, đường dẫn private hoặc dữ liệu consent nhạy cảm. Giữ CsvSanitizer chống formula injection và export_logs.
- **GATE:** CSV UTF-8/mở được.; Formula injection vẫn bị neutralize.; Branch scope đúng.; Không PII vượt phạm vi.; Cột mới đúng và không N+1.; Export tests PASS.
- **DONE:** CSV phản ánh schema mới, an toàn và đúng phân quyền.
- **NEXT:** TASK 12.1 - Tạo command audit readiness trước cutover
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 12 - BACKFILL TỔNG, CUTOVER VÀ LOẠI BỎ FALLBACK

**PHASE GATE:** Audit readiness, cutover read/write; contract legacy chỉ ở release riêng.

## TASK 12.1 - Tạo command audit readiness trước cutover

- **RUN:** `/task-cycle TASK 12.1`
- **MODE:** `AUDIT`
- **KEY:** Tạo `refactor:audit-readiness` read-only. Báo cáo:; branch user sai role/branch;; branch thiếu ward/CTA;; KCN thiếu branch/code/ward;; company thiếu HQ cần thiết;; job thiếu job_type, industry, employment type, ward, salary mode, content;; KCN/ward mismatch;; candidate thiếu ward/basic field theo form mới;; orphan/duplicate document/image;; application orphan/duplicate;; số lần legacy fallback còn được dùng. Có output console + JSON, exit code non-zero khi còn critical. Không tự sửa dữ liệu.
- **GATE:** Read-only.; Exit code đúng.; Count và ID mẫu đủ điều tra.; Phân mức critical/warning.; Chạy được trên dữ liệu lớn theo chunk.; Có test từng loại lỗi.
- **DONE:** Audit readiness phát hiện được mọi blocker trước switch.
- **NEXT:** TASK 12.2 - Chuyển hoàn toàn read path sang schema mới

## TASK 12.2 - Chuyển hoàn toàn read path sang schema mới

- **RUN:** `/task-cycle TASK 12.2`
- **MODE:** `CUTOVER READ`
- **KEY:** Release B: tắt legacy read fallback sau khi audit critical=0.; Public/Hr/model/scopes/export/dashboard chỉ dùng provinces/wards, direct job fields, new categories, new KCN relation.; Xóa code fallback, không xóa bảng/cột.; Thêm guard/metrics để phát hiện record thiếu dữ liệu mới.; Chạy full regression và smoke test.
- **GATE:** Audit critical=0 trước khi merge.; Không còn active read vào administrative_units/company_locations/job_locations cho các phân hệ đã; chuyển.; Dữ liệu public/HR đầy đủ.; Full suite/build PASS.; Có rollback về release trước.
- **DONE:** Hệ thống chạy hoàn toàn bằng schema mới nhưng bảng cũ vẫn giữ để rollback.
- **NEXT:** TASK 12.3 - Chuyển hoàn toàn write path và khóa bảng legacy

## TASK 12.3 - Chuyển hoàn toàn write path và khóa bảng legacy

- **RUN:** `/task-cycle TASK 12.3`
- **MODE:** `CUTOVER WRITE`
- **KEY:** Khóa write vào cấu trúc legacy.; Gỡ/disable route tạo/sửa administrative units, company locations và job locations khỏi luồng chính.; Nếu cần giữ trang legacy, chỉ read-only cho super_admin.; Thêm test bảo đảm không controller/action nào ghi cột/bảng cũ.; Dừng dual-write sau thời gian quan sát đã thống nhất.; Không drop schema.
- **GATE:** Không write legacy.; Route legacy trả 404/403/read-only theo quyết định.; Không ảnh hưởng data history.; Full tests PASS.; Audit fallback=0.
- **DONE:** Luồng đọc/ghi mới hoàn toàn, legacy chỉ còn dữ liệu lưu trữ tạm thời.
- **NEXT:** TASK 13.1 - Release gate tổng thể
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 13 - RELEASE, DEPLOY VÀ NGHIỆM THU CUỐI

**PHASE GATE:** Release gate, staging/UAT, production runbook và rollback.

## TASK 13.1 - Release gate tổng thể

- **RUN:** `/task-cycle TASK 13.1`
- **MODE:** `RELEASE GATE`
- **KEY:** Chạy release gate cho Baseline 1.1. Bắt buộc:; composer validate;; php artisan about;; route:list;; migrate status/pretend;; full test suite;; npm run build;; Claude config checks;; audit readiness;; storage permission/private file test;; backup + restore-test trên môi trường tách biệt;; security review branch isolation, upload, CSV, XSS, CSRF, rate limit;; update ERD, DB Dictionary, Route Map, Core Flows, Acceptance Criteria, Project Status. Không sửa code trong bước gate ngoài lỗi nhỏ được tách thành task riêng.
- **GATE:** Tất cả lệnh có output lưu lại.; Không có critical audit.; Full suite PASS hoặc blocker môi trường được giải quyết, không chấp nhận "bỏ qua".; Build PASS.; Backup restore thật PASS.; Docs khớp source.
- **DONE:** Reviewer độc lập trả RELEASE READY dựa trên bằng chứng lệnh thật.
- **NEXT:** TASK 13.2 - Staging deployment và smoke test

## TASK 13.2 - Staging deployment và smoke test

- **RUN:** `/task-cycle TASK 13.2`
- **MODE:** `STAGING`
- **KEY:** Deploy staging theo expand/backfill/switch.; Backup staging;; deploy code;; migrate --force;; sync provinces/wards;; seed reference data;; chạy backfill và audit;; build/upload public assets;; kiểm tra storage link/private download;; smoke public và /hr;; theo dõi log/query/error trong khoảng quan sát. Không contract schema cũ ở staging cùng release switch đầu tiên.
- **GATE:** Trang chủ/list/detail/filter.; Company và direct job.; HR create/edit/publish Job.; Ảnh Job.; Form ứng tuyển + CV.; Application workflow.; Branch isolation.; Các tiêu chí còn lại trong checklist TASK phải được đối chiếu đầy đủ.
- **DONE:** Tất cả flow chính PASS trên staging và rollback đã được diễn tập.
- **NEXT:** TASK 13.3 - Production deployment và theo dõi

## TASK 13.3 - Production deployment và theo dõi

- **RUN:** `/task-cycle TASK 13.3`
- **MODE:** `PRODUCTION`
- **KEY:** Lập runbook production, không tự deploy nếu chưa được phê duyệt. Runbook: thời gian maintenance;; người chịu trách nhiệm;; backup source/DB/storage;; checksum/restore verification;; lệnh deploy theo thứ tự;; lệnh backfill/audit;; smoke test;; điều kiện rollback định lượng;; cách rollback code, DB và file;; giám sát lỗi, queue, storage, 5xx, tốc độ query và số đơn ứng tuyển;; báo cáo sau deploy.
- **GATE:** Không có lệnh destructive không kiểm soát.; Có thời điểm dừng/rollback rõ.; Có backup được restore thử.; Có owner cho từng bước.; Có monitoring sau deploy.; Contract schema cũ không nằm trong deploy switch đầu tiên.
- **DONE:** Runbook được duyệt, có rollback thực tế và mọi gate staging đã PASS.
- **NEXT:** TASK 12.4 - Contract schema cũ ở release riêng (chỉ sau cửa sổ production ổn định)
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.

# PHẦN 12 - BACKFILL TỔNG, CUTOVER VÀ LOẠI BỎ FALLBACK

**PHASE GATE:** Audit readiness, cutover read/write; contract legacy chỉ ở release riêng.

## TASK 12.4 - Contract schema cũ ở release riêng

- **RUN:** `/task-cycle TASK 12.4`
- **MODE:** `CONTRACT`
- **KEY:** Release C: chỉ lập và thực hiện contract khi hệ thống đã ổn định qua ít nhất một chu kỳ vận hành được duyệt. Điều kiện trước: backup restore test PASS;; audit critical=0, fallback=0;; source không còn reference;; báo cáo đối chiếu được ký duyệt;; rollback plan có bản DB trước contract. Có thể drop sau cùng:; administrative_units và mapping chuyển tiếp;; company_locations;; job_locations;; cột jobs.employment_type string;; cột legacy address/salary không còn dùng;; route/controller/model/factory/test legacy. Mỗi nhóm drop là migration riêng. Không gom tất cả trong một migration.
- **GATE:** Không source reference.; Backup restore thử thành công.; Mỗi migration contract nhỏ.; Rollback/documented restore rõ.; DB integrity sau drop PASS.; Full tests/build/smoke PASS.
- **DONE:** Schema cũ được loại bỏ an toàn ở release riêng, không mất dữ liệu cần thiết.
- **NEXT:** NONE - chương trình hoàn tất; tiếp tục theo dõi vận hành và release policy.
- **PHASE-END:** Có - task-cycle phải đối chiếu phase gate trước khi handoff.
