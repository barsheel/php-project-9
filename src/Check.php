<?php

namespace Hexlet\Code;

class Check
{
    private int $id;
    private int $responseCode;
    private string $h1;
    private string $title;
    private string $description;
    private string $createdAt;

    public function __construct(
        int $id,
        int $responseCode,
        string $h1,
        string $title,
        string $description,
        string $createdAt
    ) {
        $this->id = $id;
        $this->responseCode = $responseCode;
        $this->h1 = $h1;
        $this->title = $title;
        $this->description = $description;
        $this->createdAt = $createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }


    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    public function getH1(): string
    {
        return $this->h1;
    }

    public function setH1(string $h1): void
    {
        $this->h1 = $h1;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
