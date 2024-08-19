<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Task;
use App\Repository\UserRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\DataFixtures\TaskFixtures;
use App\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;


class TaskControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;
    private TaskRepository $taskRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->loadFixtures();
        $this->userRepository = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository(User::class);
        $this->taskRepository = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository(Task::class);
        // $this->user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'user']);
        // $this->admin = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'admin']);
    }

    private function loadFixtures(): void
    {
        // Purge the database
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Load fixtures
        $loader = new Loader();
        $loader->addFixture(new TaskFixtures());
        $loader->addFixture(new UserFixtures($this->client->getContainer()->get('security.user_password_hasher')));

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function testTasksList(): void
    {

        // Test non-authenticated access
        $this->client->request('GET', '/tasks');
        $this->client->followRedirect();
        $currentUrl = $this->client->getRequest()->getPathInfo();
        $this->assertEquals('/login', $currentUrl);

        // Test authenticated access
        $user = $this->userRepository->findOneBy(['email' => 'user@email.fr']);
        $this->assertNotNull($user, 'User not found in the database.');
        $this->client->loginUser($user);

        // Check if the user is authenticated
        $token = $this->client->getContainer()->get('security.token_storage')->getToken();
        $this->assertNotNull($token, 'Token should not be null');
        $this->assertInstanceOf(User::class, $token->getUser(), 'User should be an instance of User');

        // Make the request to /tasks
        $crawler = $this->client->request('GET', '/tasks');

        // Check if the response is a redirection
        // pourquoi je suis redirigé alor que je suis connecté avec un User qui a le ROLE_USER
        if ($this->client->getResponse()->isRedirection()) {
            $redirectUrl = $this->client->getResponse()->headers->get('Location');
            $this->fail("Unexpected redirection to $redirectUrl");
        }

        // Check the status code
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Check the content of the page
        $this->assertSelectorTextContains('h1', 'Liste des tâches à réaliser');
        $this->assertSelectorTextContains('.btn-info', 'Créer une nouvelle tâche');

        // Follow the link to create a new task
        $link = $crawler->selectLink('Créer une nouvelle tâche')->link();
        $crawler = $this->client->click($link);
        $currentUrl = $this->client->getRequest()->getPathInfo();
        $this->assertEquals('/tasks/create', $currentUrl);

        // Follow the link to return to the task list
        $link = $crawler->selectLink('Retour à la liste des tâches')->link();
        $crawler = $this->client->click($link);
        $currentUrl = $this->client->getRequest()->getPathInfo();
        $this->assertEquals('/tasks', $currentUrl);
    }




}
