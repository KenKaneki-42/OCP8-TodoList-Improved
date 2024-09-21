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
    public const CREATE = 'create';
    public const EDIT = 'edit';
    public const ATTRIBUTES = [self::DELETE, self::TOGGLE, self::EDIT, self::CREATE];

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $task = $subject;

        if ($this->security->isGranted('ROLE_ADMIN') && $task->getUser() === null) {
            return true;
        }

        return match ($attribute) {
            self::TOGGLE => $this->canToggleTask($task, $user),
            self::DELETE => $this->canDeleteTask($task, $user),
            self::EDIT => $this->canEditTask($task, $user),
            self::CREATE => true,
            default => throw new \LogicException('Ce voteur ne devrait pas être atteint.')
        };
    }

    private function canToggleTask(Task $task, User $user): bool
    {
        // Admins can toggle any task, but only owners can toggle their own tasks
        return $user === $task->getUser() || $this->isAdmin($user);
    }

    private function canDeleteTask(Task $task, User $user): bool
    {
        // Only the owner or a super admin can delete the task, not regular admins
        return $user === $task->getUser() || $this->isSuperAdmin($user);
    }

    private function canEditTask(Task $task, User $user): bool
    {
        // Only the owner or a super admin can edit the task, not regular admins
        return $user === $task->getUser() || $this->isSuperAdmin($user);
    }

    private function isSuperAdmin(User $user): bool
    {
        return $this->security->isGranted('ROLE_SUPER_ADMIN') && $user->getEmail() === 'super.admin@orange.fr';
    }

    private function isAdmin(User $user): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
