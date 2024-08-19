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
    public const EDIT = 'edit';
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::DELETE, self::TOGGLE, self::EDIT])) {
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
        /** @var Task $task */
        $task = $subject;

        // Add your logic here to determine if the user can perform the action
        // For example:
        // $user = $token->getUser();
        // if (!$user instanceof User) {
        //     return false;
        // }

        switch ($attribute) {
            case self::DELETE:
                return $this->canDelete($task, $token);
            case self::TOGGLE:
                return $this->canToggle($task, $token);
            case self::EDIT:
                return $this->canEdit($task, $token);
        }

        return false;
    }

    private function canDelete(Task $task, TokenInterface $token): bool
    {
        // Add your logic to determine if the user can delete the task
        return true;
    }

    private function canToggle(Task $task, TokenInterface $token): bool
    {
        // Add your logic to determine if the user can toggle the task
        return true;
    }

    private function canEdit(Task $task, TokenInterface $token): bool
    {
        // Add your logic to determine if the user can edit the task
        return true;
    }
}
