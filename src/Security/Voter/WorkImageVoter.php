<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\WorkImage;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class WorkImageVoter extends Voter
{
    public const string EDIT_WORK_IMAGE = 'EDIT_WORK_IMAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT_WORK_IMAGE])
            && $subject instanceof WorkImage;
    }

    /**
     * @param string $attribute
     * @param WorkImage $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var ?User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        $work = $subject->getWork();

        return match ($attribute) {
            self::EDIT_WORK_IMAGE => $work->getUser()->getId() === $user->getId(),
            default => false,
        };
    }
}
