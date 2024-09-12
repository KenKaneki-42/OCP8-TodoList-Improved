<?php

namespace App\Tests\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use App\Security\Voter\TaskVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class TaskVoterTest extends TestCase
{
    private MockObject|Security|null $security;
    private MockObject|TokenInterface|null $token;

    public function setUp(): void
    {
        $this->security = $this
            ->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->token = $this
            ->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testTaskVoterOnBasicUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $anotherUser = new User();
        $anotherUser->setRoles(['ROLE_USER']);

        $this->token
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user);

        $voter = new TaskVoter($this->security);

        $task1 = new Task();
        $task1->setUser(null);

        $task2 = new Task();
        $task2->setUser($user);

        $task3 = new Task();
        $task3->setUser($anotherUser);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $task3, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $task1, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $task1, [TaskVoter::TOGGLE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task2, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task2, [TaskVoter::TOGGLE]));
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $task3, [TaskVoter::TOGGLE]));
    }

    public function testTaskVoterOnAdminUser(): void
    {
        $admin = new User();
        $admin->setRoles(['ROLE_ADMIN']);

        $anotherUser = new User();
        $anotherUser->setRoles(['ROLE_USER']);

        $this->token
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($admin);

        $this->security
            ->expects($this->atLeastOnce())
            ->method('isGranted')
            ->willReturn(true);

        $voter = new TaskVoter($this->security);

        $task1 = new Task();
        $task1->setUser(null);

        $task2 = new Task();
        $task2->setUser($admin);

        $task3 = new Task();
        $task3->setUser($anotherUser);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task1, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task1, [TaskVoter::TOGGLE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task2, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task2, [TaskVoter::TOGGLE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task3, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task3, [TaskVoter::TOGGLE]));
    }

    public function testTaskVoterOnSuperAdminUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_SUPER_ADMIN'])->setEmail('super.admin@orange.fr');

        $anotherUser = new User();
        $anotherUser->setRoles(['ROLE_USER']);

        $this->token
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user);

        $this->security
            ->expects($this->atLeastOnce())
            ->method('isGranted')
            ->willReturn(true);

        $voter = new TaskVoter($this->security);

        $task1 = new Task();
        $task1->setUser(null);

        $task2 = new Task();
        $task2->setUser($user);

        $task3 = new Task();
        $task3->setUser($anotherUser);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task1, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task1, [TaskVoter::TOGGLE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task2, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task2, [TaskVoter::TOGGLE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task3, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $task3, [TaskVoter::TOGGLE]));
    }

    public function testTaskVoterOnUnauthenticatedUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $anotherUser = new User();
        $anotherUser->setRoles(['ROLE_USER']);

        $this->token
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn(null);

        $voter = new TaskVoter($this->security);

        $task1 = new Task();
        $task1->setUser(null);

        $task2 = new Task();
        $task2->setUser($user);

        $task3 = new Task();
        $task3->setUser($anotherUser);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $task1, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $task1, [TaskVoter::TOGGLE]));
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $task2, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $task2, [TaskVoter::TOGGLE]));
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $task3, [TaskVoter::DELETE]));
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $task3, [TaskVoter::TOGGLE]));
    }
}
