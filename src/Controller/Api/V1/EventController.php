<?php

namespace App\Controller\Api\V1;

use App\Entity\Event;
use App\Repository\BandRepository;
use App\Service\FanartApiGetDatas;
use App\Repository\EventRepository;
use App\Service\SetlistApiGetDatas;
use App\Repository\CountryRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/v1/event', name: 'api_v1_event_')]
class EventController extends AbstractController
{
    #[Route('', name: '', methods: 'GET')]
    // Get all events for an user or with a search (depends on what query parameter is entered)
    public function browse(SetlistApiGetDatas $setlistApiGetDatas, EventRepository $eventRepository, FanartApiGetDatas $fanartApiGetDatas, Request $request, CountryRepository $countryRepository, BandRepository $bandRepository): Response
    {
        $queryParams = $request->query->all();

        // if user query parameter exists, find events for an user 
        if (array_key_exists("user", $queryParams)) {
            if (!array_key_exists("order", $queryParams)) {
                return $this->json("Missing order query parameter", 404);
            }
            $events = $eventRepository->findByUser($queryParams["user"], $queryParams["order"]);
        } else {
            if (array_key_exists("countryId", $queryParams)) {
                $queryParams["countryCode"] = $countryRepository->find($queryParams["countryId"])?->getCountryCode();
                unset($queryParams["countryId"]);
            }

            if (array_key_exists("artistId", $queryParams)) {
                $queryParams["artistMbid"] = $bandRepository->find($queryParams["artistId"])->getMusicbrainzId();
                unset($queryParams["artistId"]);
            }

            foreach ($queryParams as $queryParam => $value) {
                if (!$value) {
                    unset($queryParams[$queryParam]);
                }
            }

            // get events
            $events = $setlistApiGetDatas->getApiSetlistSearch($queryParams);
            // get band images
            $events["bandImages"] = $fanartApiGetDatas->getApiFanartImages($queryParams["artistMbid"]);
        }

        return $this->json($events, 200, [], ['groups' => 'event_browse']);
    }

    #[Route('/{setlistId}', name: 'read', methods: 'GET')]
    //Get one event
    public function read(SetlistApiGetDatas $setlistApiGetDatas, string $setlistId, FanartApiGetDatas $fanartApiGetDatas): Response
    {
        $setlistDatas = $setlistApiGetDatas->getApiSetlistEvent($setlistId);
        if ($setlistDatas === null) {
            return $this->json('The event doesn\'t exist', 404);
        }
        $bandImages = $fanartApiGetDatas->getApiFanartImages($setlistDatas["artist"]["mbid"]);

        // get event
        $event["setlist"] = $setlistDatas;
        // get band images
        $event["bandImages"] = $bandImages;

        return $this->json($event, 200, [], ['groups' => 'event_browse']);
    }

    #[Route('/{setlistId}', name: 'add', methods: 'POST')]
    // Add an user to an event. If the event is not in database, it will be save in.
    public function add($setlistId, EventRepository $eventRepository, SetlistApiGetDatas $setlistApiGetDatas, CountryRepository $countryRepository, BandRepository $bandRepository)
    {
        // Search event in database
        $event = $eventRepository->findOneBy(['setlistId' => $setlistId]);

        $user = $this->getUser();

        // if event isn't in the database
        if ($event === null) {
            $setlistEvent = $setlistApiGetDatas->getApiSetlistEvent($setlistId);
            if ($setlistEvent ===  null) {
                return $this->json('The event doesn\'t exist', 404);
            }
            $event = new Event();
            $event->setSetlistId($setlistEvent['id']);
            $event->setVenue($setlistEvent['venue']['name']);
            $event->setCity($setlistEvent['venue']['city']['name']);
            $event->setDate(new \DateTime($setlistEvent['eventDate']));
            $event->setBand($bandRepository->findOneBy(['name' => $setlistEvent['artist']['name']]));
            $event->setCountry($countryRepository->findOneBy(['countryCode' => $setlistEvent['venue']['city']['country']['code']]));
            $event->addUser($user);
            $eventRepository->add($event);

            return $this->json($event, 201, [], ['groups' => 'event_browse']);
        }
        // If event is in the database
        elseif ($event != null) {
            if ($event->getUsers()->contains($user)) {
                return $this->json("User already link to the event", 403);
            }
            $event->addUser($user);
            $eventRepository->add($event);

            return $this->json($event, 201, [], ['groups' => 'event_browse']);
        } else {
            return $this->json("Forbidden", 403);
        }
    }
}
