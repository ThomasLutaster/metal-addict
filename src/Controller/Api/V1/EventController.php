<?php

namespace App\Controller\Api\V1;

use App\Entity\Event;
use App\Repository\BandRepository;
use App\Service\FanartApiGetDatas;
use App\Repository\EventRepository;
use App\Service\SetlistApiGetDatas;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/v1/event', name: 'api_v1_event_')]
class EventController extends AbstractController
{
    #[Route('', name: '', methods: 'GET')]
    public function browse(SetlistApiGetDatas $setlistApiGetDatas, EventRepository $eventRepository, FanartApiGetDatas $fanartApiGetDatas, Request $request, CountryRepository $countryRepository): Response
    {
        $queryParams = $request->query->all();

        if (array_key_exists("user", $queryParams)) {
            $events = $eventRepository->findByUser($queryParams["user"], $queryParams["order"]);
        } else {
            if (array_key_exists("countryId", $queryParams)) {
                $queryParams["countryCode"] = $countryRepository->find($queryParams["countryId"])->getCountryCode();
                unset($queryParams["countryId"]);
            }
            $events["setlist"] = $setlistApiGetDatas->getApiSetlistSearch($queryParams);
            $events["bandImages"] = $fanartApiGetDatas->getApiFanartImages($queryParams["artistMbid"]);
        }

        return $this->json($events, 200);
    }

    #[Route('/{setlistId}', name: 'read', methods: 'GET')]
    public function read(SetlistApiGetDatas $setlistApiGetDatas, string $setlistId, FanartApiGetDatas $fanartApiGetDatas): Response
    {
        $setlistDatas = $setlistApiGetDatas->getApiSetlistEvent($setlistId);
        $bandImages = $fanartApiGetDatas->getApiFanartImages($setlistDatas["artist"]["mbid"]);

        $event["setlist"] = $setlistDatas;
        $event["bandImages"] = $bandImages;

        return $this->json($event, 200);
    }

    #[Route('/{setlistId}', name: 'add', methods: 'POST')]
    public function add($setlistId, EventRepository $eventRepository, SetlistApiGetDatas $setlistApiGetDatas, CountryRepository $countryRepository, BandRepository $bandRepository, EntityManagerInterface $em)
    {
        $event = $eventRepository->findOneBy(['setlistId' => $setlistId]);
        $user = $this->getUser();

        if ($event === null) {
            $setlistEvent = $setlistApiGetDatas->getApiSetlistEvent($setlistId);
            $event = new Event();
            $event->setSetlistId($setlistEvent['id']);
            $event->setVenue($setlistEvent['venue']['name']);
            $event->setCity($setlistEvent['venue']['city']['name']);
            $event->setDate(new \DateTime($setlistEvent['eventDate']));
            $event->setBand($bandRepository->findOneBy(['name' => $setlistEvent['artist']['name']]));
            $event->setCountry($countryRepository->findOneBy(['countryCode' => $setlistEvent['venue']['city']['country']['code']]));
            $event->addUser($user);
            $em->persist($event);
            $em->flush();

            return $this->json($event, 201);
        } elseif ($event != null) {
            if ($event->getUsers()->contains($user)) {
                return $this->json("User already link to the event", 403);
            }
            $event->addUser($user);
            $em->flush();

            return $this->json($event, 201, [], ['groups' => 'event_browse']);
        } else {
            return $this->json("Forbidden", 403);
        }
    }
}
