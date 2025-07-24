<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Dish;
use App\Entity\ExtrasGroup;
use App\Entity\User;
use App\Form\DataTransformer\JsonExtraToExtraEntityTransformer;
use App\Form\DataTransformer\JsonGroupToGroupEntityTransformer;
use App\Utility\Utility;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CompanyPageController extends AbstractController
{
    #[Route('/company/{id}/{zip?}', name: 'company_page')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $companyId = $request->get('id');
        $zip = $request->query->get('zip');
        $matches = [];
        $personalView = false;
        $isCompanyAccount = false;

        $street = '';
        $sn = '';
        $city = '';

        $session = $request->getSession();
        
    
        preg_match('/\d+/', $companyId, $matches);

        if (count($matches) !== 1 || $matches[0] !== $companyId) {
            return $this->render('bundles/TwigBundle/Exception/error404.html.twig', []);
        }
        $companyId = (int) $companyId;

        $company = $em->getRepository(Company::class)->find($companyId);

        if (!Utility::isValidCompany($company)) {
            return $this->render('bundles/TwigBundle/Exception/error404.html.twig', []);
        }

        $user = $this->getUser();
        if (Utility::isValidUser($user)) {
            $userCompany = $user->getCompany();
            if (Utility::isValidCompany($userCompany)) {
                if ($company === $userCompany) {
                    $personalView = true;
                }
            }
            $userZip = $user->getZipcode();
            if ($zip !== $userZip) {
                if (!$zip || strlen(strval($zip)) !== 5) {
                    $zip = $userZip;
                    $street = $user->getStreet();
                    $sn = $user->getSn();
                    $city = $user->getCity();
                }
            }
            $isCompanyAccount = Utility::isCompanyAccount($user);
        }

        $session->set('currentVisitedCompany', $company);

        $dishes = $em->getRepository(Dish::class)->findBy(['company' => $company, 'deleted' => false]);

        /**
         * We want to split the dishes to it's different available phases first. E.g.: Appetizers, main courses, etc...
         * The dishes are divided to the different types afterwards.
         * 
         * When done it should look like:
         * dishes = [
         *  'appetizers' =>
         *      [
         *          'breads' => [
         *              'Bruschetta',
         *              ...
         *          ]
         *  ],
         *  'main courses' =>
         *  [
         *      'breads' => [
         *          ...
         *      ]
         *  ],
         *  ...
         * ]
         */

        $sortedDishes = [];

        foreach ($dishes as $dish) {
            $category = $dish->getCategory()->value;
            $categoryOrder = $dish->getCategory()->getOrder();
            $type = $dish->getType()->value;
            if (!isset($sortedDishes[$categoryOrder])) {
                $sortedDishes[$categoryOrder] = [];
                $sortedDishes[$categoryOrder]['category'] = $category;
            }
            if (!isset($sortedDishes[$categoryOrder][$type])) {
                $sortedDishes[$categoryOrder][$type] = [];
            }
            
            $sortedDishes[$categoryOrder][$type][] = $dish;
        }

        ksort($sortedDishes);

        return $this->render('company_page/index.html.twig', [
            'personalView' => $personalView,
            'company' => $company,
            'sortedDishes' => $sortedDishes,
            'isCompanyAccount' => $isCompanyAccount,
            'user' => $user,
            'zip' => $zip,
            'street' => $street,
            'sn' => $sn,
            'city' => $city,
            'inDeliveryRange' => Utility::isInDeliveryRange($company, $user, $zip),
        ]);
    }

    #[Route('_add_dish_to_cart', name: '_addDishToCart')]
    public function addDish(Request $request, EntityManagerInterface $em): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $dishId = $request->request->get('dish');
        $session = $request->getSession();

        $user = $this->getUser();

        if (!Utility::isValidUser($user)) {
            return $this->render('company_page/_add_dish.stream.html.twig', [
                'status' => 500,
                'message' => 'You need to be logged in to perform this action.'
            ]);
        }

        if (!$dishId || $dishId < 1) {
            return $this->render('company_page/_add_dish.stream.html.twig', [
                'status' => 400,
            ]);
        }

        $company = $session->get('currentVisitedCompany');

        if (!Utility::isValidCompany($company)) {
            return $this->render('company_page/_add_dish.stream.html.twig', [
                'status' => 400,
            ]);
        }

        $dish = $em->getRepository(Dish::class)->findOneBy(['id' => $dishId, 'company' => $company]);

        if (!$dish || !$dish instanceof Dish) {
            return $this->render('company_page/_add_dish.stream.html.twig', [
                'status' => 404,
            ]);
        }

        $sizes = array_filter($dish->getSizes(), fn($value) => intval($value) > 0);

        return $this->render('company_page/_add_dish.stream.html.twig', [
            'dish' => $dish,
            'extras' => $dish->getExtras()->toArray(),
            'groups' => $dish->getExtrasGroups()->toArray(),
            'sizes' => count($sizes) > 0 ? $sizes : '{}',
            'status' => 200,
        ]);
    }

    #[Route('_remove_dishes_from_cart', name: '_removeDishesFromCart')]
    public function removeDishes(Request $request, EntityManagerInterface $em): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $dishesJSON = $request->request->get('dishes');
        $dishId = $request->request->get('dish');
        $session = $request->getSession();

        $user = $this->getUser();

        if (!Utility::isValidUser($user)) {
            return $this->render('company_page/_add_dish.stream.html.twig', [
                'status' => 500,
                'message' => 'You need to be logged in to perform this action.'
            ]);
        }

        if (!$dishId || $dishId < 1) {
            return $this->render('company_page/_add_dish.stream.html.twig', [
                'status' => 400,
            ]);
        }

        $company = $session->get('currentVisitedCompany');

        if (!Utility::isValidCompany($company)) {
            return $this->render('company_page/_add_dish.stream.html.twig', [
                'status' => 400,
            ]);
        }

        $dish = $em->getRepository(Dish::class)->findOneBy(['id' => $dishId, 'company' => $company]);

        if (!$dish || !$dish instanceof Dish) {
            return $this->render('company_page/_add_dish.stream.html.twig', [
                'status' => 404,
            ]);
        }

        if (!$dishesJSON || $dishesJSON == '') {
            return $this->render('company_page/_remove_dishes.stream.html.twig', [
                'status' => 400,
            ]);
        }

        try {
            $dishes = json_decode($dishesJSON, true);
        } catch (\Throwable $th) {
            return $this->render('company_page/_remove_dishes.stream.html.twig', [
                'status' => 400,
            ]);
        }

        return $this->render('company_page/_remove_dishes.stream.html.twig', [
            'dishes' => $dishes,
            'dishObj' => $dish,
            'status' => 200,
        ]);
    }

    #[Route('_change_address_form', name: 'changeAddressForm')]
    public function changeAddress(Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $zip = $request->request->get('zip');

        if (!$zip) {
            return $this->render('company_page/_change_address_form.stream.html.twig', [
                'status' => 400,
            ]);
        }

        return $this->render('company_page/_change_address_form.stream.html.twig', [
            'zip' => $zip,
            'status' => 200,
        ]);
    }

    #[Route('_show_ratings', name: 'showRatings')]
    public function showRatings(Request $request, EntityManagerInterface $em): Response
    {
        $company = $request->request->get('company');
        if (!$company || $company !== (string)(int)$company) {
            return $this->render('company_page/_show_ratings.stream.html.twig', [
                'company' => NULL,
                'status' => 400,
                'message' => 'No valid company information has been provided.',
            ]);
        }

        $company = $em->getRepository(Company::class)->find($company);

        if (!Utility::isValidCompany($company)) {
            return $this->render('company_page/_show_ratings.stream.html.twig', [
                'company' => NULL,
                'status' => 404,
                'message' => 'No associating company has been found.',
            ]);
        }
        
        return $this->render('company_page/_show_ratings.stream.html.twig', [
            'company' => $company,
            'status' => 200,
            'message' => '',
        ]);
    }
}
