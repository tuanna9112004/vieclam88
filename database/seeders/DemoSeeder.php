<?php

namespace Database\Seeders;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\CompanyLocation;
use App\Models\IndustrialPark;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\JobWorkShift;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Work Shifts
        $this->call(WorkShiftSeeder::class);

        // 2. Clear old demo jobs & mappings
        JobWorkShift::query()->delete();
        JobLocation::query()->delete();
        Job::query()->delete();

        // 3. Ensure Default Admin User exists
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@vieclam88.vn'],
            [
                'name' => 'Quản trị viên Hệ thống',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // 4. Administrative Units (Provinces)
        $provBacNinh = AdministrativeUnit::query()->firstOrCreate(
            ['official_code' => 'PROV-BN'],
            ['name' => 'Bắc Ninh', 'slug' => 'bac-ninh', 'type' => 'province', 'is_active' => true]
        );

        $provBacGiang = AdministrativeUnit::query()->firstOrCreate(
            ['official_code' => 'PROV-BG'],
            ['name' => 'Bắc Giang', 'slug' => 'bac-giang', 'type' => 'province', 'is_active' => true]
        );

        $provHanoi = AdministrativeUnit::query()->firstOrCreate(
            ['official_code' => 'PROV-HN'],
            ['name' => 'Hà Nội', 'slug' => 'ha-noi', 'type' => 'province', 'is_active' => true]
        );

        $provThaiNguyen = AdministrativeUnit::query()->firstOrCreate(
            ['official_code' => 'PROV-TN'],
            ['name' => 'Thái Nguyên', 'slug' => 'thai-nguyen', 'type' => 'province', 'is_active' => true]
        );

        // 5. Main Branches
        $branchHanoi = Branch::query()->firstOrCreate(
            ['code' => 'BR-HN-01'],
            [
                'name' => 'Cơ sở Hà Nội (Trụ sở chính)',
                'phone' => '0981123456',
                'zalo' => '0981123456',
                'administrative_unit_id' => $provHanoi->id,
                'address_detail' => 'Số 15 Phạm Hùng, Cầu Giấy, Hà Nội',
                'status' => 'active',
            ]
        );

        $branchBacNinh = Branch::query()->firstOrCreate(
            ['code' => 'BR-BN-01'],
            [
                'name' => 'Cơ sở Bắc Ninh',
                'phone' => '0982654321',
                'zalo' => '0982654321',
                'administrative_unit_id' => $provBacNinh->id,
                'address_detail' => 'Đường Trần Hưng Đạo, TP. Bắc Ninh',
                'status' => 'active',
            ]
        );

        $branchBacGiang = Branch::query()->firstOrCreate(
            ['code' => 'BR-BG-01'],
            [
                'name' => 'Cơ sở Bắc Giang',
                'phone' => '0983888999',
                'zalo' => '0983888999',
                'administrative_unit_id' => $provBacGiang->id,
                'address_detail' => 'KCN Quang Châu, Việt Yên, Bắc Giang',
                'status' => 'active',
            ]
        );

        // 6. Industrial Parks
        $ipYenPhong = IndustrialPark::query()->firstOrCreate(
            ['name' => 'KCN Yên Phong'],
            [
                'administrative_unit_id' => $provBacNinh->id,
                'slug' => 'kcn-yen-phong',
                'official_name' => 'Khu Công Nghiệp Yên Phong',
                'address_detail' => 'Huyện Yên Phong, Tỉnh Bắc Ninh',
                'is_active' => true,
            ]
        );

        $ipQuangChau = IndustrialPark::query()->firstOrCreate(
            ['name' => 'KCN Quang Châu'],
            [
                'administrative_unit_id' => $provBacGiang->id,
                'slug' => 'kcn-quang-chau',
                'official_name' => 'Khu Công Nghiệp Quang Châu',
                'address_detail' => 'Huyện Việt Yên, Tỉnh Bắc Giang',
                'is_active' => true,
            ]
        );

        $ipVanTrung = IndustrialPark::query()->firstOrCreate(
            ['name' => 'KCN Vân Trung'],
            [
                'administrative_unit_id' => $provBacGiang->id,
                'slug' => 'kcn-van-trung',
                'official_name' => 'Khu Công Nghiệp Vân Trung',
                'address_detail' => 'Huyện Việt Yên, Tỉnh Bắc Giang',
                'is_active' => true,
            ]
        );

        $ipThangLong = IndustrialPark::query()->firstOrCreate(
            ['name' => 'KCN Thăng Long'],
            [
                'administrative_unit_id' => $provHanoi->id,
                'slug' => 'kcn-thang-long',
                'official_name' => 'Khu Công Nghiệp Thăng Long',
                'address_detail' => 'Huyện Đông Anh, Thành phố Hà Nội',
                'is_active' => true,
            ]
        );

        // 7. Companies
        $compFoxconn = Company::query()->firstOrCreate(
            ['name' => 'Tập đoàn Điện tử Foxconn Việt Nam'],
            [
                'public_id' => (string) Str::ulid(),
                'slug' => 'foxconn-viet-nam',
                'short_name' => 'Foxconn',
                'industry' => 'Sản xuất linh kiện điện tử',
                'is_verified' => true,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $compLuxshare = Company::query()->firstOrCreate(
            ['name' => 'Công ty TNHH Luxshare - ICT Việt Nam'],
            [
                'public_id' => (string) Str::ulid(),
                'slug' => 'luxshare-ict-viet-nam',
                'short_name' => 'Luxshare-ICT',
                'industry' => 'Thiết bị âm thanh & linh kiện cáp sạc',
                'is_verified' => true,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $compSamsung = Company::query()->firstOrCreate(
            ['name' => 'Công ty TNHH Samsung Electronics Việt Nam (SEV)'],
            [
                'public_id' => (string) Str::ulid(),
                'slug' => 'samsung-electronics-viet-nam',
                'short_name' => 'Samsung SEV',
                'industry' => 'Điện thoại di động & Thiết bị điện tử',
                'is_verified' => true,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $compCanon = Company::query()->firstOrCreate(
            ['name' => 'Công ty TNHH Canon Việt Nam'],
            [
                'public_id' => (string) Str::ulid(),
                'slug' => 'canon-viet-nam',
                'short_name' => 'Canon Việt Nam',
                'industry' => 'Máy ảnh & Thiết bị quang học',
                'is_verified' => true,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $compGoertek = Company::query()->firstOrCreate(
            ['name' => 'Công ty TNHH Goertek Vina'],
            [
                'public_id' => (string) Str::ulid(),
                'slug' => 'goertek-vina',
                'short_name' => 'Goertek Vina',
                'industry' => 'Acoustic & Linh kiện điện tử',
                'is_verified' => true,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // 8. Company Locations
        $locFoxconnBG = CompanyLocation::query()->firstOrCreate(
            ['name' => 'Nhà máy Foxconn Quang Châu'],
            [
                'company_id' => $compFoxconn->id,
                'industrial_park_id' => $ipQuangChau->id,
                'administrative_unit_id' => $provBacGiang->id,
                'address_detail' => 'Lô R, KCN Quang Châu, Việt Yên, Bắc Giang',
                'status' => 'active',
            ]
        );

        $locLuxshareBG = CompanyLocation::query()->firstOrCreate(
            ['name' => 'Nhà máy Luxshare Vân Trung'],
            [
                'company_id' => $compLuxshare->id,
                'industrial_park_id' => $ipVanTrung->id,
                'administrative_unit_id' => $provBacGiang->id,
                'address_detail' => 'Lô E, KCN Vân Trung, Việt Yên, Bắc Giang',
                'status' => 'active',
            ]
        );

        $locSamsungBN = CompanyLocation::query()->firstOrCreate(
            ['name' => 'Tổ hợp Nhà máy Samsung Yên Phong'],
            [
                'company_id' => $compSamsung->id,
                'industrial_park_id' => $ipYenPhong->id,
                'administrative_unit_id' => $provBacNinh->id,
                'address_detail' => 'KCN Yên Phong, Huyện Yên Phong, Tỉnh Bắc Ninh',
                'status' => 'active',
            ]
        );

        $locCanonHN = CompanyLocation::query()->firstOrCreate(
            ['name' => 'Nhà máy Canon Thăng Long'],
            [
                'company_id' => $compCanon->id,
                'industrial_park_id' => $ipThangLong->id,
                'administrative_unit_id' => $provHanoi->id,
                'address_detail' => 'Lô A1, KCN Thăng Long, Đông Anh, Hà Nội',
                'status' => 'active',
            ]
        );

        $locGoertekBN = CompanyLocation::query()->firstOrCreate(
            ['name' => 'Nhà máy Goertek Yên Phong'],
            [
                'company_id' => $compGoertek->id,
                'industrial_park_id' => $ipYenPhong->id,
                'administrative_unit_id' => $provBacNinh->id,
                'address_detail' => 'KCN Yên Phong mở rộng, Bắc Ninh',
                'status' => 'active',
            ]
        );

        // 9. Company Contacts
        $contactFoxconn = CompanyContact::query()->firstOrCreate(
            ['phone' => '0912345678'],
            [
                'company_id' => $compFoxconn->id,
                'name' => 'Bộ phận Tuyển Dụng Foxconn',
                'position' => 'Phòng Nhân Sự',
                'email' => 'tuyendung@foxconn.com.vn',
                'is_primary' => true,
                'is_public' => true,
                'status' => 'active',
            ]
        );

        $contactLuxshare = CompanyContact::query()->firstOrCreate(
            ['phone' => '0923456789'],
            [
                'company_id' => $compLuxshare->id,
                'name' => 'Ban Tuyển Dụng Luxshare-ICT',
                'position' => 'Trưởng phòng Nhân sự',
                'email' => 'hr@luxshare-ict.com',
                'is_primary' => true,
                'is_public' => true,
                'status' => 'active',
            ]
        );

        $contactSamsung = CompanyContact::query()->firstOrCreate(
            ['phone' => '0934567890'],
            [
                'company_id' => $compSamsung->id,
                'name' => 'Trung tâm Tuyển dụng Samsung',
                'position' => 'Tuyển dụng Lao động phổ thông',
                'email' => 'sev.recruitment@samsung.com',
                'is_primary' => true,
                'is_public' => true,
                'status' => 'active',
            ]
        );

        $contactCanon = CompanyContact::query()->firstOrCreate(
            ['phone' => '0945678901'],
            [
                'company_id' => $compCanon->id,
                'name' => 'Phòng Tuyển dụng Canon',
                'position' => 'Bộ phận Tuyển dụng',
                'email' => 'tuyendung@canon-vn.com.vn',
                'is_primary' => true,
                'is_public' => true,
                'status' => 'active',
            ]
        );

        // 10. Work Shifts
        $shiftDay = WorkShift::where('code', 'day')->first();
        $shiftNight = WorkShift::where('code', 'night')->first();
        $shiftRotating = WorkShift::where('code', 'rotating')->first();
        $shiftTwoShift = WorkShift::where('code', 'two_shift')->first();

        // 11. Create Realistic High Quality Jobs
        $jobsData = [
            [
                'code' => 'JOB-FOX-01',
                'title' => 'Nam/Nữ Công Nhân Lắp Ráp Linh Kiện Điện Tử (Foxconn) - Thu Nhập 10-14 Triệu',
                'company' => $compFoxconn,
                'contact' => $contactFoxconn,
                'branch' => $branchBacGiang,
                'location' => $locFoxconnBG,
                'shifts' => [$shiftTwoShift?->id, $shiftRotating?->id],
                'salary_min' => 10000000,
                'salary_max' => 14000000,
                'salary_base' => 5300000,
                'salary_description' => 'Lương cơ bản 5.300.000đ + Phụ cấp đi lại 500k + Phụ cấp nhà ở 800k + Chuyên cần 500k + Tiền tăng ca (150% - 200%). Tổng thu nhập thực nhận từ 10 - 14 triệu/tháng.',
                'job_description' => "• Lắp ráp linh kiện điện tử, tai nghe, bảng mạch theo dây chuyền công nghệ cao.\n• Kiểm tra ngoại quan sản phẩm (VISUAL/QC) đảm bảo tiêu chuẩn chất lượng.\n• Đứng máy SMT hoặc làm tại phòng sạch theo sự phân công của quản lý.",
                'requirements' => "• Nam/Nữ từ 18 - 38 tuổi. Không yêu cầu kinh nghiệm, được đào tạo bài bản.\n• Sức khỏe tốt, không mắc bệnh truyền nhiễm, chịu khó, nhanh nhẹn.\n• Không yêu cầu bằng cấp, chỉ cần biết đọc biết viết.",
                'benefits' => "• Bao ăn 2 - 3 bữa miễn phí tại nhà ăn công ty (thực đơn phong phú tự chọn).\n• Ở Ký túc xá điều hòa, nóng lạnh miễn phí 100%.\n• Xe đưa đón công nhân đi làm từ các huyện Bắc Giang, Bắc Ninh, Hà Nội.\n• Thưởng chuyên cần, thưởng xuất sắc hàng tháng, thưởng Tết Âm Lịch.\n• Đóng BHXH, BHYT, BHTN đầy đủ theo luật lao động.",
                'quantity' => 200,
                'gender_requirement' => 'any',
                'min_age' => 18,
                'max_age' => 38,
                'has_shuttle_bus' => true,
                'shuttle_bus_details' => 'Đưa đón miễn phí từ Hà Nội, Bắc Ninh, Bắc Giang',
                'has_accommodation' => true,
                'accommodation_details' => 'KTX điều hòa, wifi, bình nóng lạnh miễn phí',
                'has_meal_support' => true,
                'meal_support_details' => 'Bao ăn 2-3 bữa chính/ngày',
                'is_urgent' => true,
            ],
            [
                'code' => 'JOB-LUX-02',
                'title' => 'Tuyển Gấp 150 Công Nhân Kiểm Hàng QC & Đóng Gói (Luxshare-ICT) - Đi Làm Ngay',
                'company' => $compLuxshare,
                'contact' => $contactLuxshare,
                'branch' => $branchBacGiang,
                'location' => $locLuxshareBG,
                'shifts' => [$shiftDay?->id, $shiftNight?->id],
                'salary_min' => 9500000,
                'salary_max' => 13500000,
                'salary_base' => 5100000,
                'salary_description' => 'Lương cơ bản 5.100.000đ + Phụ cấp ca đêm (150%) + Phụ cấp thâm niên + Tăng ca đều đặn 2-3h/ngày.',
                'job_description' => "• Kiểm tra chất lượng thành phẩm tai nghe Airpods, cáp sạc, linh kiện điện tử.\n• Đóng gói sản phẩm vào thùng carton, dán tem nhãn mác.\n• Kiểm đếm số lượng hàng hóa và đóng hàng vào kho xuất.",
                'requirements' => "• Nam/Nữ từ 18 - 40 tuổi. Tốt nghiệp THCS trở lên.\n• Mắt sáng, không mù màu, không xăm hình lộ ở cổ/tay.\n• Có thể làm ca xoay (Ca ngày / Ca đêm).",
                'benefits' => "• Miễn phí 100% bữa ăn giữa ca tại nhà máy.\n• Hỗ trợ KTX sạch đẹp, đầy đủ máy giặt, điều hòa, wifi.\n• Có xe bus đưa đón miễn phí theo tuyến cố định.\n• Được tham gia các hoạt động văn hóa, du lịch hàng năm.",
                'quantity' => 150,
                'gender_requirement' => 'any',
                'min_age' => 18,
                'max_age' => 40,
                'has_shuttle_bus' => true,
                'shuttle_bus_details' => 'Xe bus Luxshare đưa đón các tuyến Bắc Giang, Bắc Ninh',
                'has_accommodation' => true,
                'accommodation_details' => 'Ký túc xá tiêu chuẩn khách sạn',
                'has_meal_support' => true,
                'meal_support_details' => 'Bao ăn ca miễn phí',
                'is_urgent' => true,
            ],
            [
                'code' => 'JOB-SAM-03',
                'title' => 'Nhân Viên Kho & Lái Xe Nâng Điện (Samsung SEV) - Lương 11 - 16 Triệu',
                'company' => $compSamsung,
                'contact' => $contactSamsung,
                'branch' => $branchBacNinh,
                'location' => $locSamsungBN,
                'shifts' => [$shiftTwoShift?->id],
                'salary_min' => 11000000,
                'salary_max' => 16000000,
                'salary_base' => 6000000,
                'salary_description' => 'Lương cơ bản 6.000.000đ + Phụ cấp bằng lái xe nâng 1.000.000đ + Phụ cấp chuyên cần 600k + Tăng ca.',
                'job_description' => "• Vận hành xe nâng điện di chuyển hàng hóa, linh kiện điện thoại trong kho.\n• Sắp xếp hàng hóa lên giá kệ theo đúng sơ đồ quản lý kho ngăn nắp.\n• Thực hiện xuất - nhập hàng theo lệnh của quản lý kho.",
                'requirements' => "• Nam từ 20 - 38 tuổi. Có chứng chỉ hoặc bằng lái xe nâng điện.\n• Trung thực, cẩn thận, chịu được áp lực công việc kho vận.",
                'benefits' => "• Xe bus Samsung đưa đón miễn phí hơn 100 tuyến khắp các tỉnh miền Bắc.\n• Ăn uống miễn phí tại siêu canteen Samsung (hàng chục món tự chọn).\n• Chế độ phúc lợi đứng đầu thị trường: Thưởng 2 lần/năm, bảo hiểm toàn diện 24/7.",
                'quantity' => 50,
                'gender_requirement' => 'male',
                'min_age' => 20,
                'max_age' => 38,
                'has_shuttle_bus' => true,
                'shuttle_bus_details' => 'Hệ thống bus Samsung hơn 100 tuyến toàn miền Bắc',
                'has_accommodation' => true,
                'accommodation_details' => 'Ký túc xá cao cấp Samsung',
                'has_meal_support' => true,
                'meal_support_details' => 'Canteen 5 sao tự chọn món',
                'is_urgent' => true,
            ],
            [
                'code' => 'JOB-CAN-04',
                'title' => 'Công Nhân May Công Nghiệp & Đóng Gói (Canon Việt Nam) - Lương 10 - 15 Triệu',
                'company' => $compCanon,
                'contact' => $contactCanon,
                'branch' => $branchHanoi,
                'location' => $locCanonHN,
                'shifts' => [$shiftDay?->id],
                'salary_min' => 10000000,
                'salary_max' => 15000000,
                'salary_base' => 5500000,
                'salary_description' => 'Lương cứng 5.5tr + Lương sản phẩm + Phụ cấp tay nghề + Chuyên cần 800k/tháng.',
                'job_description' => "• May vỏ túi, bao bì, phụ kiện túi xách thiết bị máy ảnh Canon.\n• Vận hành máy may công nghiệp 1 kim, 2 kim hoặc máy vắt sổ.\n• Kiểm tra đường may đảm bảo đúng tiêu chuẩn sản xuất.",
                'requirements' => "• Nam/Nữ từ 18 - 45 tuổi.\n• Ưu tiên người biết sử dụng máy may (Chấp nhận lao động phổ thông được đào tạo miễn phí).",
                'benefits' => "• Thưởng tay nghề hàng tháng.\n• Bao ăn trưa + bữa phụ khi tăng ca.\n• Hỗ trợ tiền nhà trọ 600.000đ/tháng cho lao động ở xa.\n• Thưởng các ngày lễ Tết, lương tháng 13 đầy đủ.",
                'quantity' => 80,
                'gender_requirement' => 'any',
                'min_age' => 18,
                'max_age' => 45,
                'has_shuttle_bus' => false,
                'shuttle_bus_details' => null,
                'has_accommodation' => false,
                'accommodation_details' => null,
                'has_meal_support' => true,
                'meal_support_details' => 'Bao ăn trưa + bữa phụ tăng ca',
                'is_urgent' => false,
            ],
            [
                'code' => 'JOB-GOE-05',
                'title' => 'Nhân Viên Vận Hành Máy SMT & Sản Xuất Tai Nghe (Goertek Vina) - 10-15 Triệu',
                'company' => $compGoertek,
                'contact' => null,
                'branch' => $branchBacNinh,
                'location' => $locGoertekBN,
                'shifts' => [$shiftRotating?->id, $shiftTwoShift?->id],
                'salary_min' => 10000000,
                'salary_max' => 15000000,
                'salary_base' => 5400000,
                'salary_description' => 'Lương cơ bản 5.400.000đ + Phụ cấp phòng sạch 400k + Chuyên cần 500k + Thưởng ca đêm + Tăng ca.',
                'job_description' => "• Vận hành máy dán linh kiện bề mặt SMT tự động.\n• Tiếp nguyên vật liệu (cuộn linh kiện) cho máy SMT.\n• Xử lý các lỗi máy cơ bản và báo cáo kỹ thuật viên khi có sự cố.",
                'requirements' => "• Nam/Nữ từ 18 - 35 tuổi. Tốt nghiệp THPT/Trung cấp trở lên hoặc lao động phổ thông tiếp thu nhanh.\n• Sức khỏe tốt, có thể làm việc phòng sạch.",
                'benefits' => "• Làm việc trong môi trường phòng sạch điều hòa 24/24 mát mẻ, hiện đại.\n• Phụ cấp phòng sạch và độc hại hàng tháng.\n• Bữa ăn miễn phí chất lượng cao tại canteen công ty.\n• Xe buýt đưa đón công nhân khắp tỉnh Bắc Ninh và Hà Nội.",
                'quantity' => 120,
                'gender_requirement' => 'any',
                'min_age' => 18,
                'max_age' => 35,
                'has_shuttle_bus' => true,
                'shuttle_bus_details' => 'Xe bus Goertek chạy liên tỉnh Bắc Ninh - Hà Nội',
                'has_accommodation' => true,
                'accommodation_details' => 'KTX khép kín đầy đủ tiện nghi',
                'has_meal_support' => true,
                'meal_support_details' => 'Bao ăn ca tại nhà ăn máy lạnh',
                'is_urgent' => false,
            ],
            [
                'code' => 'JOB-FOX-06',
                'title' => 'Tuyển 50 Nam Thợ Hàn CO2 / Hàn TIG Cơ Khí (Foxconn Yên Phong) - Lương 12 - 18 Triệu',
                'company' => $compFoxconn,
                'contact' => $contactFoxconn,
                'branch' => $branchBacNinh,
                'location' => $locSamsungBN,
                'shifts' => [$shiftDay?->id],
                'salary_min' => 12000000,
                'salary_max' => 18000000,
                'salary_base' => 6500000,
                'salary_description' => 'Lương cứng 6.5tr + Phụ cấp độc hại + Đơn giá sản phẩm/tăng ca. Thu nhập thực nhận từ 12 - 18 triệu.',
                'job_description' => "• Thực hiện các công việc hàn CO2, hàn TIG kết cấu khung vỏ thiết bị cơ khí, giá kệ công nghiệp.\n• Đọc bản vẽ kỹ thuật cơ bản và gia công hàn hoàn thiện chi tiết.\n• Bảo dưỡng thiết bị máy hàn được giao.",
                'requirements' => "• Nam từ 20 - 42 tuổi, sức khỏe tốt.\n• Có tay nghề hàn CO2 hoặc TIG (Chấp nhận thợ mới tốt nghiệp học nghề cơ khí).",
                'benefits' => "• Cấp phát đầy đủ bảo hộ lao động đạt chuẩn an toàn.\n• Hỗ trợ nhà ở KTX và phụ cấp tiền ăn 1.200.000đ/tháng.\n• Thưởng năng suất lao động hàng tháng, bảo hiểm 24/7.",
                'quantity' => 50,
                'gender_requirement' => 'male',
                'min_age' => 20,
                'max_age' => 42,
                'has_shuttle_bus' => true,
                'shuttle_bus_details' => 'Có xe đưa đón công nhân',
                'has_accommodation' => true,
                'accommodation_details' => 'Hỗ trợ KTX hoặc tiền trọ 1.2tr/tháng',
                'has_meal_support' => true,
                'meal_support_details' => 'Bao ăn ca',
                'is_urgent' => true,
            ],
        ];

        foreach ($jobsData as $index => $item) {
            $title = $item['title'];
            $slug = Str::slug($title) . '-' . ($index + 1);

            /** @var Job $job */
            $job = Job::query()->create([
                'public_id' => (string) Str::ulid(),
                'company_id' => $item['company']->id,
                'company_contact_id' => $item['contact']?->id,
                'owner_branch_id' => $item['branch']->id,
                'code' => $item['code'],
                'title' => $title,
                'slug' => $slug,
                'employment_type' => 'full_time',
                'quantity' => $item['quantity'],
                'gender_requirement' => $item['gender_requirement'],
                'min_age' => $item['min_age'],
                'max_age' => $item['max_age'],
                'salary_min' => $item['salary_min'],
                'salary_max' => $item['salary_max'],
                'salary_base' => $item['salary_base'],
                'salary_period' => 'month',
                'currency' => 'VND',
                'salary_description' => $item['salary_description'],
                'job_description' => $item['job_description'],
                'requirements' => $item['requirements'],
                'benefits' => $item['benefits'],
                'has_shuttle_bus' => $item['has_shuttle_bus'],
                'shuttle_bus_details' => $item['shuttle_bus_details'],
                'has_accommodation' => $item['has_accommodation'],
                'accommodation_details' => $item['accommodation_details'],
                'has_meal_support' => $item['has_meal_support'],
                'meal_support_details' => $item['meal_support_details'],
                'is_urgent' => $item['is_urgent'],
                'status' => 'published',
                'published_at' => now()->subDays(rand(1, 10)),
                'created_by' => $admin->id,
            ]);

            // JobLocation
            JobLocation::query()->create([
                'job_id' => $job->id,
                'company_location_id' => $item['location']->id,
            ]);

            // JobWorkShift
            foreach ($item['shifts'] as $shiftId) {
                if ($shiftId) {
                    JobWorkShift::query()->create([
                        'job_id' => $job->id,
                        'work_shift_id' => $shiftId,
                    ]);
                }
            }
        }
    }
}
