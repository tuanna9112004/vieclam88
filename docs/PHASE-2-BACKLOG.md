# Phase 2 Backlog

Nguồn tổng hợp chính thức cho mọi chức năng **ngoài phạm vi Phase 1** (ADR-057, Phase 1 Plan
Baseline v1.0). Không thiết kế chi tiết schema ở đây trừ khi tài liệu hiện tại đã có nội dung
cần bảo tồn (ví dụ cột đã bị loại khỏi Phase 1 và ghi chú "thêm lại bằng migration mới"). Không
mục nào trong file này được phép trở thành blocker của migration hoặc go-live Phase 1
(`ROADMAP.md` mục "Phân loại blocker").

Quy tắc thêm mục mới: chỉ thêm khi có yêu cầu nghiệp vụ thật, không tự suy đoán tính năng.

| Chức năng | Mục tiêu | Lý do chưa làm ở Phase 1 | Phụ thuộc | Ưu tiên sơ bộ | Rủi ro nếu triển khai | Ảnh hưởng migration Phase 1 |
|---|---|---|---|---|---|---|
| **Lead** (điện thoại/Zalo/form "yêu cầu tư vấn") | Ghi nhận nhu cầu ứng viên trước khi đủ thông tin tạo Application chính thức | Phase 1 chỉ xử lý hồ sơ từ form ứng tuyển; Lead cần quy trình chuyển đổi riêng chưa được xác nhận (ADR-021) | Không phụ thuộc Phase 1 | Trung bình | Trùng lặp với Application nếu chuyển đổi sai; cần chống double-convert | Không — không bảng/cột nào giữ chỗ |
| **Assignment/claim hồ sơ** (nhận xử lý, gán nhân viên, round-robin) | Phân công hồ sơ cho từng nhân viên thay vì cả cơ sở | Phase 1 xử lý theo cơ sở là đủ cho quy mô hiện tại; assignment cần UI/quy tắc tranh chấp phức tạp hơn (ADR-021) | Cần Phase 1 ổn định trước | Trung bình | Race condition khi nhiều Staff cùng nhận 1 hồ sơ; cần lock riêng | Không |
| **Candidate Account** (đăng ký/đăng nhập/`/tai-khoan`/theo dõi trạng thái) | Ứng viên tự theo dõi hồ sơ, không cần gọi hỏi | Tăng đáng kể độ phức tạp auth (2 loại người dùng); ứng viên Phase 1 luôn guest (ADR-028) | Cần thiết kế `candidates.user_id`, luồng OTP/mật khẩu riêng | Thấp–Trung bình | Lộ dữ liệu chéo nếu phân quyền candidate-user sai | Không — `candidates.user_id`, `users.role=candidate` thêm bằng migration mới khi duyệt |
| **Favorites** (lưu việc làm) | Ứng viên đánh dấu việc quan tâm | Phụ thuộc Candidate Account (không có ý nghĩa với guest); ưu tiên thấp cho MVP | Candidate Account | Thấp | Không đáng kể | Không — bảng `favorites` chưa tồn tại |
| **Zalo API / tự động gọi-nhắn tin** | Giảm thao tác thủ công khi liên hệ ứng viên | Cần hợp đồng/API Zalo OA, chi phí và độ ổn định chưa đánh giá | Hạ tầng gửi tin nhắn ngoài | Trung bình | Vi phạm chính sách Zalo nếu gửi sai tần suất; cần rate limit riêng | Không |
| **Cộng tác viên / hoa hồng / referral** | Mở rộng nguồn ứng viên qua giới thiệu có thưởng | Cần quy tắc đối soát/thanh toán hoa hồng chưa xác nhận (ADR-012, ADR-029) | Cần bảng `referrers`, quy trình tài chính | Thấp | Gian lận giới thiệu nếu không kiểm soát chặt | Không — `applications.referral_code` thêm lại bằng migration mới khi duyệt |
| **Import dữ liệu hàng loạt** | Nhập dữ liệu cũ (Excel) vào hệ thống nhanh | Không nằm trong 6 luồng cốt lõi; rủi ro trùng lặp/PII nếu import ẩu (ADR-029) | Cần quy tắc dedupe khi import | Trung bình | Tạo hàng loạt Candidate trùng nếu dùng sai Duplicate Contract | Không — `actor_type=import` thêm lại khi có luồng import thật |
| **AI matching / gợi ý Job** | Gợi ý việc phù hợp cho ứng viên, xếp hạng ứng viên cho HR | Cần dữ liệu huấn luyện đủ lớn, chưa có ở giai đoạn đầu | Dữ liệu vận hành Phase 1 tích lũy đủ | Thấp | Gợi ý sai lệch/thiên vị nếu mô hình kém; chi phí vận hành | Không |
| **Dashboard/BI nâng cao, dự báo** | Báo cáo sâu, dự báo tuyển dụng | Phase 1 chỉ cần KPI cơ bản theo cơ sở (`docs/CORE-FLOWS.md` mục 9.1); BI cần kho dữ liệu riêng | Dữ liệu vận hành đủ lớn | Thấp | Chi phí hạ tầng (ETL/warehouse) không tương xứng quy mô hiện tại | Không |
| **Auto-pause Job** | Tự động tạm dừng Job lâu chưa xác minh | Rủi ro pause nhầm Job đang cần tuyển gấp; mặc định tắt (`job_auto_pause_enabled=false`, ADR-042/049) | Job Verification Scheduler đã có (Phase 1) | Trung bình | Pause nhầm gây mất cơ hội tuyển nếu ngưỡng sai | Không nếu tắt; nếu bật cần thêm `actor_type`/`changed_by` nullable cho `job_status_histories` bằng migration riêng lúc đó |
| **SMS/Email automation, Notification nâng cao** | Nhắc lịch, thông báo tự động cho Staff/ứng viên | Chưa có hạ tầng gửi mail/SMS được thiết kế; chi phí vận hành | Chọn nhà cung cấp SMS/Email | Thấp | Spam/chi phí nếu cấu hình sai tần suất | Không |
| **Báo cáo tài chính, quản lý hợp đồng khách hàng, hồ sơ pháp lý doanh nghiệp sâu** | Theo dõi công nợ, hợp đồng, hồ sơ pháp lý Company | Ngoài phạm vi tuyển dụng cốt lõi; `companies` Phase 1 cố ý tối giản (không mã số thuế/trụ sở — mục 0.2) | Module kế toán/pháp lý riêng | Thấp | Trộn lẫn trách nhiệm với hệ thống kế toán hiện có (nếu có) | Không |
| **RBAC nhiều tầng, full audit log tổng quát** | Phân quyền chi tiết hơn 2 role, log mọi thay đổi mọi bảng | 2 role (staff/admin) đủ cho Phase 1; audit trail theo từng action đã đáp ứng yêu cầu hiện tại (ADR-019) | Nhu cầu nhiều role thực tế phát sinh | Thấp | Overhead vận hành/hiệu năng nếu log mọi thay đổi | Không |
