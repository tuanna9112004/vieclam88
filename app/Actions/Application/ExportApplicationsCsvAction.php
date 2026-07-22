<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ExportLog;
use App\Models\User;
use App\Support\CsvSanitizer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * docs/CORE-FLOWS.md mục 9, ADR-019, ADR-053 — Xuất CSV danh sách Application theo quyền & filter.
 * Staff chỉ được xuất dữ liệu thuộc cơ sở mình; Admin xuất theo filter hoặc toàn bộ.
 * Tự động phòng chống CSV Formula Injection và ghi nhật ký export_logs.
 */
class ExportApplicationsCsvAction
{
    public function handle(array $filters, User $actor): StreamedResponse
    {
        $query = Application::query()
            ->with(['job.company', 'ownerBranch']);

        // 1. Phân quyền & Branch Isolation
        if ($actor->isStaff()) {
            $query->where('owner_branch_id', $actor->branch_id);
        } else if (! empty($filters['owner_branch_id'])) {
            $branchIds = (array) $filters['owner_branch_id'];
            $query->whereIn('owner_branch_id', $branchIds);
        }

        // 2. Bộ lọc tìm kiếm
        if (! empty($filters['q'])) {
            $kw = trim($filters['q']);
            $query->where(function ($q) use ($kw) {
                $q->where('submitted_full_name', 'like', "%{$kw}%")
                  ->orWhere('submitted_phone', 'like', "%{$kw}%");
            });
        }

        if (! empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['uncontacted'])) {
            $query->whereDoesntHave('contactAttempts');
        }

        if (! empty($filters['needs_duplicate_review'])) {
            $query->where('needs_duplicate_review', true);
        }

        $query->orderByDesc('id');

        // 3. Ghi log khởi tạo export_logs
        $fileName = 'applications_export_' . now()->format('Ymd_His') . '.csv';

        $exportLog = ExportLog::query()->create([
            'exported_by' => $actor->id,
            'export_type' => 'applications_csv',
            'filters' => $filters,
            'row_count' => 0,
            'file_name' => $fileName,
        ]);

        // 4. Stream response dòng theo dòng
        return response()->stream(function () use ($query, $exportLog) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM
            fputs($handle, "\xEF\xBB\xBF");

            // Header (chỉ gồm các cột nghiệp vụ cần thiết, không lộ PII thừa)
            fputcsv($handle, [
                'ID Hồ Sơ',
                'Mã Hồ Sơ',
                'Họ Tên Ứng Viên',
                'Số Điện Thoại',
                'Tên Công Việc',
                'Tên Công Ty',
                'Cơ Sở Phụ Trách',
                'Giai Đoạn',
                'Lý Do Đóng',
                'Ngày Nộp',
            ]);

            $rowCount = 0;

            $query->chunk(500, function ($applications) use ($handle, &$rowCount) {
                foreach ($applications as $app) {
                    $rowCount++;

                    fputcsv($handle, [
                        $app->id,
                        CsvSanitizer::escape($app->code ?? '#'.$app->id),
                        CsvSanitizer::escape($app->submitted_full_name),
                        CsvSanitizer::escape($app->submitted_phone),
                        CsvSanitizer::escape($app->job?->title),
                        CsvSanitizer::escape($app->job?->company?->name),
                        CsvSanitizer::escape($app->ownerBranch?->name),
                        CsvSanitizer::escape($app->stage),
                        CsvSanitizer::escape($app->close_reason),
                        $app->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);

            // Cập nhật row_count thực tế sau khi stream hoàn tất
            $exportLog->update(['row_count' => $rowCount]);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
