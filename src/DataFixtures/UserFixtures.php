<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            ['email' => 'superadmin@example.com', 'password' => 'SuperAdminPass123!', 'roles' => ['ROLE_SUPER_ADMIN']],
            ['email' => 'admin@example.com', 'password' => 'AdminPass123!', 'roles' => ['ROLE_ADMIN']],
            ['email' => 'editor@example.com', 'password' => 'EditorPass123!', 'roles' => ['ROLE_EDITOR']],
            ['email' => 'reviewer@example.com', 'password' => 'ReviewerPass123!', 'roles' => ['ROLE_REVIEWER']],
            ['email' => 'guest@example.com', 'password' => 'GuestPass123!', 'roles' => ['ROLE_GUEST']],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $userData['password']));
            $manager->persist($user);
        }

        $manager->flush();
    }
}
