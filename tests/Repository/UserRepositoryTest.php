<?php
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends TestCase
{
    private $entityManager;
    private $userRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $classMetadata = new ClassMetadata(User::class);
        $classMetadata->name = User::class;

        $this->entityManager->method('getClassMetadata')
            ->willReturn($classMetadata);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->userRepository = new UserRepository($registry);
    }

    public function testUpgradePassword(): void
    {
        $user = new User();
        $newHashedPassword = 'new_hashed_password';

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userRepository->upgradePassword($user, $newHashedPassword);

        $this->assertSame($newHashedPassword, $user->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $unsupportedUser = $this->createMock(PasswordAuthenticatedUserInterface::class);
        $newHashedPassword = 'new_hashed_password';

        $this->userRepository->upgradePassword($unsupportedUser, $newHashedPassword);
    }
}
