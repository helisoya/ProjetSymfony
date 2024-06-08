<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture implements FixtureGroupInterface, OrderedFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('pierre@pierresoftwares.com');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, '4h!RZ[VVB0G3xM8'));
        $user->setNom('Pierre');
        $user->setPrenom('Dupont');

        $user2 = new User();
        $user2->setEmail('fouzi@pierresoftwares.com');
        $user2->setRoles(['ROLE_USER']);
        $user2->setPassword($this->passwordHasher->hashPassword($user2, '12345678'));
        $user2->setNom('Fouzi');
        $user2->setPrenom('Miloud');

        $event = new Event();
        $event->setTitle("Pierre Engine Reveal 1.0");
        $event->setDescription("Reveal de la version 1.0 de Pierre Engine.");
        $event->setIsPublic(true);
        $event->setMaxParticipants(50);
        $event->setStartDate(new DateTime("2024-08-08 20:10"));
        $event->addParticipant($user);
        $event->setCreator($user2);

        $event2 = new Event();
        $event2->setTitle("Pierre Engine Reveal 1.1");
        $event2->setDescription("Reveal de la version 1.1 de Pierre Engine.");
        $event2->setIsPublic(false);
        $event2->setMaxParticipants(78);
        $event2->setStartDate(new DateTime("2024-09-08 20:10"));
        $event2->addParticipant($user);
        $event2->setCreator($user2);

        $manager->persist($user);
        $manager->persist($user2);
        $manager->persist($event);
        $manager->persist($event2);
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['Users'];
    }

    public function getOrder(): int
    {
        return 1;
    }
}
