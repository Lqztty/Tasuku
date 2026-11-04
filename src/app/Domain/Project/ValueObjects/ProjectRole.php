<?php

namespace App\Domain\Project\ValueObjects;

/**
 * プロジェクト内でのロール。
 *
 * オーナーは1プロジェクトに必ず1人。移譲する機能はない。
 */
enum ProjectRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
