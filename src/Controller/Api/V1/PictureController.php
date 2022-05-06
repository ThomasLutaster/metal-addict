<?php

namespace App\Controller\Api\V1;

use App\Entity\Event;
use App\Entity\Picture;
use App\Repository\EventRepository;
use App\Repository\PictureRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\PictureUploader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;

#[Route('/api/v1/picture', name: 'app_api_v1_picture_')]
class PictureController extends AbstractController
{
    #[Route('', name: '', methods: "GET")]
    public function browse(Request $request, EventRepository $eventRepository, UserRepository $userRepository, ReviewRepository $reviewRepository, PictureRepository $pictureRepository): Response
    {
        $queryParams = $request->query->all();

        if (!isset($queryParams['user']) && !isset($queryParams['setlistId']) && !isset($queryParams['review'])) {
            return $this->json("Missing query parameters", 404);
        }

        if (isset($queryParams['user']) && isset($queryParams['setlistId']) && !isset($queryParams['review'])) {
            $pictures = $pictureRepository->findByUserAndEvent($queryParams['user'], $queryParams['setlistId'], $queryParams['order']);
        }

        if (!isset($queryParams['user']) && isset($queryParams['setlistId']) && !isset($queryParams['review'])) {
            $pictures = $pictureRepository->findByEvent($queryParams['setlistId'], $queryParams['order']);
        }

        if (isset($queryParams['user']) && !isset($queryParams['setlistId']) && !isset($queryParams['review'])) {
            $pictures = $pictureRepository->findByUser($queryParams['user'], $queryParams['order']);
        }

        if (!isset($queryParams['user']) && !isset($queryParams['setlistId']) && isset($queryParams['review'])) {
            $review = $reviewRepository->find($queryParams['review']);
            $pictures = $pictureRepository->findByUserAndEvent($review->getUser()->getId(), $review->getEvent()->getSetlistId(), $queryParams['order']);
        }

        return $this->json($pictures, 200, [], ["groups" => "picture_browse"]);
    }

    #[Route('/{setlistId}', name: 'add', methods: "POST")]
    public function index(?Event $event, Request $request, ValidatorInterface $validator, PictureUploader $pictureUploader, PictureRepository $pictureRepository): Response
    {
        $user = $this->getUser();

        if (!$user->getEvents()->contains($event)) {
            return $this->json('The user is not linked with the event', 403);
        }

        $uploadedFile = $request->files->get('image');

        if ($uploadedFile === null) {
            return $this->json("No file found", 422);
        }

        $violations = $validator->validate(
            $uploadedFile,
            [
                new NotBlank(['message' => 'Please select a file to upload']),
                new Image(['maxSize' => '5M',])
            ]
        );
        if ($violations->count() > 0) {
            return $this->json($violations, 400);
        }

        $path = $pictureUploader->upload($uploadedFile, $_ENV['EVENT_PICTURE']);

        $picture = new Picture();
        $picture->setPath($path);
        $picture->setEvent($event);
        $picture->setUser($user);

        $pictureRepository->add($picture);

        return $this->json($picture, 201, [], ["groups" => "picture_browse"]);
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: "DELETE")]
    public function delete(?Picture $picture, Filesystem $filesystem, PictureRepository $pictureRepository): Response
    {
        $this->denyAccessUnlessGranted('delete', $picture);

        $path = $picture->getPath();

        $filesystem->remove($path);

        $pictureRepository->remove($picture);

        return $this->json(204);
    }
}
