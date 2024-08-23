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
    }

    private function loadFixtures(): void
    {
        // Purge the database before each test
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

        $this->client->followRedirects();

        // Test non-authenticated access
        $this->client->request('GET', '/tasks');
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
        $currentUrl = rtrim($this->client->getRequest()->getPathInfo(), '/');
        $this->assertEquals('/tasks', $currentUrl);
    }

    public function testTasksDone(): void
    {
        $this->client->followRedirects();

        $this->client->request('GET', '/tasks/done');
        $currentUrl = $this->client->getRequest()->getPathInfo();

        $this->assertEquals('/login', $currentUrl);

        $this->client->loginUser($this->userRepository->findOneBy(['email' => "user@email.fr"]));
        $crawler = $this->client->request('GET', '/tasks/done');

        $this->assertSelectorTextContains('h1', 'Liste des tâches terminées');
        $this->assertSelectorTextContains('.btn-info', 'Créer une nouvelle tâche');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $link = $crawler->selectLink('Créer une nouvelle tâche')->link();
        $crawler = $this->client->click($link);
        $currentUrl = $this->client->getRequest()->getPathInfo();

        $this->assertEquals('/tasks/create', $currentUrl);

        $link = $crawler->selectLink('Retour à la liste des tâches')->link();
        $crawler = $this->client->click($link);
        $currentUrl = rtrim($this->client->getRequest()->getPathInfo(), '/');

        $this->assertEquals('/tasks', $currentUrl);
    }

    public function testTasksCreate(): void
    {
        $this->client->followRedirects();
        $this->client->request('GET', '/tasks/create');
        $currentUrl = $this->client->getRequest()->getPathInfo();
        $this->assertEquals('/login', $currentUrl);
        $user = $this->userRepository->findOneBy(['email' => 'user@email.fr']);
        $this->client->loginUser($user);
        $this->client->request('GET', '/tasks/create');

        $this->assertSelectorTextContains('h1', 'Créer une nouvelle tâche');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->submitForm('Ajouter', ['task[title]' => 'TestTitle', 'task[content]' => 'TestContent']);
        // $crawler = $this->client->followRedirect();
        $currentUrl = rtrim($this->client->getRequest()->getPathInfo(), '/');
        $task = $this->taskRepository->findOneByUser($user);
        $userTask = $task->getUser();
        $taskCount = $this->taskRepository->findByTitle('Title');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame($user, $userTask);
        $this->assertCount(1, $taskCount);
        $this->assertEquals('/tasks', $currentUrl);
        $this->assertResponseIsSuccessful();
        $this->assertEquals('La tâche a bien été ajoutée.', trim($this->client->getCrawler()->filter('.alert-success')->text()));
    }

    public function testTasksEdit(): void
    {
        $this->client->followRedirects();

        // Create a task with the user
        $user = $this->userRepository->findOneBy(['email' => 'user@email.fr']);
        $this->client->loginUser($user);
        $this->client->request('GET', '/tasks/create');
        $this->client->submitForm('Ajouter', ['task[title]' => 'TitleTestEdit', 'task[content]' => 'ContentTestEdit']);

        // Retrieve the task to edit
        $task = $this->taskRepository->findOneByUser($user);
        $taskId = $task->getId();
        $this->client->request('GET', '/tasks/'.$taskId.'/edit');
        $this->assertSelectorTextContains('h1', 'Modification de tâche');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        //Submit the form with the new values
        $this->client->submitForm('Modifier', ['task[title]' => 'TitleTestEdited', 'task[content]' => 'ContentTestEdited']);

        $currentUrl = $this->client->getRequest()->getPathInfo();
        $task = $this->taskRepository->findOneByUser($user);
        $taskCount = $this->taskRepository->findByTitle('TitleTest');
        $editedTaskCount = $this->taskRepository->findByTitle('TitleTestEdited');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $taskCount);
        $this->assertCount(1, $editedTaskCount);
        $this->assertEquals('/tasks', rtrim($currentUrl, '/'));
        $crawler = $this->client->getCrawler();
        $this->assertEquals('La tâche a bien été modifiée.', trim($crawler->filter('.alert-success')->text()));

        $link = $crawler->selectLink('TitleTestEdited')->link();
        $crawler = $this->client->click($link);
        $currentUrl = $this->client->getRequest()->getPathInfo();

        $this->assertEquals('/tasks/'.$taskId.'/edit', $currentUrl);
    }

    public function testTasksSimpleToggle(): void
    {
        $this->client->followRedirects();

        $user = $this->userRepository->findOneBy(['email' => 'user@email.fr']);
        $this->client->loginUser($user);
        $this->client->request('GET', '/tasks/create');
        $this->client->submitForm('Ajouter', ['task[title]' => 'TitleToggleSimple', 'task[content]' => 'ContentToggleSimple']);
        $crawler = $this->client->getCrawler();

        // Find the specific <h4> by its content
        $h4ToClick = $crawler->filter('h4:contains("TitleToggleSimple")')->first();
        // Find the parent .thumbnail div containing the <h4> and the toggle button
        $thumbnailDiv = $h4ToClick->closest('.thumbnail');
        // Use the button text to find the specific toggle button in the parent
        $toggleButton = $thumbnailDiv->selectButton('Marquer comme faite');
        // Submit the form associated with the toggle button
        $this->client->submit($toggleButton->form());

        $currentUrl = $this->client->getRequest()->getPathInfo();
        $toggledTask = $this->taskRepository->findOneByUser($user);

         // redifine the crawler to get the updated page
        $crawler = $this->client->getCrawler();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertEquals('/tasks', rtrim($currentUrl, '/'));
        $this->assertEquals(true, $toggledTask->isDone());
        $this->assertEquals("La tâche '".$toggledTask->getTitle()."' a bien été marquée comme faite.", trim($crawler->filter('.alert-success')->text()));

        $crawler = $this->client->request('GET', '/tasks/done');
        $filteredH4 = $crawler->filter('h4:contains("Title")');

        $this->assertCount(1, $filteredH4);
    }

    public function testTasksDelete(): void
    {
        $this->client->followRedirects();
        $user = $this->userRepository->findOneBy(['email' => 'user@email.fr']);
        $this->client->loginUser($user);
        $this->client->request('GET', '/tasks/create');
        $this->client->submitForm('Ajouter', ['task[title]' => 'TitleToDelete', 'task[content]' => 'ContentToDelete']);

        $crawler = $this->client->getCrawler();
        // Find the specific <h4> by its content
        $h4ToClick = $crawler->filter('h4:contains("TitleToDelete")')->first();
        // Find the parent .thumbnail div containing the <h4> and the toggle button
        $thumbnailDiv = $h4ToClick->closest('.thumbnail');
        // Use the button text to find the specific toggle button in the parent
        $deleteButton = $thumbnailDiv->selectButton('Supprimer');
        // Submit the form associated with the toggle button
        $this->client->submit($deleteButton->form());

        $currentUrl = $this->client->getRequest()->getPathInfo();
        $taskCount = $this->taskRepository->findByTitle('TitleToDelete');

        // redifine the crawler to get the updated page
        $crawler = $this->client->getCrawler();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertEquals('/tasks', rtrim($currentUrl, '/'));
        $this->assertCount(0, $taskCount);
        $this->assertEquals('La tâche a bien été supprimée.', trim($crawler->filter('.alert-success')->text()));
    }

}
