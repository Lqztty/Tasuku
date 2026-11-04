<?php

namespace App\Application\Project\Commands;

final readonly class CreateProjectCommand
{
    public function __construct(
        public int $userId,
        public string $name,
        public ?string $description,
    ) {}
}
