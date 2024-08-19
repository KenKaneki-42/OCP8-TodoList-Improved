<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

class SecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // initialization of the entity manager to be able to load fixtures
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        // Purge the database
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Load fixtures
        $fixture = new UserFixtures($this->client->getContainer()->get('security.user_password_hasher'));
        $fixture->load($this->entityManager);
    }

    // ajouter un user avec le role admin

    public function testLoginAccess(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThanOrEqual(1, $crawler->filter('form.login-form')->count());
        $this->assertSelectorTextContains('h1', 'Connexion');
    }

    public function testLoginSuccess(): void
    {
      $crawler = $this->client->request(Request::METHOD_GET, '/login');

      // Vérifiez que le formulaire contient les champs attendus
      $this->assertGreaterThan(0, $crawler->filter('input[name="_username"]')->count(), 'Le champ _username est manquant');
      $this->assertGreaterThan(0, $crawler->filter('input[name="_password"]')->count(), 'Le champ _password est manquant');

      // Soumettez le formulaire avec les données de connexion
      $this->client->submitForm('Confirmer', [
          '_username' => 'user@email.fr',
          '_password' => 'password'
      ]);

      // Suivez la redirection après la soumission du formulaire
      $crawler = $this->client->followRedirect();
      $currentUrl = $this->client->getRequest()->getPathInfo();

      // Vérifiez que la réponse est réussie
      $this->assertResponseIsSuccessful();

      // Vérifiez que l'utilisateur est redirigé vers la page d'accueil
      $this->assertEquals('/', $currentUrl);

      // Vérifiez que le message de succès est affiché
      // probleme avec l'ajout de flash message dans la session pour l'écouteur d'évènement de login -> passer par le subscriber avec un extends de l'abstract controller pour avoir accès à addFlash
      $this->assertGreaterThan(0, $crawler->filter('.alert-success')->count(), 'Le message de succès est manquant');
      $this->assertEquals('Vous êtes désormais connecté.', trim($crawler->filter('.alert-success')->text()));
    }

    public function testLoginFailure(): void
    {
        $this->client->request(Request::METHOD_GET, '/login');
        $this->client->submitForm('Confirmer', ['_username' => 'invalid@email.fr', '_password' => 'invalidpassword']);
        $crawler = $this->client->followRedirect();
        $currentUrl = $this->client->getRequest()->getPathInfo();

        $this->assertEquals('/login', $currentUrl);

        // Ensure the correct error message is displayed
        $this->assertCount(1, $crawler->filter('.alert-danger'));
        $this->assertEquals('Identifiants invalides.', trim($crawler->filter('.alert-danger')->text()));
    }

    public function testLogout(): void
    {
        $crawler = $this->client->request('GET', '/logout');
        $crawler = $this->client->followRedirect();
        $currentUrl = $this->client->getRequest()->getPathInfo();

        $this->assertEquals('/login', $currentUrl);
        $this->assertCount(1, $crawler->filter('.alert-success'));
        $this->assertEquals('Vous êtes désormais déconnecté.', trim($crawler->filter('.alert-success')->text()));
    }
}
