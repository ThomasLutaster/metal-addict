<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FanartApiGetDatas
{
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $client,
    ) {
        $this->apiKey = $_ENV['FANARTAPIKEY'];
    }

    /**
     *Get band images with a request to fanart api
     **/
    public function getApiFanartImages(string $mbId): array
    {
        $response = $this->client->request(
            'GET',
            'http://webservice.fanart.tv/v3/music/' . $mbId,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'api_key' => $this->apiKey,
                ],
            ]
        );

        if ($response->getStatusCode() === 404) {
            return [
                'name' => '',
                'mbid_id' => '',
                'albums' => [],
                'artistthumb' => [],
                'hdmusiclogo' => [],
                'musiclogo' => [],
                'musicbanner' => [],
                'artistbackground' => [],
            ];
        }

        return $response->toArray();
    }
}
