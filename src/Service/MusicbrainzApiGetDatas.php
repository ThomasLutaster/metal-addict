<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;



class MusicbrainzApiGetDatas
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    /**
     *Get a band list with a fetch to musicbrainz api
     **/
    public function getMusicbrainzBands($offset, $genre = "metal")
    {
        $response = $this->client->request(
            'GET',
            'https://musicbrainz.org/ws/2/artist?query=tag:' . $genre . '&limit=100&offset=' . $offset,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return $response->toArray();
    }
}
