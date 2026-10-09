<?php
declare(strict_types=1);

namespace Library\Repositories;

use InvalidArgumentException;
use PDO;
use RuntimeException;

final class SystemSettingRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function string(string $key, string $fallback): string
    {
        $statement = $this->db->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :key');
        $statement->execute(['key' => $key]);
        $value = $statement->fetchColumn();
        return $value === false ? $fallback : (string) $value;
    }

    public function integer(string $key, int $fallback, int $minimum, int $maximum): int
    {
        $value = filter_var($this->string($key, (string) $fallback), FILTER_VALIDATE_INT);
        if ($value === false || $value < $minimum || $value > $maximum) {
            throw new RuntimeException("Thiết lập {$key} nằm ngoài phạm vi hợp lệ.");
        }
        return $value;
    }

    public function decimal(string $key, float $fallback, float $minimum, float $maximum): float
    {
        $value = filter_var($this->string($key, (string) $fallback), FILTER_VALIDATE_FLOAT);
        if ($value === false || $value < $minimum || $value > $maximum) {
            throw new RuntimeException("Thiết lập {$key} nằm ngoài phạm vi hợp lệ.");
        }
        return (float) $value;
    }

    public function update(array $values, int $userId): void
    {
        $allowed = [
            'library_name' => ['string', 2, 120],
            'max_books_per_reader' => ['int', 1, 50],
            'default_loan_days' => ['int', 1, 90],
            'max_renewals' => ['int', 0, 10],
            'renewal_days' => ['int', 1, 90],
            'fine_per_overdue_day' => ['decimal', 0, 10000000],
            'reservation_expiry_days' => ['int', 1, 30],
            'damaged_copy_fine' => ['decimal', 0, 99999999],
            'lost_copy_fine' => ['decimal', 0, 99999999],
        ];
        $upsert = $this->db->prepare(
            'INSERT INTO system_settings (setting_key, setting_value, updated_by)
             VALUES (:key, :value, :user_id)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
        );
        foreach ($allowed as $key => [$type, $minimum, $maximum]) {
            if (!array_key_exists($key, $values) || !is_string($values[$key])) {
                throw new InvalidArgumentException("Thiếu thiết lập {$key}.");
            }
            $raw = trim($values[$key]);
            if ($type === 'string') {
                if (mb_strlen($raw) < $minimum || mb_strlen($raw) > $maximum) {
                    throw new InvalidArgumentException('Tên thư viện cần từ 2 đến 120 ký tự.');
                }
                $value = $raw;
            } elseif ($type === 'int') {
                $parsed = filter_var($raw, FILTER_VALIDATE_INT);
                if ($parsed === false || $parsed < $minimum || $parsed > $maximum) {
                    throw new InvalidArgumentException("Thiết lập {$key} không hợp lệ.");
                }
                $value = (string) $parsed;
            } else {
                if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $raw)) {
                    throw new InvalidArgumentException('Mức phạt cần là số tiền không âm, tối đa 2 chữ số thập phân.');
                }
                $amount = (float) $raw;
                if ($amount < $minimum || $amount > $maximum) {
                    throw new InvalidArgumentException('Mức phạt nằm ngoài phạm vi cho phép.');
                }
                $value = number_format($amount, 2, '.', '');
            }
            $upsert->execute(['key' => $key, 'value' => $value, 'user_id' => $userId]);
        }
        $revision = $this->db->prepare(
            'INSERT INTO library_policy_versions
             (library_name, max_books_per_reader, default_loan_days, max_renewals, renewal_days,
              fine_per_overdue_day, reservation_expiry_days, damaged_copy_fine, lost_copy_fine, updated_by)
             SELECT
                MAX(CASE WHEN setting_key = "library_name" THEN setting_value END),
                CAST(MAX(CASE WHEN setting_key = "max_books_per_reader" THEN setting_value END) AS UNSIGNED),
                CAST(MAX(CASE WHEN setting_key = "default_loan_days" THEN setting_value END) AS UNSIGNED),
                CAST(MAX(CASE WHEN setting_key = "max_renewals" THEN setting_value END) AS UNSIGNED),
                CAST(MAX(CASE WHEN setting_key = "renewal_days" THEN setting_value END) AS UNSIGNED),
                CAST(MAX(CASE WHEN setting_key = "fine_per_overdue_day" THEN setting_value END) AS DECIMAL(10,2)),
                CAST(MAX(CASE WHEN setting_key = "reservation_expiry_days" THEN setting_value END) AS UNSIGNED),
                CAST(MAX(CASE WHEN setting_key = "damaged_copy_fine" THEN setting_value END) AS DECIMAL(10,2)),
                CAST(MAX(CASE WHEN setting_key = "lost_copy_fine" THEN setting_value END) AS DECIMAL(10,2)),
                :user_id
             FROM system_settings'
        );
        $revision->execute(['user_id' => $userId]);
    }

    public function currentPolicyVersion(): int
    {
        return (int) $this->db->query('SELECT COALESCE(MAX(id), 1) FROM library_policy_versions')->fetchColumn();
    }
}
