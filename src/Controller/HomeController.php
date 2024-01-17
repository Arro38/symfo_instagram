<?php

namespace App\Controller;

use App\Entity\Follow;
use App\Entity\Post;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Doctrine\ORM\EntityManagerInterface;


#[Route('/api')]
class HomeController extends AbstractController
{

    #[Route('/home', name: "app_home", methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $follows = $em->getRepository(Follow::class)->findBy(['follower' => $user->getId()]);
        $followings_ids = [];
        foreach ($follows as $follows) {
            $followings_ids[] = $follows->getFollowing()->getId();
        }
        $posts = $em->getRepository(Post::class)->findBy(['createdBy' => $followings_ids], ['createdAt' => 'DESC']);
        $serializer = new Serializer([new ObjectNormalizer()]);
        $jsonPosts = $serializer->normalize($posts, 'json', ['attributes' => ['id', 'description', 'imageUrl', 'createdAt', 'createdBy' => ['id', 'email', 'imageUrl', 'username'], 'likeds' => ['user' => ['id']], 'comments' => ['id', 'content', 'createdAt', 'user' => ['id', 'email', 'imageUrl', 'username']]]]);
        return new JsonResponse($jsonPosts, JsonResponse::HTTP_OK);

    }

    #[Route('/home/page/{i}', name: "app_home_page", methods: ['GET'])]
    public function indexPage(EntityManagerInterface $em, $i): JsonResponse
    {
        $user = $this->getUser();
        $follows = $em->getRepository(Follow::class)->findBy(['follower' => $user->getId()]);
        $followings_ids = [];
        foreach ($follows as $follows) {
            $followings_ids[] = $follows->getFollowing()->getId();
        }
        $posts = $em->getRepository(Post::class)->findBy(['createdBy' => $followings_ids], ['createdAt' => 'DESC'], 10, $i * 10);
        $serializer = new Serializer([new ObjectNormalizer()]);
        $jsonPosts = $serializer->normalize($posts, 'json', ['attributes' => ['id', 'description', 'imageUrl', 'createdAt', 'createdBy' => ['id', 'email', 'imageUrl', 'username'], 'likeds' => ['user' => ['id']], 'comments' => ['id', 'content', 'createdAt', 'user' => ['id', 'email', 'imageUrl', 'username']]]]);
        return new JsonResponse($jsonPosts, JsonResponse::HTTP_OK);

    }

    #[Route('/home/{id}', name: "app_home_user", methods: ['GET'])]
    public function indexUser(EntityManagerInterface $em, $id): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }
        $posts = $em->getRepository(Post::class)->findBy(['createdBy' => $user->getId()], ['createdAt' => 'DESC']);
        $serializer = new Serializer([new ObjectNormalizer()]);
        $jsonPosts = $serializer->normalize($posts, 'json', ['attributes' => ['id', 'createdAt', 'description', 'imageUrl', 'createdBy' => ['id', 'email', 'imageUrl', 'username'], 'likeds' => ['user' => ['id']], 'comments' => ['id', 'content', 'createdAt', 'user' => ['id', 'email', 'imageUrl', 'username']]]]);
        return new JsonResponse($jsonPosts, JsonResponse::HTTP_OK);

    }

    #[Route('/me', name: "app_me", methods: ['GET'])]
    public function me(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $serializer = new Serializer([new ObjectNormalizer()]);
        $jsonUser = $serializer->normalize($user, 'json', ['attributes' => ['id', 'email', 'imageUrl', 'username']]);
        return new JsonResponse($jsonUser, JsonResponse::HTTP_OK);
    }

    #[Route('/user/{id}', name: "app_get_user", methods: ['GET'])]
    public function userInfo(EntityManagerInterface $em, User $user): JsonResponse
    {
        $serializer = new Serializer([new ObjectNormalizer()]);
        $jsonUser = $serializer->normalize($user, 'json', ['attributes' => ['id', 'email', 'imageUrl', 'username']]);
        return new JsonResponse($jsonUser, JsonResponse::HTTP_OK);
    }

    #[Route('/users', name: "app_get_users", methods: ['GET'])]
    public function getUsers(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();
        $me = $this->getUser();
        foreach ($users as $key => $user) {
            if ($user->getId() == $me->getId()) {
                unset($users[$key]);
            }
        }
        $serializer = new Serializer([new ObjectNormalizer()]);
        $jsonUsers = $serializer->normalize($users, 'json', ['attributes' => ['id', 'email', 'imageUrl', 'username']]);
        return new JsonResponse($jsonUsers, JsonResponse::HTTP_OK);
    }
}
