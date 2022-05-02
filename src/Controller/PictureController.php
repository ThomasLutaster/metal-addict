<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Picture;
use App\Repository\EventRepository;
use App\Repository\PictureRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\PictureUploader;
use Doctrine\ORM\EntityManagerInterface;
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

        if (!isset($queryParams['user']) && !isset($queryParams['setlistId'])) {
            return $this->json("Missing query parameters", 404);
        }

        if (!isset($queryParams['user']) && isset($queryParams['setlistId'])) {
            $event = $eventRepository->findOneBy(['setlistId' => $queryParams['setlistId']]);
            $pictures = $event->getPictures();
        }
        if (isset($queryParams['user']) && isset($queryParams['setlistId'])) {
            $user = $userRepository->find($queryParams['user']);
            $event = $eventRepository->findOneBy(['setlistId' => $queryParams['setlistId']]);
            $pictures = $pictureRepository->findBy(["user" => $user, "event" => $event]);
        }
        if (isset($queryParams['user']) && !isset($queryParams['setlistId'])) {
            $user = $userRepository->find($queryParams['user']);
            $pictures = $user->getPictures();
        }

        return $this->json($pictures, 200, [], ["groups" => "picture_browse"]);
    }

    #[Route('/{id<\d+>}/{setlistId}', name: 'add', methods: "POST")]
    public function index(User $user, Event $event, Request $request, ValidatorInterface $validator, EntityManagerInterface $em, PictureUploader $pictureUploader): Response
    {
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

        $em->persist($picture);
        $em->flush();

        return $this->json($picture->getId(), 201);
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: "DELETE")]
    public function delete(?Picture $picture, Filesystem $filesystem, EntityManagerInterface $em): Response
    {
        if ($picture === null) {
            return $this->json('The picture doesn\'t exist', 404);
        }

        $path = $picture->getPath();

        $filesystem->remove($path);

        $em->remove($picture);
        $em->flush();

        return $this->json(204);
    }
}
