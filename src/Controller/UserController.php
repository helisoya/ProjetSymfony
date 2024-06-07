<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditProfileFormType;
use App\Form\RegistrationFormType;
use App\Security\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{

    public function __construct(
        private readonly EntityManagerInterface      $entityManager,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly Security                    $security,
        private readonly ValidatorInterface          $validator,
        private readonly TokenStorageInterface       $tokenStorage
    )
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $user->setNom($form->get('nom')->getData());
            $user->setPrenom($form->get('prenom')->getData());

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // do anything else you need here, like send an email

            return $this->security->login($user, AppAuthenticator::class, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/profile/edit', name: 'app_edit_profile')]
    public function editProfile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $userEmail = $user->getEmail();

        $form = $this->createForm(EditProfileFormType::class, $user);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $passwordHasChanged = true;
            $emailHasChanged = true;

            if ($form->get('password')->getData() === null) $passwordHasChanged = false;
            if ($form->get('email')->getData() === $userEmail) $emailHasChanged = false;

            if ($passwordHasChanged) {
                $user->setPassword(
                    $this->userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
            }

            $currentErrors = $this->validator->validate($user);
            $errors = [];

            if (count($currentErrors) > 0) {
                foreach ($currentErrors as $error) {
                    $message = $error->getMessage();
                    $field = $error->getPropertyPath();
                    $errors[] = "$message ($field)";
                }

                return $this->render('user/editProfile.html.twig', [
                    'editForm' => $form->createView(),
                    'errors' => $errors,
                ]);
            }

            $this->entityManager->flush();

            if ($emailHasChanged || $passwordHasChanged) {
                return $this->redirectToRoute('app_login');
            }

            $this->tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

            return $this->redirectToRoute('app_profile');
        } else if ($form->isSubmitted()) {
            dd($form->getErrors(true));
        }

        return $this->render('user/editProfile.html.twig', [
            'editForm' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function showProfile(): Response
    {
        $user = $this->security->getUser();

        if ($user === null) {
            return $this->redirect('/login');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }
}
