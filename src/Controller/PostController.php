<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/post')]
class PostController extends AbstractController
{
    #[Route('/add', name: 'app_add_post', methods: ['POST'])]
    public function add(EntityManagerInterface $em, Request $request): JsonResponse
    {

        $description = $request->request->get("description");
        $picture = $request->files->get("picture");

        if (!isset($description) || !isset($picture)) {
            return new JsonResponse(null, JsonResponse::HTTP_BAD_REQUEST);
        }
        $post = new Post();
        try {
            $post->setDescription($description);
            $post->setImageFile($picture);
            $post->setCreatedBy($this->getUser());
            $post->setCreatedAt(new \DateTimeImmutable());
            $em->persist($post);
            $em->flush();
            return new JsonResponse(null, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            // remove uploaded file if error
            if ($post->getImageName() !== null) {
                unlink('images/posts/' . $post->getImageName());
            }
            return new JsonResponse(null, JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/remove/{id}', name: 'app_remove_post', methods: ['POST'])]
    public function remove(EntityManagerInterface $em, Post $post): JsonResponse
    {
        if ($post->getCreatedBy() !== $this->getUser()) {
            return new JsonResponse(null, JsonResponse::HTTP_BAD_REQUEST);
        }
        $em->remove($post);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_OK);
    }
}
