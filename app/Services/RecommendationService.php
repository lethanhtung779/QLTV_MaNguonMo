<?php
declare(strict_types=1);

namespace Library\Services;

use PDO;

final class RecommendationService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Get smart rule-based recommendations for a user or book context.
     * Returns an array of books with 'recommendation_reason'.
     */
    public function getRecommendations(?array $user, int $currentBookId = 0, int $limit = 6): array
    {
        $readerId = (int) ($user['reader_id'] ?? 0);
        $recommended = [];
        $seenBookIds = $currentBookId > 0 ? [$currentBookId => true] : [];

        // 1. Context: On book detail page
        if ($currentBookId > 0) {
            $stmt = $this->db->prepare('SELECT author, category_id, category_name FROM (
                SELECT b.id, b.author, b.category_id, c.name as category_name 
                FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = ?
            ) t');
            $stmt->execute([$currentBookId]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($current) {
                // Same author
                $authorStmt = $this->db->prepare('
                    SELECT b.*, c.name as category_name,
                           (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") as available_copies
                    FROM books b
                    LEFT JOIN categories c ON b.category_id = c.id
                    WHERE b.author = ? AND b.id != ? AND b.is_active = 1 AND b.is_public = 1
                    ORDER BY b.id DESC LIMIT ?
                ');
                $authorStmt->bindValue(1, $current['author']);
                $authorStmt->bindValue(2, $currentBookId, PDO::PARAM_INT);
                $authorStmt->bindValue(3, $limit, PDO::PARAM_INT);
                $authorStmt->execute();
                while ($b = $authorStmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!isset($seenBookIds[$b['id']])) {
                        $b['recommendation_reason'] = "Cùng tác giả {$current['author']}";
                        $recommended[] = $b;
                        $seenBookIds[$b['id']] = true;
                        if (count($recommended) >= $limit) return $recommended;
                    }
                }

                // Same category
                if (count($recommended) < $limit && (int)$current['category_id'] > 0) {
                    $catStmt = $this->db->prepare('
                        SELECT b.*, c.name as category_name,
                               (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") as available_copies
                        FROM books b
                        LEFT JOIN categories c ON b.category_id = c.id
                        WHERE b.category_id = ? AND b.id != ? AND b.is_active = 1 AND b.is_public = 1
                        ORDER BY (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") DESC, b.id DESC
                        LIMIT ?
                    ');
                    $catStmt->bindValue(1, (int)$current['category_id'], PDO::PARAM_INT);
                    $catStmt->bindValue(2, $currentBookId, PDO::PARAM_INT);
                    $catStmt->bindValue(3, $limit - count($recommended), PDO::PARAM_INT);
                    $catStmt->execute();
                    while ($b = $catStmt->fetch(PDO::FETCH_ASSOC)) {
                        if (!isset($seenBookIds[$b['id']])) {
                            $b['recommendation_reason'] = "Cùng thể loại {$current['category_name']}";
                            $recommended[] = $b;
                            $seenBookIds[$b['id']] = true;
                            if (count($recommended) >= $limit) return $recommended;
                        }
                    }
                }
            }
        }

        // 2. Personalization for logged-in readers
        if ($readerId > 0 && count($recommended) < $limit) {
            // Find user's top categories from loans and favorites
            $topCatsStmt = $this->db->prepare('
                SELECT b.category_id, c.name as category_name, COUNT(*) as weight
                FROM (
                    SELECT cp.book_id FROM loans l JOIN loan_items li ON l.id = li.loan_id JOIN book_copies cp ON cp.id = li.book_copy_id WHERE l.reader_id = ?
                    UNION ALL
                    SELECT book_id FROM book_favorites WHERE reader_id = ?
                ) activity
                JOIN books b ON activity.book_id = b.id
                JOIN categories c ON b.category_id = c.id
                GROUP BY b.category_id, c.name
                ORDER BY weight DESC
                LIMIT 2
            ');
            $topCatsStmt->execute([$readerId, $readerId]);
            $topCats = $topCatsStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($topCats as $tc) {
                if (count($recommended) >= $limit) break;
                $remStmt = $this->db->prepare('
                    SELECT b.*, c.name as category_name,
                           (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") as available_copies
                    FROM books b
                    LEFT JOIN categories c ON b.category_id = c.id
                    WHERE b.category_id = ? AND b.is_active = 1 AND b.is_public = 1
                    ORDER BY (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") DESC, b.id DESC
                    LIMIT ?
                ');
                $remStmt->bindValue(1, (int)$tc['category_id'], PDO::PARAM_INT);
                $remStmt->bindValue(2, $limit - count($recommended), PDO::PARAM_INT);
                $remStmt->execute();
                while ($b = $remStmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!isset($seenBookIds[$b['id']])) {
                        $b['recommendation_reason'] = "Dựa trên sở thích đọc thể loại {$tc['category_name']}";
                        $recommended[] = $b;
                        $seenBookIds[$b['id']] = true;
                        if (count($recommended) >= $limit) return $recommended;
                    }
                }
            }
        }

        // 3. Fallback: Highly rated & Most popular books
        if (count($recommended) < $limit) {
            $fallbackStmt = $this->db->prepare('
                SELECT b.*, c.name as category_name,
                       COALESCE((SELECT AVG(rating) FROM book_reviews r WHERE r.book_id = b.id AND r.is_approved = 1), 5.0) as avg_rating,
                       (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") as available_copies
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE b.is_active = 1 AND b.is_public = 1
                ORDER BY avg_rating DESC, (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") DESC, b.id ASC
                LIMIT ?
            ');
            $fallbackStmt->bindValue(1, $limit * 2, PDO::PARAM_INT);
            $fallbackStmt->execute();
            while ($b = $fallbackStmt->fetch(PDO::FETCH_ASSOC)) {
                if (!isset($seenBookIds[$b['id']])) {
                    $b['recommendation_reason'] = 'Tác phẩm được độc giả đánh giá cao nhất';
                    $recommended[] = $b;
                    $seenBookIds[$b['id']] = true;
                    if (count($recommended) >= $limit) break;
                }
            }
        }

        return $recommended;
    }
}
