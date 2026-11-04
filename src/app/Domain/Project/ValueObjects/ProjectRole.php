<?php

namespace App\Domain\Project\ValueObjects;

/**
 * プロジェクト内でのロール。
 *
 * オーナーは1プロジェクトに必ず1人。移譲は TransferProjectOwnershipUseCase で、
 * 元のオーナーをメンバーにする処理と同じトランザクションで行う。
 */
enum ProjectRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
