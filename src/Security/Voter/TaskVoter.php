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
            self::TOGGLE, self::DELETE, self::EDIT => $this->canPerformAction($task, $user),
            self::CREATE => true,
            default => throw new \LogicException('Ce voteur ne devrait pas être atteint.')
        };
    }

    private function canPerformAction(Task $task, User $user): bool
    {
        return $user === $task->getUser() || $this->security->isGranted('ROLE_ADMIN');
    }

    private function isSuperAdmin(User $user): bool
    {
        return $this->security->isGranted('ROLE_SUPER_ADMIN') && $user->getEmail() === 'super.admin@orange.fr';
    }
}
