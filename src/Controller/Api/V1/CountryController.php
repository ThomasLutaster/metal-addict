<?php

namespace App\Controller\Api\V1;

use App\Repository\CountryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/country', name: 'api_v1_country')]
class CountryController extends AbstractController
{
    #[Route('', name: '')]
    public function browse(CountryRepository $countryRepository): Response
    {
        $countries = $countryRepository->findAll();

        return $this->json($countries, 200);
    }
}
