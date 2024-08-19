<?php

namespace App\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TaskVoter extends Voter
{
    public const TOGGLE = 'toggle';
    public const DELETE = 'delete';
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::DELETE, self::TOGGLE])) {
            return false;
        }

        // only vote on `Task` objects
        if (!$subject instanceof Task) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if ($this->security->isGranted('ROLE_SUPER_ADMIN') && 'super.admin@orange.fr' === $user->getEmail()) {
            return true;
        }

        // you know $subject is a Task object, thanks to `supports()`
        /** @var Task $task */
        $task = $subject;

        if ($this->security->isGranted('ROLE_ADMIN') && null === $task->getUser()) {
            return true;
        }

        return match ($attribute) {
            self::TOGGLE => $this->canToggle($task, $user),
            self::DELETE => $this->canDelete($task, $user),
            default => throw new \LogicException('Ce voteur ne devrait pas être atteint.')
        };
    }

    private function canToggle(Task $task, User $user): bool
    {
        return $user === $task->getUser();
    }

    private function canDelete(Task $task, User $user): bool
    {
        return $user === $task->getUser();
    }
}
