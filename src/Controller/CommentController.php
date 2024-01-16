<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Follow;
use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

#[Route('/api/comment')]
class CommentController extends AbstractController
{
    #[Route('/add/{id}', name: 'app_add_comment', methods: ['POST'])]
    public function add(EntityManagerInterface $em, Request $request, Post $post): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'];
        if (!isset($content)) {
            return new JsonResponse("Content can't be null", JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!$post) {
            return new JsonResponse("Post not found", JsonResponse::HTTP_BAD_REQUEST);
        }
        $user = $this->getUser();
        // check if user got permission to add comment to this post
        $postCreatedBy = $post->getCreatedBy();
        // check if user is a follower of post's creator
        $isFollower = $em->getRepository(Follow::class)->findOneBy(["follower" => $user->getId(), "following" => $postCreatedBy->getId()]);
        // dd($postCreatedBy, $user, $isFollower);
        if (!$isFollower && ($user != $postCreatedBy)) {
            return new JsonResponse("Only follower or owner can't add comment", JsonResponse::HTTP_BAD_REQUEST);
        }
        $comment = new Comment();
        try {
            $comment->setContent($content);
            $comment->setPost($post);
            $comment->setUser($user);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $em->persist($comment);
            $em->flush();
            $serializer = new Serializer([new ObjectNormalizer()]);
            $jsonComment = $serializer->normalize($comment, 'json', ['attributes' => ['id', 'content', 'createdAt', 'user' => ['id', 'email', 'imageUrl', 'username']]]);
            return new JsonResponse($jsonComment, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/remove/{id}', name: 'app_remove_comment', methods: ['POST'])]
    public function remove(EntityManagerInterface $em, Comment $comment): JsonResponse
    {
        if ($comment->getUser() !== $this->getUser()) {
            return new JsonResponse(null, JsonResponse::HTTP_BAD_REQUEST);
        }
        $em->remove($comment);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_OK);
    }

    #[Route('/edit/{id}', name: 'app_edit_comment', methods: ['POST'])]
    public function edit(EntityManagerInterface $em, Request $request, Comment $comment): JsonResponse
    {
        if ($comment->getUser() !== $this->getUser()) {
            return new JsonResponse(null, JsonResponse::HTTP_BAD_REQUEST);
        }
        $data = json_decode($request->getContent(), true);
        $content = $data['content'];
        if (!isset($content)) {
            return new JsonResponse(null, JsonResponse::HTTP_BAD_REQUEST);
        }
        try {
            $comment->setContent($content);
            $em->persist($comment);
            $em->flush();
            $serializer = new Serializer([new ObjectNormalizer()]);
            $jsonComment = $serializer->normalize($comment, 'json', ['attributes' => ['id', 'content', 'createdAt', 'user' => ['id', 'email', 'imageUrl', 'username']]]);
            return new JsonResponse($jsonComment, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(null, JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
