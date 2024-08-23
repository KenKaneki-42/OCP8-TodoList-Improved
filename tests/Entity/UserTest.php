<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class UserTest extends KernelTestCase
{

    public function setUp(): void
    {
        self::bootKernel();
    }

    public function testUserEntity(): void
    {

        $task1 = new Task();
        $task2 = new Task();
        $user = new User();
        $user
            ->setUsername('John Doe')
            ->setEmail('john.doe@gmail.com')
            ->setPassword('password')
            ->setRoles(['ROLE_ADMIN'])
            ->addTask($task1)
            ->addTask($task2)
            ->removeTask($task1)
        ;

        $this->assertEquals('John Doe', $user->getUsername());
        $this->assertEquals('john.doe@gmail.com', $user->getEmail());
        $this->assertEquals('password', $user->getPassword());
        $this->assertEquals(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        $this->assertCount(1, $user->getTasks());
        $this->assertContains($task2, $user->getTasks());
        $this->assertNotContains($task1, $user->getTasks());

    }

    public function testUserEntityOnInvalidInput(): void
    {

        $user1 = new User();
        $user1
            ->setEmail('')
            ->setPassword('')
            ->setUsername('')
            ;

        $user2 = new User();
        $user2
            ->setEmail('sylvain.vandermeersch')
            ->setPassword('pass')
            ->setUsername('User2');

        $user3 = new User();
        $user3
            ->setEmail('UserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUser@email.com')
            ->setPassword('UserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUser')
            ->setUsername('UserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUserUser');

        $validator = self::getContainer()->get(ValidatorInterface::class);
        $errors1 = $validator->validate($user1);
        $errors2 = $validator->validate($user2);
        $errors3 = $validator->validate($user3);

        $messages1 = array_map(fn($error) => $error->getMessage(), iterator_to_array($errors1));
        $messages2 = array_map(fn($error) => $error->getMessage(), iterator_to_array($errors2));
        $messages3 = array_map(fn($error) => $error->getMessage(), iterator_to_array($errors3));

        $this->assertCount(5, $errors1);
        $this->assertContains('Le nom de l\'utilisateur doit être renseigné.', $messages1);
        $this->assertContains('Le nom de l\'utilisateur doit faire au moins 2 caractères.', $messages1);
        $this->assertContains('Vous devez saisir une adresse email.', $messages1);
        $this->assertContains('Le mot de passe doit être renseigné.', $messages1);
        $this->assertContains('Le mot de passe doit faire au moins 8 caractères.', $messages1);

        $this->assertCount(3, $errors2);
        $this->assertContains('Le format de l\'adresse n\'est pas correcte.', $messages2);
        $this->assertContains('Le mot de passe doit faire au moins 8 caractères.', $messages2);
        $this->assertContains('Le nom de l\'utilisateur doit seulement contenir des lettres et espaces.', $messages2);

        $this->assertCount(3, $errors3);
        $this->assertContains('L\'email ne peut pas faire plus de 255 caractères.', $messages3);
        $this->assertContains('Le mot de passe ne peut excéder 255 caractères.', $messages3);
        $this->assertContains('Le nom de l\'utilisateur ne peut pas faire plus de 120 caractères.', $messages3);
    }

}
