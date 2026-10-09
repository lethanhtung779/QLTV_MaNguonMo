<?php
declare(strict_types=1);

namespace Library\Services;

use Library\Repositories\ActivityRepository;
use Library\Repositories\FineRepository;
use Library\Repositories\NotificationRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\SystemSettingRepository;
use PDO;
use RuntimeException;

final class FineService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function synchronizeReader(int $readerId, ?int $assessedBy = null): void
    {
        $repository = new FineRepository($this->db);
        foreach ($repository->overdueItems($readerId) as $item) {
            $days = (int) $item['overdue_days'];
            $amountCents = self::toCents((string) $item['daily_fine']) * $days;
            if ($days > 0 && $amountCents > 0) {
                $repository->upsertCharge(
                    (int) $item['id'],
                    (int) $item['reader_id'],
                    'overdue',
                    self::fromCents($amountCents),
                    "Phí quá hạn {$days} ngày: {$item['title']} ({$item['copy_code']})",
                    'overdue:' . (int) $item['id'],
                    $assessedBy
                );
            }
        }
    }

    public function assessConditionCharge(array $item, string $condition, ?int $assessedBy): void
    {
        $settingKey = match ($condition) {
            'damaged' => 'damaged_copy_fine',
            'lost' => 'lost_copy_fine',
            default => null,
        };
        if ($settingKey === null) {
            return;
        }
        $settings = new SystemSettingRepository($this->db);
        $amount = $settings->string($settingKey, $condition === 'lost' ? '200000' : '50000');
        $amountCents = self::toCents($amount);
        if ($amountCents < 1) {
            return;
        }
        (new FineRepository($this->db))->upsertCharge(
            (int) $item['id'],
            (int) $item['reader_id'],
            $condition,
            self::fromCents($amountCents),
            ($condition === 'lost' ? 'Phí bản sao bị mất' : 'Phí xử lý bản sao bị hỏng')
                . ": {$item['title']} ({$item['copy_code']})",
            $condition . ':' . (int) $item['id'],
            $assessedBy
        );
    }

    public function synchronizeAll(): void
    {
        $repository = new FineRepository($this->db);
        foreach ($repository->overdueReaderIds() as $readerId) {
            $this->synchronizeReader($readerId);
        }
    }

    public function balance(int $readerId): float
    {
        $this->synchronizeReader($readerId);
        $totalCents = 0;
        foreach ((new FineRepository($this->db))->chargesForReader($readerId) as $charge) {
            $totalCents += max(
                0,
                self::toCents((string) $charge['amount'])
                    - self::toCents((string) $charge['paid_amount'])
                    - self::toCents((string) $charge['waived_amount'])
            );
        }
        return $totalCents / 100;
    }

    public function recordPayment(
        int $readerId,
        string $amount,
        string $method,
        ?string $reference,
        ?string $note,
        int $userId
    ): void {
        $amountCents = self::toCents($amount);
        if ($readerId < 1 || $amountCents < 1 || !in_array($method, ['cash', 'bank_transfer', 'card', 'other'], true)) {
            throw new RuntimeException('Thông tin thanh toán không hợp lệ.');
        }

        $this->db->beginTransaction();
        try {
            $reader = (new ReaderRepository($this->db))->findForUpdate($readerId);
            if (!$reader) {
                throw new RuntimeException('Không tìm thấy độc giả đang hoạt động.');
            }
            $this->synchronizeReader($readerId, $userId);
            $repository = new FineRepository($this->db);
            $charges = $repository->outstandingChargesForUpdate($readerId);
            $balanceCents = array_sum(array_map(
                static fn(array $charge): int => self::toCents((string) $charge['balance']),
                $charges
            ));
            if ($amountCents > $balanceCents) {
                throw new RuntimeException('Số tiền thanh toán vượt quá dư nợ phí hiện tại.');
            }

            $paymentId = $repository->insertPayment(
                $readerId,
                self::fromCents($amountCents),
                $method,
                $reference ?: null,
                $note ?: null,
                $userId
            );
            $remaining = $amountCents;
            foreach ($charges as $charge) {
                if ($remaining < 1) {
                    break;
                }
                $chargeBalance = self::toCents((string) $charge['balance']);
                $allocation = min($remaining, $chargeBalance);
                if ($allocation > 0) {
                    $repository->allocate($paymentId, (int) $charge['id'], self::fromCents($allocation));
                    $remaining -= $allocation;
                }
            }
            if ($remaining !== 0) {
                throw new RuntimeException('Không thể phân bổ đầy đủ khoản thanh toán.');
            }

            (new ActivityRepository($this->db))->log(
                $userId,
                'fine_payment_recorded',
                'fine_payment',
                $paymentId,
                "Ghi nhận thanh toán phí cho thẻ {$reader['card_number']}"
            );
            $userIdForReader = (int) ($reader['user_id'] ?? 0);
            if ($userIdForReader > 0) {
                (new NotificationRepository($this->db))->create(
                    $userIdForReader,
                    'Đã ghi nhận thanh toán',
                    'Thư viện đã ghi nhận khoản thanh toán phí của bạn.',
                    'fine_payment',
                    'fine_payment',
                    $paymentId,
                    'fine-payment:' . $paymentId
                );
            }
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function waive(int $chargeId, string $amount, string $reason, int $userId): void
    {
        $amountCents = self::toCents($amount);
        $reason = trim($reason);
        $readerId = (new FineRepository($this->db))->readerIdForCharge($chargeId);
        if (!$readerId || $amountCents < 1 || mb_strlen($reason) < 5 || mb_strlen($reason) > 300) {
            throw new RuntimeException('Khoản miễn/giảm hoặc lý do không hợp lệ.');
        }

        $this->db->beginTransaction();
        try {
            if (!(new ReaderRepository($this->db))->findForUpdate($readerId)) {
                throw new RuntimeException('Không tìm thấy độc giả đang hoạt động.');
            }
            $repository = new FineRepository($this->db);
            $charge = $repository->charge($chargeId);
            if (!$charge || (int) $charge['reader_id'] !== $readerId) {
                throw new RuntimeException('Không tìm thấy khoản phí cần xử lý.');
            }
            $openCharge = null;
            foreach ($repository->outstandingChargesForUpdate($readerId) as $candidate) {
                if ((int) $candidate['id'] === $chargeId) {
                    $openCharge = $candidate;
                    break;
                }
            }
            if (!$openCharge || $amountCents > self::toCents((string) $openCharge['balance'])) {
                throw new RuntimeException('Số tiền miễn/giảm vượt quá số dư của khoản phí.');
            }
            $repository->waive($chargeId, self::fromCents($amountCents), $reason, $userId);
            $reader = (new ReaderRepository($this->db))->find($readerId);
            if ($reader && (int) ($reader['user_id'] ?? 0) > 0) {
                (new NotificationRepository($this->db))->create(
                    (int) $reader['user_id'],
                    'Khoản phí đã được điều chỉnh',
                    'Thư viện đã cập nhật khoản miễn/giảm trên tài khoản của bạn.',
                    'fine_adjustment',
                    'fine_charge',
                    $chargeId,
                    'fine-waiver:' . $chargeId . ':' . bin2hex(random_bytes(8))
                );
            }
            (new ActivityRepository($this->db))->log(
                $userId,
                'fine_waived',
                'fine_charge',
                $chargeId,
                'Miễn/giảm phí: ' . $reason
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public static function toCents(string $amount): int
    {
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', trim($amount))) {
            throw new RuntimeException('Số tiền không hợp lệ.');
        }
        [$whole, $fraction] = array_pad(explode('.', trim($amount), 2), 2, '0');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private static function fromCents(int $amountCents): string
    {
        return number_format($amountCents / 100, 2, '.', '');
    }
}
