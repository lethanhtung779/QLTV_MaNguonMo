<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class ActivityRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function log(?int $userId, string $action, ?string $subjectType, ?int $subjectId, string $details): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO activity_logs (user_id, action, subject_type, subject_id, details)
             VALUES (:user_id, :action, :subject_type, :subject_id, :details)'
        );
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'details' => mb_substr($details, 0, 500),
        ]);
    }

    public function recent(int $limit = 8): array
    {
        $limit = max(1, min(30, $limit));
        return $this->db->query(
            'SELECT a.*, u.name AS user_name FROM activity_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC, a.id DESC LIMIT ' . $limit
        )->fetchAll();
    }
}
