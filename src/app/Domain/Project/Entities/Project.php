<?php

namespace App\Domain\Project\Entities;

use App\Domain\Shared\Exceptions\ConflictException;

/**
 * プロジェクト。
 *
 * 不変（readonly）。値を変えるときは PHP 8.5 の clone with で新しいインスタンスを返す。
 */
final readonly class Project
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public int $boardVersion,
    ) {}

    public function rename(string $name, ?string $description): self
    {
        return clone ($this, [
            'name' => $name,
            'description' => $description,
        ]);
    }

    /**
     * ボード（タスクの並びや内容）が変わったときに版を1つ進める。
     */
    public function bumpBoardVersion(): self
    {
        return clone ($this, ['boardVersion' => $this->boardVersion + 1]);
    }

    /**
     * クライアントが見ていた版が最新でなければ、競合として扱う。
     *
     * @throws ConflictException
     */
    public function ensureBoardVersion(int $expectedVersion): void
    {
        if ($expectedVersion !== $this->boardVersion) {
            throw new ConflictException;
        }
    }
}
