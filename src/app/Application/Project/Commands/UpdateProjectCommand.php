<?php

namespace App\Application\Project\Commands;

final readonly class UpdateProjectCommand
{
    public function __construct(
        public int $userId,
        public int $projectId,
        public string $name,
        public ?string $description,
    ) {}
}
