<?php
declare(strict_types=1);

namespace Library\Services;

use RuntimeException;

final class LoanReportCsvExporter
{
    public static function write(mixed $output, array $rows): void
    {
        if (!is_resource($output)) {
            throw new RuntimeException('Luồng xuất CSV không hợp lệ.');
        }
        if (fwrite($output, "\xEF\xBB\xBF") === false ||
            fputcsv($output, [
                'Mã phiếu', 'Mã thẻ', 'Độc giả', 'Mã sách', 'Tên sách', 'Mã bản sao',
                'Ngày mượn', 'Hạn trả', 'Ngày trả', 'Trạng thái', 'Mức phí/ngày',
                'Phí phát sinh', 'Đã thanh toán', 'Miễn giảm', 'Còn phải thu',
            ], ',', '"', '\\', "\r\n") === false) {
            throw new RuntimeException('Không thể ghi phần đầu báo cáo CSV.');
        }

        foreach ($rows as $row) {
            $charged = (float) $row['charged_amount'];
            $paid = (float) $row['paid_amount'];
            $waived = (float) $row['waived_amount'];
            $cells = [
                $row['loan_code'],
                $row['card_number'],
                $row['full_name'],
                $row['book_code'],
                $row['title'],
                $row['copy_code'],
                $row['borrowed_at'],
                $row['due_date'],
                $row['returned_at'] ?? '',
                ['returned' => 'Đã trả', 'overdue' => 'Quá hạn', 'borrowed' => 'Đang mượn'][$row['item_status']] ?? $row['item_status'],
                $row['daily_fine'],
                $row['charged_amount'],
                $row['paid_amount'],
                $row['waived_amount'],
                max(0, $charged - $paid - $waived),
            ];
            if (fputcsv(
                $output,
                array_map(self::safeCell(...), $cells),
                ',',
                '"',
                '\\',
                "\r\n"
            ) === false) {
                throw new RuntimeException('Không thể ghi dữ liệu báo cáo CSV.');
            }
        }
    }

    public static function safeCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');
        return preg_match('/^[\x00-\x20]*[=+\-@]/u', $cell) ? "'" . $cell : $cell;
    }
}
