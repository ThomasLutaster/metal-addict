<?php

namespace App\Controller\Api\V1;

use App\Entity\Event;
use App\Entity\Review;
use App\Repository\EventRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/review', name: 'api_v1_review_')]
class ReviewController extends AbstractController
{
    #[Route('', name: '', methods: 'GET')]
    public function browse(Request $request, ReviewRepository $reviewRepository, EventRepository $eventRepository, UserRepository $UserRepository): Response
    {
        $queryParams = $request->query->all();
        $reviews = [];

        if (isset($queryParams['user']) && isset($queryParams['setlistId'])) {;
            $reviews = $reviewRepository->findByUserAndEventIds($queryParams['order'], $queryParams['user'], $queryParams['setlistId']);
        }

        if (isset($queryParams['setlistId'])) {
            $reviews = $reviewRepository->findByEvent($queryParams['order'], $queryParams['setlistId']);
        }

        if (isset($queryParams['user'])) {
            $reviews = $reviewRepository->findByUser($queryParams['order'], $queryParams['user']);
        }

        if (isset($queryParams['limit']) && isset($queryParams['order'])) {
            $reviews = $reviewRepository->findByLatest($queryParams['order'], $queryParams['limit']);
        }

        return $this->json($reviews, 200, [], ['groups' => 'review']);
    }

    #[Route('/{id}', name: 'read', methods: 'GET')]
    public function read(?Review $review): Response
    {
        if ($review === null) {
            return $this->json('The review doesn\'t exist', 404);
        }

        return $this->json($review, 200, [], ['groups' => 'review']);
    }

    #[Route('/{setlistId}', name: 'add', methods: 'POST')]
    public function add(?Event $event, ReviewRepository $reviewRepository, Request $request, SerializerInterface $serializer, ValidatorInterface $validatorInterface, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $review = $reviewRepository->findByUserAndEvent($user, $event);
        if ($review !== null) {
            return $this->json('The user has already writen a review for this event', 409);
        }
        if (!$user->getEvents()->contains($event)) {
            return $this->json('The user has not participated at the event', 409);
        }

        $jsonContent = $request->getContent();
        $review = $serializer->deserialize($jsonContent, Review::class, 'json');

        $errors = $validatorInterface->validate($review);

        if (count($errors) > 0) {
            $errorsString = (string) $errors;

            return $this->json($errorsString, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $review->setUser($user);
        $review->setEvent($event);

        $em->persist($review);
        $em->flush();

        return $this->json($review, 201, [], ['groups' => 'review']);
    }
}
