<?php

namespace App\Controller;

use App\Entity\Follow;
use App\Entity\Post;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
        // get information for my homepage 
        // 1. get all posts from users that I follow
        $follows = $em->getRepository(Follow::class)->findBy(['follower' => $user->getId()]);
        $followings_ids = [];
        foreach ($follows as $follows) {
            $followings_ids[] = $follows->getFollowing()->getId();
        }
        $posts = $em->getRepository(Post::class)->findBy(['createdBy' => $followings_ids], ['createdAt' => 'DESC']);
        $serializer = new Serializer([new ObjectNormalizer()]);
        $jsonPosts = $serializer->normalize($posts, 'json', ['attributes' => ['id', 'description', 'imageName', 'createdBy' => ['id', 'email', 'profilePicture'], 'likeds' => ['user' => ['id']], 'comments' => ['id', 'content', 'createdAt', 'user' => ['id', 'email', 'profilePicture']]]]);
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
        $jsonPosts = $serializer->normalize($posts, 'json', ['attributes' => ['id', 'description', 'imageName', 'createdBy' => ['id', 'email', 'profilePicture'], 'likeds' => ['user' => ['id']], 'comments' => ['id', 'content', 'createdAt', 'user' => ['id', 'email', 'profilePicture']]]]);
        return new JsonResponse($jsonPosts, JsonResponse::HTTP_OK);

    }
}
