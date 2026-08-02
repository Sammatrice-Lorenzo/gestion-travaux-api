<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\WorkImage;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends Voter<string, WorkImage>
 */
final class WorkImageVoter extends Voter
{
    public const string EDIT_WORK_IMAGE = 'EDIT_WORK_IMAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::EDIT_WORK_IMAGE === $attribute
            && $subject instanceof WorkImage;
    }

    /**
     * @param WorkImage $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
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
