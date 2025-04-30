<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\WorkEventDay;
use App\Interface\UserOwnerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class CurrentUserVoter extends Voter
{
    public const string VIEW = 'VIEW';

    public const string EDIT = 'EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT]) && $subject instanceof UserOwnerInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var ?User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::VIEW, self::EDIT => $subject->getUser()?->getId() === $user->getId(),
            default => false,
        };
    }
}
