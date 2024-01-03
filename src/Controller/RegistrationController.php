<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'app_registration')]
    public function index(UserPasswordHasherInterface $passwordHasher, Request $request, EntityManagerInterface $em): JsonResponse
    {

        if ($request->isMethod('POST') && $request->getContent() !== '') {
            try {
                $data = json_decode($request->getContent(), true);
                // validate data
                $email = $data['email'];
                $password = $data['password'];
                //check if email is valid
                if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return new JsonResponse(['status' => 'Invalid email'], 400);
                }

                // check if password is valid
                if (
                    $password === null || !filter_var($password, FILTER_VALIDATE_REGEXP, [
                        'options' => [
                            'regexp' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/'
                        ]
                    ])
                ) {
                    return new JsonResponse(['status' => 'Invalid password'], 400);
                }
                $user = new User();
                $user->setEmail($data['email']);
                $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
                $em->persist($user);
                $em->flush();
                return new JsonResponse(['status' => 'User created!'], 201);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 500);

            }
        } else {

            return new JsonResponse([
                'status' => 'error',
            ], 400);
        }
    }
}
