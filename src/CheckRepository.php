<?php

namespace Hexlet\Code;

use Illuminate\Support\Collection;

class CheckRepository
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
                checks.id,
                response_code,
                h1,
                title,
                description,
                checks.created_at
            FROM urls
            INNER JOIN checks
            ON urls.id = checks.url_id
            WHERE :id = urls.id
            ORDER BY checks.created_at DESC;";

        $stmt = $this->conn->prepare($sqlChecks);
        $stmt->bindValue('id', $urlId);
        $stmt->execute();

        $checks = [];
        while ($row = $stmt->fetch()) {
            $checks[] = new Check(
                $row['id'],
                $row['response_code'],
                $row['h1'],
                $row['title'],
                $row['description'],
                $row['created_at']
            );
        }
        return collect($checks);
    }
}
