<?php

namespace App\Controller\Api\V1;

use App\Repository\BandRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/v1/band', name: 'api_v1_band_', methods: 'GET')]
class BandController extends AbstractController
{
    #[Route('', name: '')]
    public function browse(BandRepository $bandRepository): Response
    {
        $bands = $bandRepository->findAll();

        return $this->json($bands, 200, [], ['groups' => 'band_browse']);
    }
}
