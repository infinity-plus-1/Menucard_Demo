<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\DeliveryZip;
use App\Entity\Order;
use App\Enum\CuisinesEnum;
use App\Utility\Paths;
use App\Utility\Utility;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

class HomeController extends AbstractController
{
    const MAX_SUGGESTIONS = 5;
    const MAX_PAGE_RESULTS = 10;
    const MAX_RATING = 5;

    #[Route('/listSuggestedRestaurants/{zip?}', name: 'listSuggestedRestaurants')]
    public function listSuggestedRestaurants(Request $request, EntityManagerInterface $em): Response
    {
        $suggestedRestaurants = [];
        $allRestaurants = [];
        $status = 200;
        $message = '';
        $zip = $request->get('zip');
        if ($zip) {
            try {
                $allRestaurantsDzEntities = $em->getRepository(DeliveryZip::class)->getCompaniesByDeliveryZip($zip, [], true);
                foreach ($allRestaurantsDzEntities as $entity) {
                    if ($entity instanceof DeliveryZip) {
                        $company = $entity->getCompany();
                        if (Utility::isValidCompany($company)) {
                            $allRestaurants[] = $company;
                        }
                    }
                }
                usort($allRestaurants, fn($a, $b) => $a->getAverageRating() < $b->getAverageRating());
                $suggestedRestaurants = array_slice($allRestaurants, 0, self::MAX_SUGGESTIONS);
            } catch (\Throwable $th) {
                $status = 500;
            }
        } else {
            $status = 400;
            $message = 'We\'ve received no zip code. Please reload and try again.';
        }
        
        return $this->render('home/_restaurant_suggestions.html.twig', [
            'suggestedRestaurants' => $suggestedRestaurants,
            'status' => $status,
            'message' => $message,
            'zip' => $zip,
        ]);
    }

    #[Route('listRestaurants/{zip?}', name: 'listRestaurants')]
    public function listRestaurants(Request $request, EntityManagerInterface $em) : Response
    {
        $pagerfanta = NULL;
        $status = 200;
        $message = '';
        $zip = $request->get('zip');

        if ($zip) {
            try {
                
                $pagerfanta = new Pagerfanta(
                    new QueryAdapter(
                        $em->getRepository(DeliveryZip::class)->getCompaniesByDeliveryZip($zip, $request->query->all())
                    )
                );
                $pagerfanta->setMaxPerPage(1);
                $pagerfanta->setCurrentPage($request->get('page') ?? 1);
            } catch (\Throwable $th) {
                $status = 500;
            }
        } else {
            $status = 400;
            $message = 'We\'ve received no zip code. Please reload and try again.';
        }

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        $proximity = $request->get('proximity') ?? 0;

        if ($proximity !== (string)(int)$proximity) {
            $proximity = 0;
        }

        return $this->render('home/_restaurants_list.stream.html.twig', [
            'pager' => $pagerfanta,
            'status' => $status,
            'message' => $message,
            'zip' => $zip,
            'proximity' => $proximity,
        ]);
    }

    #[Route('restaurants/{zip?}', name: 'restaurants')]
    public function renderRestaurantSearch(Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        $cuisines = array_merge(['All'], array_map(fn($case) => $case->value, CuisinesEnum::cases()));
        return $this->render('home/_restaurants.stream.html.twig', [
            'maxRating' => self::MAX_RATING,
            'cuisines' => $cuisines,
            'zip' => $request->get('zip') ?? 0,
        ]);
    }

    #[Route('/', name: 'home')]
    public function index(Request $request, Paths $paths, EntityManagerInterface $em): Response
    {
        return $this->render('home/index.html.twig', [
            'rating' => 0.0,
            'companies' => []
        ]);
    }

    public function _renderCarousel(): Response {
        return $this->render('home/_carousel.html.twig', [
            
        ]);
    }

    #[Route('/impressum', name: 'impressum')]
    public function impressum(): Response {
        return $this->render('home/impressum.html.twig', []);
    }
}
