<?php

namespace App\Tests\Repository;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\User;

class TaskRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TaskRepository $taskRepository;

    protected function setUp(): void
    {
        // Call parent setUp method to configure the kernel environment before booting
        parent::setUp();
        self::bootKernel();
        // configure EntityManager
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        // configure TaskRepository
        $this->taskRepository = self::getContainer()->get(TaskRepository::class);
        // clear Task table before each test to avoid duplicates
        $this->entityManager->createQuery('DELETE FROM App\Entity\Task')->execute();
    }

    public function testSave(): void
    {
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setContent('This is a test task content');
        $task->setIsDone(false);
        $task->setCreatedAt(new \DateTimeImmutable());

        $this->taskRepository->save($task, true);

        $savedTask = $this->taskRepository->find($task->getId());

        $this->assertNotNull($savedTask);
        $this->assertEquals('Test Task', $savedTask->getTitle());
    }

    public function testRemove(): void
    {
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setContent('This is a test task content');
        $task->setIsDone(false);
        $task->setCreatedAt(new \DateTimeImmutable());

        $this->taskRepository->save($task, true);
        $taskId = $task->getId();

        $this->taskRepository->remove($task, true);

        $removedTask = $this->taskRepository->find($taskId);

        $this->assertNull($removedTask);
    }

    public function testFindUserTasks(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'user@email.fr']);

        $task1 = new Task();
        $task1->setTitle('Task 1');
        $task1->setContent('Content for Task 1');
        $task1->setIsDone(false);
        $task1->setCreatedAt(new \DateTimeImmutable());
        $task1->setUser($user);

        $task2 = new Task();
        $task2->setTitle('Task 2');
        $task2->setContent('Content for Task 2');
        $task2->setIsDone(true);
        $task2->setCreatedAt(new \DateTimeImmutable());
        $task2->setUser($user);

        $this->taskRepository->save($task1, true);
        $this->taskRepository->save($task2, true);

        $tasks = $this->taskRepository->findUserTasks($user);

        $this->assertCount(1, $tasks);
        $this->assertEquals('Task 1', $tasks[0]->getTitle());
    }

    public function testFindUserTasksDone(): void
    {
        $task1 = new Task();
        $task1->setTitle('Task 1');
        $task1->setContent('Content for Task 1');
        $task1->setIsDone(false);
        $task1->setCreatedAt(new \DateTimeImmutable());

        $task2 = new Task();
        $task2->setTitle('Task 2');
        $task2->setContent('Content for Task 2');
        $task2->setIsDone(true);
        $task2->setCreatedAt(new \DateTimeImmutable());

        $this->taskRepository->save($task1, true);
        $this->taskRepository->save($task2, true);

        $tasks = $this->taskRepository->findUserTasksDone();

        $this->assertCount(1, $tasks);
        $this->assertEquals('Task 2', $tasks[0]->getTitle());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->entityManager !== null) {
            $this->entityManager->close();
        }
    }
}
