<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();
    }

    public function testTaskEntity(): void
    {
        $user = new User();
        $task = new Task();
        $task
            ->setTitle('TestTitle')
            ->setContent('Content test')
            ->setUser($user)
        ;

        $this->assertEquals('Content test', $task->getContent());
        $this->assertEquals('TestTitle', $task->getTitle());
        $this->assertEquals($user, $task->getUser());
        $this->assertInstanceOf(\DateTimeImmutable::class, $task->getCreatedAt());
        $this->assertEquals(false, $task->isDone());

        $task->toggle(!$task->isDone());
        $this->assertEquals(true, $task->isDone());
    }

    public function testTaskEntityOnInvalidInput(): void
    {
        $task1 = new Task();
        $task1->setTitle('T')->setContent('');

        $task2 = new Task();
        $task2->setTitle('')->setContent('Valid content');

        $task3 = new Task();
        $task3->setTitle('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.')->setContent('Valid content');

        $validator = self::getContainer()->get(ValidatorInterface::class);
        $errors1 = $validator->validate($task1);
        $errors2 = $validator->validate($task2);
        $errors3 = $validator->validate($task3);

        $this->assertCount(2, $errors1);
        $this->assertCount(2, $errors2);
        $this->assertCount(1, $errors3);
        // $this->assertEquals('Le titre doit contenir au moins 2 caractères.', $errors1[0]->getMessage());
        // $this->assertEquals('Le contenu ne peut être vide.', $errors1[1]->getMessage());
        // $this->assertEquals('Le titre doit être renseigné.', $errors2[0]->getMessage());
        // $this->assertEquals('Le titre doit contenir au moins 2 caractères.', $errors2[1]->getMessage());
        // $this->assertEquals('Le titre ne peut excéder 255 caractères.', $errors3[0]->getMessage());
    }
}
