<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class NotificationRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(int $userId, string $title, string $message, string $type, ?string $entityType, ?int $entityId, string $deduplicationKey): void
    {
        $statement = $this->db->prepare(
            'INSERT IGNORE INTO notifications
             (user_id, title, message, type, entity_type, entity_id, deduplication_key)
             VALUES (:user_id, :title, :message, :type, :entity_type, :entity_id, :deduplication_key)'
        );
        $statement->execute([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'deduplication_key' => $deduplicationKey,
        ]);
    }

    public function recentForUser(int $userId, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $statement = $this->db->prepare(
            'SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT ' . $limit
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL');
        $statement->execute(['user_id' => $userId]);
        return (int) $statement->fetchColumn();
    }

    public function markRead(int $id, int $userId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId]);
        if ($statement->rowCount() > 0) {
            return true;
        }
        $exists = $this->db->prepare('SELECT EXISTS(SELECT 1 FROM notifications WHERE id = :id AND user_id = :user_id)');
        $exists->execute(['id' => $id, 'user_id' => $userId]);
        return (bool) $exists->fetchColumn();
    }

    public function markAllRead(int $userId): void
    {
        $statement = $this->db->prepare(
            'UPDATE notifications SET read_at = NOW() WHERE user_id = :user_id AND read_at IS NULL'
        );
        $statement->execute(['user_id' => $userId]);
    }
}
