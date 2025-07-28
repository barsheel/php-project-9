<?php

namespace Hexlet\Code;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class UrlRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }
    public function save(string $name): int
    {
        $sql = "INSERT INTO urls (
                        name,
                        created_at
                    )
                    VALUES (
                        :name,
                        :created_at
                    ) RETURNING id;";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue('name', $name);
        $stmt->bindValue('created_at', Carbon::now()->toDateTimeString());
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM urls
                    WHERE
                        id = :id;";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue('id', $id);

        return $stmt->execute();
    }

    public function findById(int $id): ?Url
    {
        $sql = "SELECT                     
                    name,
                    created_at
                FROM urls
                WHERE :id = id;";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();

        if (!($row = $stmt->fetch())) {
            return null;
        }

        $name = $row['name'];
        $createdAt = $row['created_at'];

        return new Url($id, $name, $createdAt);
    }

    public function findByName($name): ?Url
    {
        $sql = "SELECT                     
                    id,
                    name,
                    created_at
                FROM urls
                WHERE :name = name;";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue('name', $name);
        $stmt->execute();

        if (!($row = $stmt->fetch())) {
            return null;
        }

        $id = $row['id'];
        $createdAt = $row['created_at'];

        return new Url($id, $name, $createdAt);
    }

    public function readAll(): Collection
    {
        $sql = "SELECT
                    id,
                    name,
                    created_at 
                FROM urls;";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $results = [];

        while ($row = $stmt->fetch()) {
            $id = $row['id'];
            $name = $row['name'];
            $createdAt = $row['created_at'];
            $results[] = new Url($id, $name, $createdAt);
        }

        return collect($results);
    }
}
