<?php

namespace App\Controller;

use App\Entity\Follow;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;


#[Route('/api/follow')]
class FollowController extends AbstractController
{
    #[Route('/add/{id}', name: 'app_follow', methods: ['POST'])]
    public function add(EntityManagerInterface $em, User $user): JsonResponse
    {
        $follow = $em->getRepository(Follow::class)->findOneBy(['follower' => $this->getUser(), 'following' => $user]);
        if ($follow) {
            return new JsonResponse(['status' => 'error', 'message' => 'You are already following this user'], 400);
        }
        if ($user === $this->getUser()) {
            return new JsonResponse(['status' => 'error', 'message' => 'You can\'t follow yourself'], 400);
        }
        try {
            $follow = new Follow();
            $follower = $this->getUser();
            $follow->setFollower($follower);
            $follow->setFollowing($user);
            $follow->setCreatedAt(new \DateTimeImmutable());
            $em->persist($follow);
            $em->flush();
            return new JsonResponse(['status' => 'success', 'message' => 'Followed successfully'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/remove/{id}', name: 'app_unfollow', methods: ['POST'])]
    public function remove(EntityManagerInterface $em, User $user): JsonResponse
    {
        try {
            $follower = $this->getUser();
            $follow = $em->getRepository(Follow::class)->findOneBy(['follower' => $follower, 'following' => $user]);
            if (!$follow) {
                return new JsonResponse(['status' => 'error', 'message' => 'You are not following this user'], 400);
            }
            $em->remove($follow);
            $em->flush();
            return new JsonResponse(['status' => 'success', 'message' => 'Unfollowed successfully'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


    #[Route('/followers/{id}', name: 'app_followers', methods: ['GET'])]
    public function getFollowers(EntityManagerInterface $em, User $user): JsonResponse
    {
        try {
            $followers = $em->getRepository(Follow::class)->findBy(['following' => $user]);
            $serializer = new Serializer([new ObjectNormalizer()]);
            $jsonFollowers = $serializer->normalize($followers, 'json', [AbstractNormalizer::ATTRIBUTES => ['follower' => ['id', 'email']]]);


            // $jsonFollowers = $serializer->serialize($followers, 'json');
            return new JsonResponse(['status' => 'success', 'followers' => $jsonFollowers], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/followings/{id}', name: 'app_followings', methods: ['GET'])]
    public function getFollowing(EntityManagerInterface $em, User $user): JsonResponse
    {
        try {
            $followings = $em->getRepository(Follow::class)->findBy([
                'follower' => $user
            ]);
            $serializer = new Serializer([new ObjectNormalizer()]);
            $jsonFollowing = $serializer->normalize($followings, 'json', ['attributes' => ['following' => ['id', 'email']]]);
            return new JsonResponse(['status' => 'success', 'followings' => $jsonFollowing], 200);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $e->getMessage()],
                500
            );
        }
    }
}
