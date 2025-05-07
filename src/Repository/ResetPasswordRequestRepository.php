<?php

namespace App\Repository;
use Doctrine\Persistence\ManagerRegistry;  
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

class ResetPasswordRequestRepository implements ResetPasswordRequestRepositoryInterface
{
   
    public function __construct(ManagerRegistry $doctrine)
    {
        // You can just ignore $doctrine if you don't use it
    }
    public function createResetPasswordRequest(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequestInterface
    {
        throw new \LogicException('Not implemented because we do not store reset password requests.');
    }

    public function findResetPasswordRequest(string $selector): ?ResetPasswordRequestInterface
    {
        return null;
    }

    public function removeResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        // Do nothing
    }

    public function removeExpiredResetPasswordRequests(): int
    {
        return 0;
    }

    public function getUserIdentifier(object $user): string
    {
        return 'fake_user_identifier';
    }

    public function persistResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        // Do nothing
    }

    public function getMostRecentNonExpiredRequestDate(object $user): ?\DateTimeInterface
    {
        return null;
    }
}
