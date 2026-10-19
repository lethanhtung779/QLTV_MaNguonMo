<?php
declare(strict_types=1);
namespace Library\Services;

use PDO;
use PDOException;
use RuntimeException;

/** Claim and result are committed atomically with the business transaction. */
final class MutationRequest
{
    public function __construct(
        private readonly PDO $db,
        private readonly int $actor,
        private readonly string $operation,
        private readonly ?string $key
    ) {}

    public function claim(array $payload): ?array
    {
        if (!$this->db->inTransaction()) {
            throw new RuntimeException('Mutation claim requires an active transaction.');
        }
        if ($this->key === null) return null; // Compatible internal/CLI callers.
        if (!preg_match('/^[a-f0-9]{32}$/D', $this->key)) {
            throw new RuntimeException('Mã yêu cầu giao dịch không hợp lệ.');
        }
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        try {
            $q = $this->db->prepare('INSERT INTO mutation_requests
                (actor_id,operation,request_key,payload_hash) VALUES (?,?,?,?)');
            $q->execute([$this->actor,$this->operation,$this->key,$hash]);
            return null;
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) !== 1062) throw $e;
        }
        // A concurrent insert waits for the first transaction to commit/rollback.
        $q = $this->db->prepare('SELECT payload_hash,result_json FROM mutation_requests
            WHERE actor_id=? AND operation=? AND request_key=? FOR UPDATE');
        $q->execute([$this->actor,$this->operation,$this->key]);
        $row = $q->fetch();
        if (!$row || !hash_equals($row['payload_hash'],$hash)) {
            throw new RuntimeException('Mã giao dịch đã được dùng cho nội dung khác.');
        }
        if ($row['result_json'] === null) {
            throw new RuntimeException('Yêu cầu giao dịch chưa có kết quả hoàn chỉnh.');
        }
        return json_decode($row['result_json'],true,512,JSON_THROW_ON_ERROR);
    }

    public function complete(array $result): void
    {
        if ($this->key === null) return;
        if (!$this->db->inTransaction()) throw new RuntimeException('Mutation result requires a transaction.');
        $q=$this->db->prepare('UPDATE mutation_requests SET result_json=?
            WHERE actor_id=? AND operation=? AND request_key=? AND result_json IS NULL');
        $q->execute([json_encode($result,JSON_THROW_ON_ERROR),$this->actor,$this->operation,$this->key]);
        if ($q->rowCount() !== 1) throw new RuntimeException('Không thể lưu kết quả giao dịch.');
    }
}
