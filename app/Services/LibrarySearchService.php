<?php
declare(strict_types=1);
namespace Library\Services;
use PDO;
use Library\Repositories\BookRepository;
final class LibrarySearchService
{
 public function __construct(private readonly PDO $db) {}
 public function search(string $query,int $limit,bool $publicOnly=true):array{return (new BookRepository($this->db))->chatSearch($query,$limit,$publicOnly);}
 public function find(int $id,bool $publicOnly=true):?array{return (new BookRepository($this->db))->find($id,$publicOnly);}
}
