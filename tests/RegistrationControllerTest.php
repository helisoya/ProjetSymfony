<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Ensure we have a clean database
        $container = static::getContainer();

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();
        $this->userRepository = $container->get(UserRepository::class);

        foreach ($this->userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();
    }

    public function testRegister(): void
    {
        // Register a new user
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('S\'inscrire', [
            'registration_form[email]' => 'me@example.com',
            'registration_form[nom]' => 'test',
            'registration_form[prenom]' => 'test',
            'registration_form[password][first]' => 'pyi%8TFV7&Ic;a(a&]Tk',
            'registration_form[password][second]' => 'pyi%8TFV7&Ic;a(a&]Tk',
            'registration_form[agreeTerms]' => true,
        ]);

        // Ensure the response redirects after submitting the form, the user exists, and is not verified
        self::assertResponseRedirects('/profile');
        self::assertCount(1, $this->userRepository->findAll());
    }
}
