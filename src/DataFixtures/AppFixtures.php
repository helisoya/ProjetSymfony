<?php

namespace App\DataFixtures;

use App\Entity\User;
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
        $user->setPassword($this->passwordHasher->hashPassword($user, '12345678'));
        $user->setNom('Pierre');
        $user->setPrenom('Dupont');

        $manager->persist($user);
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
