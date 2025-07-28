<?php

namespace Hexlet\Code;

use Illuminate\Support\Collection;
use Carbon\Carbon;

class UrlCheckRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function save(int $url_id, int $statusCode, string $h1, string $title, string $description): int
    {
        $sql = "INSERT INTO url_checks (
                    url_id,
                    status_code,
                    h1,
                    title,
                    description,
                    created_at
                )
                VALUES (
                    :url_id,
                    :status_code,
                    :h1,
                    :title,
                    :description,
                    :created_at
                ) RETURNING id;";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue('url_id', $url_id);
        $stmt->bindValue('status_code', $statusCode);
        $stmt->bindValue('h1', $h1);
        $stmt->bindValue('title', $title);
        $stmt->bindValue('description', $description);
        $stmt->bindValue('created_at', Carbon::now()->toDateTimeString());
        $stmt->execute();
        return $stmt->fetchColumn();
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
