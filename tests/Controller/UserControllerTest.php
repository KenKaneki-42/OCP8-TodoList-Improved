<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;


class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->loadFixtures();
        $this->userRepository = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository(User::class);
    }

    private function loadFixtures(): void
    {
        // Purge the database
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Load fixtures
        $loader = new Loader();
        $loader->addFixture(new UserFixtures($this->client->getContainer()->get('security.user_password_hasher')));

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function testUsersList(): void
    {
        // Test non-authenticated access
        $this->client->followRedirects();
        $this->client->request('GET', '/users');
        $currentUrl = $this->client->getRequest()->getPathInfo();
        $this->assertEquals('/login', $currentUrl);

        // Test regular user access
        $regularUser = $this->userRepository->findOneByUsername('user');
        $this->assertNotNull($regularUser);
        $this->assertContains('ROLE_USER', $regularUser->getRoles());
        $this->client->loginUser($regularUser);
        $this->client->request('GET', '/users');
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // Test admin user access
        $adminUser = $this->userRepository->findOneByUsername('admin');
        $this->assertNotNull($adminUser);
        $this->assertContains('ROLE_ADMIN', $adminUser->getRoles());
        $this->client->loginUser($adminUser);

        // Faire une requête GET vers /users
        $crawler = $this->client->request('GET', '/users');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        $this->assertCount(1, $crawler->filter('table'));
    }


    public function testUserCreationSuccess(): void
    {
        $this->client->followRedirects();
        // Test non-authenticated access
        $this->client->request('GET', '/users/create');
        $currentUrl = $this->client->getRequest()->getPathInfo();
        $this->assertEquals('/login', $currentUrl);

        // Test regular user access
        $this->client->loginUser($this->userRepository->findOneByUsername('user'));
        $this->client->request('GET', '/users/create');
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // Test admin user access
        $this->client->loginUser($this->userRepository->findOneByUsername('admin'));
        $this->client->request('GET', '/users/create');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h1', 'Créer un utilisateur');

        // Submit the form
        $crawler = $this->client->submitForm('Ajouter', ['user[username]' => 'createduser', 'user[password][first]' => 'password', 'user[password][second]' => 'password', 'user[email]' => 'createduser@email.fr', 'user[roles]' => 'ROLE_USER']);

        $currentUrl = rtrim($this->client->getRequest()->getPathInfo(), '/');
        $user = $this->userRepository->findOneByEmail('createduser@email.fr');
        $userCount = $this->userRepository->findByEmail('createduser@email.fr');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $userCount);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        $this->assertEquals('/users', $currentUrl);
        $this->assertEquals('L\'utilisateur a bien été ajouté.', trim($crawler->filter('.alert-success')->text()));
    }

    public function testUserCreationFailureOnUsernameUnicity(): void
    {
        $this->client->loginUser($this->userRepository->findOneByUsername('admin'));

        // Vérifiez que la page de création d'utilisateur est accessible
        $crawler = $this->client->request('GET', '/users/create');
        $this->assertResponseIsSuccessful();

        // in fixtures we have already created a user with username 'user'
        // Soumettez le formulaire
        $crawler = $this->client->submitForm('Ajouter', [
          'user[username]' => 'user',
          'user[password][first]' => 'password',
          'user[password][second]' => 'password',
          'user[email]' => 'createduser@email.fr',
          'user[roles]' => 'ROLE_USER'
        ]);

        // Vérifiez que l'utilisateur n'a pas été créé et que l'erreur est affichée
        $currentUrl = $this->client->getRequest()->getPathInfo();
        $userCount = $this->userRepository->findByUsername('user');
        $userCountByMail = $this->userRepository->findByEmail('createduser@email.fr');

        $this->assertEquals('/users/create', $currentUrl);
        $this->assertCount(1, $userCount);
        $this->assertCount(0, $userCountByMail);

        // Vérifiez que l'élément d'erreur existe avant d'y accéder
        $this->assertGreaterThan(0, $crawler->filter('.form-error-message')->count(), 'L\'élément .form-error-message n\'existe pas.');
        $this->assertEquals('Ce nom d\'utilisateur est déjà utilisé.', trim($crawler->filter('.form-error-message')->text()));
    }

    public function testUserCreationFailureOnEmailUnicity(): void
    {
        $this->client->loginUser($this->userRepository->findOneByUsername('admin'));

        // Vérifiez que la page de création d'utilisateur est accessible
        $crawler = $this->client->request('GET', '/users/create');
        $this->assertResponseIsSuccessful();

        // in fixtures we have already created a user with username 'user'
        // Soumettez le formulaire
        $crawler = $this->client->submitForm('Ajouter', [
          'user[username]' => 'emailAledreadyExist',
          'user[password][first]' => 'password',
          'user[password][second]' => 'password',
          'user[email]' => 'user@email.fr',
          'user[roles]' => 'ROLE_USER'
        ]);


        $currentUrl = $this->client->getRequest()->getPathInfo();
        $userCount = $this->userRepository->findByEmail('user@email.fr');
        $userCountByUsername = $this->userRepository->findByUsername('emailAledreadyExist');

        $this->assertEquals('/users/create', $currentUrl);
        $this->assertCount(1, $userCount);
        $this->assertCount(0, $userCountByUsername);
        $this->assertEquals("Il existe déjà un compte avec cet email.", trim($crawler->filter('.form-error-message')->text()));
    }

    public function testUserCreationFailureOnPasswordMistake(): void
    {
        $this->client->loginUser($this->userRepository->findOneByUsername('admin'));

        $this->client->request('GET', '/users/create');
        $crawler = $this->client->submitForm('Ajouter', ['user[username]' => 'Babylone', 'user[password][first]' => 'password', 'user[password][second]' => 'password2', 'user[email]' => 'createduser@email.fr', 'user[roles]' => 'ROLE_USER']);

        $currentUrl = $this->client->getRequest()->getPathInfo();
        $userCount = $this->userRepository->findByEmail('createduser@email.fr');

        $this->assertEquals('/users/create', $currentUrl);
        $this->assertCount(0, $userCount);
        $this->assertEquals('Les mots de passe ne correspondent pas.', trim($crawler->filter('.form-error-message')->text()));
    }

    public function testUserEdit(): void
    {
        $this->client->followRedirects();
        $this->client->loginUser($this->userRepository->findOneByUsername('admin'));
        $this->client->request('GET', '/users/create');
        $this->client->submitForm('Ajouter', ['user[username]' => 'createduser', 'user[password][first]' => 'password', 'user[password][second]' => 'password', 'user[email]' => 'createduser@email.fr', 'user[roles]' => 'ROLE_USER']);

        $user = $this->userRepository->findOneByUsername('createduser');
        $userId = $user->getId();
        $this->client->request('GET', "/users/".$userId.'/edit');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h1', 'Modifier '.$user->getUsername());

        $crawler = $this->client->submitForm('Modifier', ['user[username]' => 'createduserEdited', 'user[password][first]' => 'password', 'user[password][second]' => 'password', 'user[email]' => 'createduser@email.fr', 'user[roles]' => 'ROLE_USER']);

        $currentUrl = rtrim($this->client->getRequest()->getPathInfo(), '/');
        $userCount = $this->userRepository->findByUsername('createduserEdited');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $userCount);
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        $this->assertEquals('/users', $currentUrl);
        $this->assertEquals('L\'utilisateur a bien été modifié.', trim($crawler->filter('.alert-success')->text()));
    }
}
