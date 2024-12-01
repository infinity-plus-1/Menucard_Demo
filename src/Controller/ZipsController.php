<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Services\Geo\GeoRepository;

class ZipsController extends AbstractController
{
    #[Route('/zips', name: 'app_zips')]
    public function index(): Response
    {
        $geo = new GeoRepository();
        $geo->getByPostalCode($_GET["zip"]);
        $cityMatches = json_encode($geo -> exportZipCity(), JSON_UNESCAPED_UNICODE);
        return $this->render('zips/index.html.twig', [
            'cities' => $cityMatches
        ]);
    }
}
