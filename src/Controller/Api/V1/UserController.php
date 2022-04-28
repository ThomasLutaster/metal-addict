<?php

namespace App\Controller\Api\V1;

use Exception;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\EventRepository;
use App\Service\AvatarUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
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

        return $this->json($users, 200);
    }

    #[Route('/{id}', name: 'read', methods: 'GET')]
    public function read(User $user): Response
    {
        if ($user === null) {
            return $this->json('The user doesn\'t exist', 404);
        }

        return $this->json($user, 200);
    }

    #[Route('', name: 'add', methods: 'POST')]
    public function add(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasherInterface): Response
    {
        $jsonContent = $request->getContent();
        $newUser = $serializer->deserialize($jsonContent, User::class, 'json');

        $errors = $validator->validate(value: $newUser, groups: "registration");
        if (count($errors) > 0) {
            return $this->json($errors, 422);
        }

        $newUser->setPassword($userPasswordHasherInterface->hashPassword($newUser, $newUser->getPassword()));
        $newUser->setRoles(['ROLE_USER']);

        $em->persist($newUser);

        try {
            $em->flush();
        } catch (Exception $e) {
            if ($e->getCode() === 1062) {
                return $this->json('The email is already used', 409);
            }
        }

        return $this->json($newUser, 201);
    }

    #[Route('/{id}', name: 'edit', methods: "PATCH")]
    public function edit(?User $user, Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasherInterface): Response
    {
        if ($user === null) {
            return $this->json('The user doesn\'t exist', 404);
        }

        $jsonContent = $request->getContent();
        $content = json_decode($jsonContent, true);

        $newUser = $serializer->deserialize($jsonContent, User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);

        if (isset($content['newPassword']) && isset($content['oldPassword'])) {
            if (!$userPasswordHasherInterface->isPasswordValid($user, $content['oldPassword'])) {
                return $this->json('Password is not correct', 401);
            }
            $newUser->setPassword($content['newPassword']);
        }

        $errors = $validator->validate(value: $newUser, groups: "edit");
        if (count($errors) > 0) {
            return $this->json($errors, 422);
        }

        $newUser->setPassword($userPasswordHasherInterface->hashPassword($newUser, $newUser->getPassword()));
        $newUser->setUpdatedAt(new \DateTime());

        try {
            $em->flush();
        } catch (Exception $e) {
            if ($e->getCode() === 1062) {
                return $this->json('The email is already used', 409);
            }
        }

        return $this->json($newUser, 200);
    }

    #[Route('/avatar/{id}', name: 'avatar_add', methods: 'POST')]
    public function addAvatar(User $user, Filesystem $filesystem, Request $request, ValidatorInterface $validator, AvatarUploader $avatarUploader, EntityManagerInterface $em): Response
    {
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
        $errors = $validator->validate(value: $uploadedFile, groups: "avatar");
        if (count($errors) > 0) {
            return $this->json($errors, 422);
        }

        $newFileName = $avatarUploader->upload($uploadedFile);

        $user->setAvatar($newFileName);
        $user->setUpdatedAt(new \DateTime());
        $em->flush();

        return $this->json($user, 200);
    }

    #[Route('/avatar/{id}', name: 'avatar_delete', methods: 'DELETE')]
    public function deleteAvatar(User $user, Filesystem $filesystem, EntityManagerInterface $em): Response
    {
        if ($user === null) {
            return $this->json('The user doesn\'t exist', 404);
        }

        $userAvatar = $user->getAvatar();

        if ($userAvatar != NULL) {
            $targetDirectory = $_ENV['AVATAR_PICTURE'];
            $path = $targetDirectory . '/' . $userAvatar;
            $filesystem->remove($path);

            $user->setAvatar(null);
            $em->flush();

            return $this->json("Avatar deleted", 204);
        }

        return $this->json('No avatar found for this user', 404);
    }
}
