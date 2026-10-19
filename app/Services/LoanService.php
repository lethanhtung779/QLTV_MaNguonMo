<?php
declare(strict_types=1);

namespace Library\Services;

use Library\Repositories\ActivityRepository;
use Library\Repositories\BookRepository;
use Library\Repositories\LoanRepository;
use Library\Repositories\NotificationRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\SystemSettingRepository;
use PDO;
use RuntimeException;

final class LoanService
{
    public function __construct(private readonly PDO $db, private readonly float $dailyFine)
    {
    }

    public function checkout(int $readerId, array $copyIds, int $issuedBy, string $borrowedAt, string $dueAt, string $note = '', ?string $requestKey = null): string
    {
        if (!$this->validDate($borrowedAt) || !$this->validDate($dueAt) ||
            $borrowedAt > date('Y-m-d') || $dueAt < $borrowedAt) {
            throw new RuntimeException('Ngày mượn hoặc hạn trả không hợp lệ.');
        }
        $copyIds = array_values(array_unique(array_filter(
            array_map('intval', $copyIds),
            static fn(int $copyId): bool => $copyId > 0
        )));
        sort($copyIds, SORT_NUMERIC);

        $this->db->beginTransaction();
        try {
            $request = new MutationRequest($this->db, $issuedBy, 'checkout', $requestKey);
            $previous = $request->claim([$readerId,$copyIds,$borrowedAt,$dueAt,$note]);
            if ($previous !== null) {
                $this->db->commit();
                return $previous['loan_code'];
            }
            $policy = (new SystemSettingRepository($this->db))->currentPolicy();
            $maxCopies = (int) $policy['max_books_per_reader'];
            $loanDays = (int) $policy['default_loan_days'];
            if (!$copyIds || count($copyIds) > $maxCopies) {
                throw new RuntimeException("Mỗi phiếu cần từ 1 đến {$maxCopies} bản sao sách.");
            }
            if ((int) ((strtotime($dueAt) - strtotime($borrowedAt)) / 86400) > $loanDays) {
                throw new RuntimeException("Thời hạn mượn không được vượt quá {$loanDays} ngày.");
            }
            $dailyFine = (float) $policy['fine_per_overdue_day'];
            $reader = (new ReaderRepository($this->db))->findForUpdate($readerId);
            if (!$reader || !(bool) $reader['is_active']) {
                throw new RuntimeException('Thẻ độc giả chưa được kích hoạt hoặc đã bị khóa.');
            }
            if ($reader['expires_on'] && $reader['expires_on'] < date('Y-m-d')) {
                throw new RuntimeException('Thẻ độc giả đã hết hạn.');
            }

            $loans = new LoanRepository($this->db);
            if ($loans->activeCountForReader($readerId) + count($copyIds) > $maxCopies) {
                throw new RuntimeException("Độc giả đã đạt giới hạn {$maxCopies} bản sách đang mượn.");
            }

            // Lock parent books in ascending order before joining/locking copies.
            $q = $this->db->prepare('SELECT DISTINCT book_id FROM book_copies WHERE id IN ('
                . implode(',', array_fill(0,count($copyIds),'?')) . ') ORDER BY book_id');
            $q->execute($copyIds);
            foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $bookId) {
                (new BookRepository($this->db))->findForUpdate((int) $bookId);
            }
            $copies = [];
            $booksInRequest = [];
            foreach ($copyIds as $copyId) {
                $copy = $loans->findCopyForUpdate($copyId);
                if (!$copy || !(bool) $copy['book_active'] ||
                    in_array($copy['item_condition'], ['damaged', 'lost'], true)) {
                    throw new RuntimeException('Một hoặc nhiều bản sao đã được mượn, hỏng, mất hoặc ngừng phục vụ.');
                }
                $bookId = (int) $copy['book_id'];
                if (!isset($booksInRequest[$bookId]) && $loans->hasActiveBookForReader($readerId, $bookId)) {
                    throw new RuntimeException('Độc giả đang có bản sao của một trong các đầu sách được chọn.');
                }

                $readyReservation = $loans->readyReservationForReader($readerId, $bookId);
                $reservedForReader = $readyReservation &&
                    (int) ($readyReservation['book_copy_id'] ?? 0) === (int) $copy['id'];
                if ($copy['status'] === 'reserved' && !$reservedForReader) {
                    throw new RuntimeException('Bản sao này đang được giữ cho độc giả đã đặt trước.');
                }
                if ($copy['status'] === 'pending_audit') {
                    throw new RuntimeException("Bản sao {$copy['copy_code']} đang ở trạng thái chờ kiểm kê (pending_audit), chưa thể đưa vào lưu hành.");
                }
                if (!in_array($copy['status'], ['available', 'reserved'], true)) {
                    throw new RuntimeException("Bản sao {$copy['copy_code']} không ở trạng thái sẵn sàng cho mượn (trạng thái: {$copy['status']}).");
                }
                if ($readyReservation) {
                    if ($readyReservation['book_copy_id'] !== null && !$reservedForReader) {
                        throw new RuntimeException('Yêu cầu đặt trước đang giữ một bản sao khác.');
                    }
                    $loans->fulfillReservation((int) $readyReservation['id']);
                } elseif ($loans->hasWaitingReservationsForBook($bookId)) {
                    throw new RuntimeException('Đầu sách đang có người đặt trước. Hãy xử lý theo thứ tự hàng chờ.');
                }
                $booksInRequest[$bookId] = true;
                $copies[] = $copy;
            }

            $code = 'PM' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
            $loanId = $loans->create([
                'loan_code' => $code,
                'reader_id' => $readerId,
                'book_id' => (int) $copies[0]['book_id'],
                'issued_by' => $issuedBy,
                'borrowed_at' => $borrowedAt,
                'due_at' => $dueAt,
                'daily_fine' => $dailyFine,
                'note' => $note ?: null,
            ]);
            foreach ($copies as $copy) {
                $loans->markCopyOnLoan((int) $copy['id'], $copy['status'] === 'reserved');
                $loans->createItem($loanId, (int) $copy['id'], $borrowedAt, $dueAt, $dailyFine);
            }
            (new ActivityRepository($this->db))->log($issuedBy, 'loan_created', 'loan', $loanId, "Lập phiếu {$code}");
            $this->notifyReader($readerId, 'Phiếu mượn mới', "Phiếu {$code} đã được lập. Hạn trả: " . date('d/m/Y', strtotime($dueAt)) . '.', 'loan', $loanId, 'loan-created:' . $loanId);
            $request->complete(['loan_code' => $code]);
            $this->db->commit();
            return $code;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function returnBook(int $loanItemId,int $userId,string $condition='good'): void
    {
        $this->returnItems([$loanItemId],$userId,$condition);
    }
    public function returnAll(int $loanId,int $userId,string $condition='good'): void
    {
        $s=$this->db->prepare('SELECT id FROM loan_items WHERE loan_id=? AND returned_at IS NULL ORDER BY id');
        $s->execute([$loanId]);$ids=array_map('intval',$s->fetchAll(\PDO::FETCH_COLUMN));
        if (!$ids) throw new RuntimeException('Phiếu không còn bản sách chưa trả.');
        $this->returnItems($ids,$userId,$condition);
    }
    private function returnItems(array $ids,int $userId,string $condition): void
    {
        $this->db->beginTransaction();
        try {
            sort($ids,SORT_NUMERIC);
            $this->lockItemContext($ids);
            foreach ($ids as $id) $this->returnItem($id,$userId,$condition);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function lockItemContext(array $ids): array
    {
        $q=$this->db->prepare('SELECT li.id,l.reader_id,cp.book_id FROM loan_items li
            JOIN loans l ON l.id=li.loan_id JOIN book_copies cp ON cp.id=li.book_copy_id
            WHERE li.id IN ('.implode(',',array_fill(0,count($ids),'?')).')');
        $q->execute($ids);
        $rows=$q->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
        $readers=array_unique(array_column($rows,'reader_id'));
        $books=array_unique(array_column($rows,'book_id'));
        sort($readers,SORT_NUMERIC);sort($books,SORT_NUMERIC);
        $lock=$this->db->prepare('SELECT id FROM readers WHERE id=? FOR UPDATE');
        foreach ($readers as $id) $lock->execute([$id]);
        foreach ($books as $id) (new BookRepository($this->db))->findForUpdate((int)$id);
        return $rows;
    }

    private function returnItem(int $loanItemId, int $userId, string $condition = 'good'): void
    {
        if (!in_array($condition, ['good', 'worn', 'damaged', 'lost'], true)) {
            throw new RuntimeException('Tình trạng bản sao khi trả không hợp lệ.');
        }
        try {
            $loans = new LoanRepository($this->db);
            $item = $loans->findItemForUpdate($loanItemId);
            if (!$item || $item['returned_at'] !== null) {
                throw new RuntimeException('Bản sao không tồn tại hoặc đã được trả.');
            }
            $books = new BookRepository($this->db);
            $book = $books->findForUpdate((int) $item['book_id']);
            if (!$book) {
                throw new RuntimeException('Không tìm thấy đầu sách của bản sao.');
            }
            $loans->markItemReturned($loanItemId, (int) $item['book_copy_id'], $condition);
            $loans->updateHeaderReturnStatus((int) $item['loan_id']);
            $fines = new \Library\Services\FineService($this->db);
            $fines->synchronizeReader((int) $item['reader_id'], $userId);
            $fines->assessConditionCharge($item, $condition, $userId);
            if (in_array($condition, ['good', 'worn'], true)) {
                $this->allocateReservations((int) $item['book_id'], $userId, 1);
            }
            $this->notifyReader(
                (int) $item['reader_id'],
                'Đã ghi nhận trả sách',
                "Bản {$item['copy_code']} đã được ghi nhận trả. Phí trễ hạn (nếu có) sẽ được cập nhật trên tài khoản.",
                'return',
                (int) $item['loan_id'],
                'loan-returned:' . $loanItemId
            );

            (new ActivityRepository($this->db))->log(
                $userId,
                'loan_item_returned',
                'loan',
                (int) $item['loan_id'],
                "Nhận trả bản {$item['copy_code']} của phiếu {$item['loan_code']}"
            );
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function reserve(int $readerId, int $bookId, int $userId): void
    {
        $this->db->beginTransaction();
        try {
            $reader = (new ReaderRepository($this->db))->findForUpdate($readerId);
            if (!$reader || ($reader['expires_on'] && $reader['expires_on'] < date('Y-m-d'))) {
                throw new RuntimeException('Thẻ độc giả không hợp lệ hoặc đã hết hạn.');
            }
            $books = new BookRepository($this->db);
            $book = $books->findForUpdate($bookId);
            if (!$book) {
                throw new RuntimeException('Không tìm thấy sách.');
            }
            $loans = new LoanRepository($this->db);
            if ($loans->hasActiveBookForReader($readerId, $bookId) ||
                $loans->readerHasReservation($readerId, $bookId)) {
                throw new RuntimeException('Bạn đã mượn hoặc đặt trước cuốn sách này.');
            }
            $available = $loans->availableCopyCountForBook($bookId);
            if ($available > 0) {
                throw new RuntimeException('Sách đang có sẵn. Bạn có thể đến thư viện để mượn.');
            }

            $statement = $this->db->prepare('INSERT INTO reservations (reader_id, book_id) VALUES (:reader_id, :book_id)');
            $statement->execute(['reader_id' => $readerId, 'book_id' => $bookId]);
            $reservationId = (int) $this->db->lastInsertId();
            (new ActivityRepository($this->db))->log($userId, 'book_reserved', 'book', $bookId, "Đặt trước sách: {$book['title']}");
            $this->notifyReader(
                $readerId,
                'Đã nhận yêu cầu đặt trước',
                "Yêu cầu đặt trước “{$book['title']}” đã được thêm vào hàng chờ.",
                'reservation',
                $reservationId,
                'reservation-created:' . $reservationId
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function updateReservation(int $reservationId, string $status, int $userId): void
    {
        if (!in_array($status, ['ready', 'cancelled'], true)) {
            throw new RuntimeException('Trạng thái đặt trước không hợp lệ.');
        }

        $this->db->beginTransaction();
        try {
            $reservations = new LoanRepository($this->db);
            $initial = $reservations->findReservation($reservationId);
            if (!$initial || !in_array($initial['status'], ['pending', 'ready'], true)) {
                throw new RuntimeException('Yêu cầu đặt trước không còn chờ xử lý.');
            }
            $book = (new BookRepository($this->db))->findForUpdate((int) $initial['book_id']);
            $reservation = $reservations->findReservationForUpdate($reservationId);
            if (!$book || !(bool) $book['is_active'] || !$reservation ||
                !in_array($reservation['status'], ['pending', 'ready'], true)) {
                throw new RuntimeException('Yêu cầu đặt trước không còn hợp lệ.');
            }
            if ($status === 'ready' && $reservation['status'] === 'pending') {
                $waiting = $reservations->pendingReservationsForBook((int) $book['id']);
                if (!$waiting || (int) $waiting[0]['id'] !== $reservationId) {
                    throw new RuntimeException('Hãy xử lý yêu cầu theo thứ tự thời gian đăng ký.');
                }
                $copy = $reservations->findAvailableCopyForUpdate((int) $book['id']);
                if (!$copy || !$reservations->holdCopy((int) $copy['id'])) {
                    throw new RuntimeException('Chưa có bản sách sẵn sàng để xác nhận yêu cầu.');
                }
                $copyId = (int) $copy['id'];
            } else {
                $copyId = isset($reservation['book_copy_id']) ? (int) $reservation['book_copy_id'] : null;
            }
            if (!$reservations->updateReservation(
                $reservationId,
                $status,
                $userId,
                $status === 'ready' ? $copyId : null,
                (new SystemSettingRepository($this->db))->integer('reservation_expiry_days', 3, 1, 30)
            )) {
                throw new RuntimeException('Yêu cầu đặt trước đã được xử lý ở nơi khác.');
            }
            if ($status === 'cancelled' && !empty($reservation['book_copy_id'])) {
                $this->releaseReservedCopy((int) $reservation['book_copy_id']);
                $this->allocateReservations((int) $book['id'], $userId, 1);
            } elseif ($status === 'ready' && $copyId !== null) {
                $this->notifyReservationReady($reservationId, (int) $reservation['reader_id'], (int) $book['id'], $copyId);
            }
            (new ActivityRepository($this->db))->log(
                $userId,
                'reservation_' . $status,
                'reservation',
                $reservationId,
                $status === 'ready' ? 'Đánh dấu yêu cầu đặt trước sẵn sàng' : 'Hủy yêu cầu đặt trước'
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function renew(int $loanItemId, int $userId, ?int $readerId = null, ?string $requestKey = null): string
    {
        $this->db->beginTransaction();
        try {
            $request = new MutationRequest($this->db,$userId,'renew',$requestKey);
            $previous = $request->claim([$loanItemId,$readerId]);
            if ($previous !== null) {
                $this->db->commit();
                return $previous['due_date'];
            }
            $context = $this->lockItemContext([$loanItemId]);
            $owner = $context[$loanItemId]['reader_id'] ?? 0;
            $reader = (new ReaderRepository($this->db))->findForUpdate((int) $owner);
            if (!$reader || ($reader['expires_on'] && $reader['expires_on'] < date('Y-m-d'))) {
                throw new RuntimeException('Thẻ độc giả không hoạt động hoặc đã hết hạn.');
            }
            $policy = (new SystemSettingRepository($this->db))->currentPolicy();
            $maximum = (int) $policy['max_renewals'];
            $days = (int) $policy['renewal_days'];
            $policyVersion = (int) $policy['id'];
            $loans = new LoanRepository($this->db);
            $item = $loans->findItemForUpdate($loanItemId);
            if (!$item || $item['returned_at'] !== null ||
                ($readerId !== null && (int) $item['reader_id'] !== $readerId)) {
                throw new RuntimeException('Chỉ có thể gia hạn bản sách đang được mượn.');
            }
            if ($item['due_date'] < date('Y-m-d')) {
                throw new RuntimeException('Không thể gia hạn bản sách đã quá hạn.');
            }
            if ($loans->renewalCount($loanItemId) >= $maximum) {
                throw new RuntimeException("Bản sách đã đạt giới hạn {$maximum} lần gia hạn.");
            }
            if ($loans->hasActiveReservationsForBook((int) $item['book_id'])) {
                throw new RuntimeException('Không thể gia hạn vì đầu sách đang có người đặt trước.');
            }
            $newDueDate = (new \DateTimeImmutable($item['due_date']))
                ->modify("+{$days} days")
                ->format('Y-m-d');
            $renewalNumber = $loans->renewalCount($loanItemId) + 1;
            $loans->recordRenewal(
                $loanItemId,
                $renewalNumber,
                $policyVersion,
                $days,
                $item['due_date'],
                $newDueDate,
                $userId
            );
            $loans->updateItemDueDate($loanItemId, (int) $item['loan_id'], $newDueDate);
            (new ActivityRepository($this->db))->log($userId, 'loan_item_renewed', 'loan', (int) $item['loan_id'], "Gia hạn bản {$item['copy_code']} đến {$newDueDate}");
            $this->notifyReader(
                (int) $item['reader_id'],
                'Đã gia hạn sách',
                "Bản {$item['copy_code']} đã được gia hạn đến " . date('d/m/Y', strtotime($newDueDate)) . '.',
                'renewal',
                $loanItemId,
                'loan-renewed:' . $loanItemId . ':' . $renewalNumber
            );
            $request->complete(['due_date' => $newDueDate]);
            $this->db->commit();
            return $newDueDate;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function cancelReaderReservation(int $reservationId, int $readerId): void
    {
        $this->db->beginTransaction();
        try {
            $loans = new LoanRepository($this->db);
            $initial = $loans->findReservation($reservationId);
            if (!$initial || (int) $initial['reader_id'] !== $readerId) {
                throw new RuntimeException('Không thể hủy yêu cầu đặt trước này.');
            }
            (new BookRepository($this->db))->findForUpdate((int) $initial['book_id']);
            $reservation = $loans->findReservationForUpdate($reservationId);
            if (!$reservation || (int) $reservation['reader_id'] !== $readerId ||
                !in_array($reservation['status'], ['pending', 'ready'], true)) {
                throw new RuntimeException('Không thể hủy yêu cầu đặt trước này.');
            }

            $book = (new BookRepository($this->db))->findForUpdate((int) $reservation['book_id']);
            if (!$book) {
                throw new RuntimeException('Không tìm thấy đầu sách của yêu cầu đặt trước.');
            }
            if (!$loans->updateReservation($reservationId, 'cancelled', null)) {
                throw new RuntimeException('Yêu cầu đặt trước đã được xử lý ở nơi khác.');
            }
            if (!empty($reservation['book_copy_id'])) {
                $this->releaseReservedCopy((int) $reservation['book_copy_id']);
                $this->allocateReservations((int) $book['id'], null, 1);
            }
            $this->notifyReader(
                $readerId,
                'Đã hủy yêu cầu đặt trước',
                "Yêu cầu đặt trước “{$book['title']}” đã được hủy.",
                'reservation',
                $reservationId,
                'reservation-cancelled:' . $reservationId
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function allocateAvailableReservations(int $bookId, int $userId): void
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            if (!(new BookRepository($this->db))->findForUpdate($bookId)) {
                throw new RuntimeException('Không tìm thấy đầu sách để cấp hàng chờ.');
            }
            $available = (new LoanRepository($this->db))->availableCopyCountForBook($bookId);
            $this->allocateReservations($bookId, $userId, $available);
            if ($ownsTransaction) {
                $this->db->commit();
            }
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function expireReservations(): int
    {
        $this->db->beginTransaction();
        try {
            $expired = $this->db->query(
                'SELECT id, reader_id, book_id, book_copy_id FROM reservations
                 WHERE status = "ready" AND expires_at < NOW() ORDER BY book_id, id'
            )->fetchAll();
            $statement = $this->db->prepare(
                'UPDATE reservations SET status = "cancelled" WHERE id = :id AND status = "ready"'
            );
            $processed = 0;
            foreach ($expired as $candidate) {
                (new BookRepository($this->db))->findForUpdate((int) $candidate['book_id']);
                $current=$this->db->prepare('SELECT id,reader_id,book_id,book_copy_id FROM reservations
                    WHERE id=? AND status="ready" AND expires_at < NOW() FOR UPDATE');
                $current->execute([$candidate['id']]);
                $reservation=$current->fetch();
                if (!$reservation) continue;
                $processed++;
                $statement->execute(['id' => $reservation['id']]);
                if (!empty($reservation['book_copy_id'])) {
                    $this->releaseReservedCopy((int) $reservation['book_copy_id']);
                    $this->allocateReservations((int) $reservation['book_id'], null, 1);
                }
                $this->notifyReader(
                    (int) $reservation['reader_id'],
                    'Đã hết hạn nhận sách',
                    'Thời hạn nhận sách đặt trước đã kết thúc. Bạn có thể đặt lại nếu vẫn cần.',
                    'reservation',
                    (int) $reservation['id'],
                    'reservation-expired:' . $reservation['id']
                );
            }
            $this->db->commit();
            return $processed;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    private function allocateReservations(int $bookId, ?int $userId, int $limit): int
    {
        $loans = new LoanRepository($this->db);
        $settings = new SystemSettingRepository($this->db);
        $expiryDays = $settings->integer('reservation_expiry_days', 3, 1, 30);
        $allocated = 0;
        foreach ($loans->pendingReservationsForBook($bookId) as $reservation) {
            if ($allocated >= $limit) {
                break;
            }
            $copy = $loans->findAvailableCopyForUpdate($bookId);
            if (!$copy || !$loans->holdCopy((int) $copy['id'])) {
                break;
            }
            if (!$loans->promoteReservation((int) $reservation['id'], $userId, (int) $copy['id'], $expiryDays)) {
                throw new RuntimeException('Không thể cấp giữ chỗ cho yêu cầu tiếp theo.');
            }
            $this->notifyReservationReady(
                (int) $reservation['id'],
                (int) ($reservation['reader_id'] ?? 0),
                $bookId,
                (int) $copy['id']
            );
            $allocated++;
        }
        return $allocated;
    }

    private function notifyReservationReady(int $reservationId, int $readerId, int $bookId, int $copyId): void
    {
        $statement = $this->db->prepare(
            'SELECT r.user_id, b.title, cp.copy_code FROM readers r
             JOIN books b ON b.id = :book_id
             JOIN book_copies cp ON cp.id = :copy_id
             WHERE r.id = :reader_id'
        );
        $statement->execute(['reader_id' => $readerId, 'book_id' => $bookId, 'copy_id' => $copyId]);
        $row = $statement->fetch();
        if ($row && $row['user_id'] !== null) {
            (new NotificationRepository($this->db))->create(
                (int) $row['user_id'],
                'Sách đặt trước đã sẵn sàng',
                "“{$row['title']}” (bản {$row['copy_code']}) đã sẵn sàng. Vui lòng nhận trong thời hạn quy định.",
                'reservation',
                'reservation',
                $reservationId,
                'reservation-ready:' . $reservationId
            );
        }
    }

    private function notifyReader(int $readerId, string $title, string $message, string $type, ?int $entityId, string $deduplicationKey): void
    {
        $statement = $this->db->prepare('SELECT user_id FROM readers WHERE id = :reader_id');
        $statement->execute(['reader_id' => $readerId]);
        $userId = $statement->fetchColumn();
        if ($userId !== false && $userId !== null) {
            (new NotificationRepository($this->db))->create(
                (int) $userId,
                $title,
                $message,
                $type,
                'loan',
                $entityId,
                $deduplicationKey
            );
        }
    }

    private function releaseReservedCopy(int $copyId): void
    {
        $statement = $this->db->prepare(
            'UPDATE book_copies SET status = "available" WHERE id = :id AND status = "reserved"'
        );
        $statement->execute(['id' => $copyId]);
    }

    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
