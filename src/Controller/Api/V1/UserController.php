<?php

namespace App\Controller\Api\V1;

use Exception;
use App\Entity\User;
use App\Service\PictureUploader;
use App\Repository\UserRepository;
use App\Repository\EventRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/v1/user', name: 'app_api_v1_user_')]
class UserController extends AbstractController
{
    #[Route('', name: '', methods: 'GET')]
    public function browse(UserRepository $userRepository, Request $request, EventRepository $eventRepository): Response
    {
        $setlistIdParam = $request->query->get("setlistId");

        if ($setlistIdParam != null) {
            $users = $eventRepository->findOneBy(["setlistId" => $setlistIdParam])->getUsers();
        } else {
            $users = $userRepository->findAll();
        }

        return $this->json($users, 200, [], ['groups' => 'user']);
    }

    #[Route('/{id}', name: 'read', methods: 'GET')]
    public function read(?User $user): Response
    {
        if ($user === null) {
            return $this->json('The user doesn\'t exist', 404);
        }

        return $this->json($user, 200, [], ['groups' => 'user']);
    }

    #[Route('', name: 'add', methods: 'POST')]
    public function add(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasherInterface): Response
    {
        $jsonContent = $request->getContent();
        $newUser = $serializer->deserialize($jsonContent, User::class, 'json');

        $errors = $validator->validate(value: $newUser, groups: "registration");
        if (count($errors) > 0) {
            return $this->json($errors, 422);
        }

        $newUser->setPassword($userPasswordHasherInterface->hashPassword($newUser, $newUser->getPassword()));
        $newUser->setRoles(['ROLE_USER']);

        try {
            $userRepository->add($newUser);
        } catch (Exception $e) {
            if ($e->getCode() === 1062) {
                return $this->json('The email is already used', 409);
            }
        }

        return $this->json($newUser, 201, [], ['groups' => 'user']);
    }

    #[Route('/{id<\d+>}', name: 'edit', methods: "PATCH")]
    public function edit(?User $user, Request $request, SerializerInterface $serializer, ValidatorInterface $validator, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasherInterface): Response
    {
        $this->denyAccessUnlessGranted('edit', $user);

        if ($user === null) {
            return $this->json('The user doesn\'t exist', 404);
        }

        $jsonContent = $request->getContent();
        $content = json_decode($jsonContent, true);

        $updatedUser = $serializer->deserialize($jsonContent, User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);

        if (isset($content['newPassword']) && isset($content['oldPassword'])) {
            if (!$userPasswordHasherInterface->isPasswordValid($user, $content['oldPassword'])) {
                return $this->json('Password is not correct', 401);
            }
            $updatedUser->setPassword($content['newPassword']);
        }

        $errors = $validator->validate(value: $updatedUser, groups: "edit");
        if (count($errors) > 0) {
            return $this->json($errors, 422);
        }

        $updatedUser->setPassword($userPasswordHasherInterface->hashPassword($updatedUser, $updatedUser->getPassword()));
        $updatedUser->setUpdatedAt(new \DateTime());

        try {
            $userRepository->add($updatedUser);
        } catch (Exception $e) {
            if ($e->getCode() === 1062) {
                return $this->json('The email is already used', 409);
            }
        }

        return $this->json($updatedUser, 200, [], ['groups' => 'user']);
    }

    #[Route('/avatar/{id<\d+>}', name: 'avatar_add', methods: 'POST')]
    public function addAvatar(?User $user, Filesystem $filesystem, Request $request, ValidatorInterface $validator, PictureUploader $pictureUploader, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('avatar', $user);

        if ($user === null) {
            return $this->json('The user doesn\'t exist', 404);
        }

        $userAvatar = $user->getAvatar();

        if ($userAvatar != NULL) {
            $targetDirectory = $_ENV['AVATAR_PICTURE'];
            $path = $targetDirectory . '/' . $userAvatar;
            $filesystem->remove($path);
        }

        $uploadedFile = $request->files->get('avatar');

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

        $newFileName = $pictureUploader->upload($uploadedFile, $_ENV['AVATAR_PICTURE']);

        $user->setAvatar($newFileName);
        $user->setUpdatedAt(new \DateTime());
        $userRepository->add($user);

        return $this->json($user, 200, [], ['groups' => 'user']);
    }

    #[Route('/avatar/{id<\d+>}', name: 'avatar_delete', methods: 'DELETE')]
    public function deleteAvatar(?User $user, Filesystem $filesystem, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('avatar', $user);

        if ($user === null) {
            return $this->json('The user doesn\'t exist', 404);
        }

        $userAvatar = $user->getAvatar();

        if ($userAvatar != NULL) {
            $filesystem->remove($userAvatar);

            $user->setAvatar(null);
            $user->setUpdatedAt(new \DateTime());

            $userRepository->add($user);

            return $this->json("Avatar deleted", 204);
        }

        return $this->json('No avatar found for this user', 404);
    }
}
