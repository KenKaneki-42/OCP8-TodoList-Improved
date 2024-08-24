<?php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create a user
        $user = new User();
        $user->setUsername('user')
            ->setEmail('user@email.fr')
            ->setPassword($this->passwordHasher->hashPassword($user, 'password'))
            ->setRoles(['ROLE_USER']);

        $manager->persist($user);

        // Create an admin
        $admin = new User();
        $admin->setUsername('admin')
            ->setEmail('admin@email.fr')
            ->setPassword($this->passwordHasher->hashPassword($admin, 'password'))
            ->setRoles(['ROLE_ADMIN']);

        $manager->persist($admin);

        // Create a super admin
        $superAdmin = new User();
        $superAdmin->setUsername('super_admin')
            ->setEmail('super.admin@orange.fr')
            ->setPassword($this->passwordHasher->hashPassword($superAdmin, 'password'))
            ->setRoles(['ROLE_SUPER_ADMIN']);

        $manager->flush();
    }
}
