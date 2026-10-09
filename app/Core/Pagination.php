<?php
declare(strict_types=1);
namespace Library\Core;
final class Pagination
{
    public readonly int $page;
    public readonly int $pages;
    public readonly int $offset;
    public function __construct(public readonly int $total, public readonly int $perPage = 12)
    {
        $this->pages = max(1, (int) ceil($total / $perPage));
        $raw = $_GET['page'] ?? 1;
        $requested = is_scalar($raw) ? filter_var($raw, FILTER_VALIDATE_INT) : false;
        $this->page = max(1, min($this->pages, $requested ?: 1));
        $this->offset = ($this->page - 1) * $perPage;
    }
}
