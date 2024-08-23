<?php
namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class TaskFixtures extends Fixture
{

    public function __construct()
    {

    }

    public function load(ObjectManager $manager): void
    {
        // Create a task
        $task = new Task();
        $task->setTitle('Title')
              ->setContent('Content of the task')
              ->setCreatedAt(new \DateTimeImmutable('now'));

        $manager->persist($task);

        $manager->flush();
    }
}
