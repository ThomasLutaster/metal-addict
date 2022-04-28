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
     *Get an images list with a fetch to fanart api
     **/
    public function getApiFanartImages(string $mbId)
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

        return $response->toArray();
    }
}
