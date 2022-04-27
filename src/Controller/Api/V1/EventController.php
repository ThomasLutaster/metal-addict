<?php

namespace App\Controller\Api\V1;

use App\Service\SetlistApiGetDatas;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/event', name: 'api_v1_event_')]
class EventController extends AbstractController
{
    #[Route('', name: '', methods: 'GET')]
    public function browse(SetlistApiGetDatas $setlistApiGetDatas): Response
    {
        $params = [];
        $params["artistMbid"] = "a9044915-8be3-4c7e-b11f-9e2d2ea0a91e";
        $params["year"] = "2009";
        $params["countryCode"] = "fr";
        $params["cityName"] = null;

        $events = $setlistApiGetDatas->getApiSetlistSearch($params);

        return $this->json($events, 200);
    }

    #[Route('/{setlistId}', name: 'read', methods: 'GET')]
    public function read(SetlistApiGetDatas $setlistApiGetDatas, string $setlistId): Response
    {
        $event = $setlistApiGetDatas->getApiSetlistEvent($setlistId);

        return $this->json($event, 200);
    }
}
