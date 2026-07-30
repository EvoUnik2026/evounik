<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            $email = (string) $request->request->get('email', '');
            $password = (string) $request->request->get('password', '');
            $role = (string) $request->request->get('role', 'ROLE_USER');

            if (!$email || !$password) {
                $this->addFlash('error', 'Vul een geldig e-mailadres en wachtwoord in.');
                return $this->redirectToRoute('app_register');
            }

            if ($userRepository->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'Gebruiker met dit e-mailadres bestaat al.');
                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setRoles([$role]);
            $user->setPassword($passwordHasher->hashPassword($user, $password));

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig');
    }
}
