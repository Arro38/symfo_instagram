<?php

namespace App\Controller;

use App\Entity\Liked;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class LikedController extends AbstractController
{
    #[Route('/api/liked/{id}', name: 'app_liked')]
    public function index(EntityManagerInterface $em, Post $post): JsonResponse
    {
        $user = $this->getUser();
        try {
            $liked = $em->getRepository(Liked::class)->findOneBy([
                'post' => ['id' => $post->getId()],
                'user' => ['id' => $user->getId()]
            ]);
            if ($liked) {
                $em->remove($liked);
                $em->flush();
                return new JsonResponse(["liked" => 0], JsonResponse::HTTP_OK);
            } else {
                $liked = new Liked();
                $liked->setUser($user);
                $liked->setPost($post);
                $em->persist($liked);
                $em->flush();
                return new JsonResponse(['liked' => 1], JsonResponse::HTTP_OK);
            }

        } catch (\Exception $e) {
            return new JsonResponse(null, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
