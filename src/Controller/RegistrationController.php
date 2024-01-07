<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'app_registration', methods: ['POST'])]
    public function index(UserPasswordHasherInterface $passwordHasher, Request $request, EntityManagerInterface $em): JsonResponse
    {

        $user = new User();
        try {
            // get data from form data
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $username = $request->request->get('username');
            $profilePicture = $request->files->get('profilePicture');

            if (!isset($email, $password, $username, $profilePicture)) {
                return new JsonResponse(['status' => 'Missing data'], 400);
            }

            // validate data
            if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse(['status' => 'Invalid email'], 400);
            }

            if (
                $password === null || !filter_var($password, FILTER_VALIDATE_REGEXP, [
                    'options' => [
                        'regexp' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/'
                    ]
                ])
            ) {
                return new JsonResponse(['status' => 'Invalid password'], 400);
            }

            if (
                $username === null || !filter_var($username, FILTER_VALIDATE_REGEXP, [
                    'options' => [
                        'regexp' => '/^[a-zA-Z0-9_]{3,25}$/'
                    ]
                ])
            ) {
                return new JsonResponse(['status' => 'Invalid username'], 400);
            }

            if (!($profilePicture instanceof UploadedFile)) {
                return new JsonResponse(['status' => 'Invalid profile picture'], 400);
            }
            $user->setEmail($email);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setUsername($username);
            $user->setImageFile($profilePicture);
            $em->persist($user);
            $em->flush();

            return new JsonResponse(['status' => 'User created!'], 201);
        } catch (\Exception $e) {
            // remove uploaded file if error
            if ($user->getImageName() !== null) {
                unlink('images/profiles/' . $user->getImageName());
            }
            return new JsonResponse(['status' => $e->getMessage()], 500);
        }

    }
}