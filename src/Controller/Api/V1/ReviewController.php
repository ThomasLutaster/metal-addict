<?php

namespace App\Controller\Api\V1;

use App\Entity\Event;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/v1/review', name: 'api_v1_review_')]
class ReviewController extends AbstractController
{
    #[Route('', name: '', methods: 'GET')]
    // Get pictures for an user, an event or all pictures with possibility to indicate a limit number (depends on query parameters)
    public function browse(Request $request, ReviewRepository $reviewRepository): Response
    {
        $queryParams = $request->query->all();
        $reviews = [];

        if (!array_key_exists("order", $queryParams)) {
            return $this->json("Missing order query parameter", 404);
        }

        if (isset($queryParams['user']) && isset($queryParams['setlistId'])) {
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
    // Add a review for an event linked to the connected user.
    public function add(?Event $event, ReviewRepository $reviewRepository, Request $request, SerializerInterface $serializer, ValidatorInterface $validatorInterface): Response
    {
        $user = $this->getUser();

        $review = $reviewRepository->findByUserAndEvent($user, $event);
        if (count($review) > 0) {
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

            return $this->json($errorsString, 422);
        }

        $review->setUser($user);
        $review->setEvent($event);

        $reviewRepository->add($review);

        return $this->json($review, 201, [], ['groups' => 'review']);
    }

    #[Route('/{id<\d+>}', name: 'edit', methods: 'PATCH')]
    public function edit(?Review $review, Request $request, SerializerInterface $serializer, ValidatorInterface $validatorInterface, ReviewRepository $reviewRepository): Response
    {
        $this->denyAccessUnlessGranted('edit', $review);

        if ($review === null) {
            return $this->json('The review doesn\'t exist', 404);
        }

        $jsonContent = $request->getContent();

        $review = $serializer->deserialize($jsonContent, Review::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $review]);

        $errors = $validatorInterface->validate($review);

        if (count($errors) > 0) {
            $errorsString = (string) $errors;

            return $this->json($errorsString, 422);
        }

        $review->setUpdatedAt(new \DateTime);

        $reviewRepository->add($review);

        return $this->json($review, 201, [], ['groups' => 'review']);
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: 'DELETE')]
    public function delete(?Review $review, ReviewRepository $reviewRepository): Response
    {
        $this->denyAccessUnlessGranted('delete', $review);

        $reviewRepository->remove($review);

        return $this->json("The review is deleted", 204);
    }
}
