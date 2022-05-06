<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;



class SetlistApiGetDatas
{
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $client,
    ) {
        $this->apiKey = $_ENV['SETLISTAPIKEY'];
    }

    /**
     *Get a country list with a fetch to setlist api
     **/
    public function getApiSetlistCountries()
    {
        $response = $this->client->request(
            'GET',
            'https://api.setlist.fm/rest/1.0/search/countries',
            [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Accept-Language' => 'fr',
                ],
            ]
        );

        return $response->toArray();
    }

    /**
     *Get an events list with a fetch to setlist api
     **/
    public function getApiSetlistSearch(array $params)
    {
        $response = $this->client->request(
            'GET',
            'https://api.setlist.fm/rest/1.0/search/setlists',
            [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Accept-Language' => 'fr',
                ],
                'query' => $params,
            ]
        );

        if ($response->getStatusCode() === 404) {
            return [
                "type" => "setlists",
                "itemsPerPage" => 20,
                "page" => 1,
                "total" => 0,
                "setlist" => [],
            ];
        }

        return $response->toArray();
    }

    /**
     *Get an event with a fetch to setlist api
     **/
    public function getApiSetlistEvent(string $setlistId)
    {
        $response = $this->client->request(
            'GET',
            'https://api.setlist.fm/rest/1.0/setlist/' . $setlistId,
            [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Accept-Language' => 'fr',
                ],
            ]
        );

        return $response->toArray();
    }
}
