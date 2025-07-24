<?php

namespace Hexlet\Code;

use Illuminate\Support\Collection;

class UrlCheckRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function findChecksByUrlId(int $urlId): Collection
    {
        $sqlChecks =
            "SELECT
                url_checks.id,
                status_code,
                h1,
                title,
                description,
                url_checks.created_at
            FROM urls
            INNER JOIN url_checks
            ON urls.id = url_checks.url_id
            WHERE :id = urls.id
            ORDER BY url_checks.created_at DESC;";

        $stmt = $this->conn->prepare($sqlChecks);
        $stmt->bindValue('id', $urlId);
        $stmt->execute();

        $checks = [];
        while ($row = $stmt->fetch()) {
            $checks[] = new UrlCheck(
                $row['id'],
                $row['status_code'],
                $row['h1'],
                $row['title'],
                $row['description'],
                $row['created_at']
            );
        }
        return collect($checks);
    }
}
