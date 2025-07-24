<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Dish;
use App\Entity\Extra;
use App\Entity\ExtrasGroup;
use App\Entity\User;
use App\Trait\DishTrait;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use App\Form\DataTransformer\JsonExtraToExtraEntityTransformer;
use App\Form\DataTransformer\JsonGroupToGroupEntityTransformer;
use App\Utility\Utility;

#[Route('/dish')]
class DishController extends AbstractController
{
    use DishTrait;
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public string $filter = '';

    public function __construct(private LoggerInterface $logger) {}

    #[Route('/create', name: 'create_product')]
    public function create(): Response
    {
        $user = $this->getUser();

        $status = 200;
        $message = '';

        if (!Utility::isCompanyAccount($user)) {
            $status = 403;
            $message = 'This page is only accessible by commercial accounts.';
        } elseif (!Utility::accountIsSetUp($user)) {
            $status = 403;
            $message = 'Your company has not been set up yet.';
        }

        return $this->render('create_product/index.html.twig', [
            'status' => $status,
            'message' => $message,
        ]);
    }

    #[Route('/edit/{id}', name: 'edit_product')]
    public function edit(EntityManagerInterface $em, Request $request): Response
    {
        $id = $request->attributes->get('id');
        $dish = $this->getDish($id, $em);
        $groups = $this->_getGroups($dish->getId(), $em);
        $extras = $this->_getMultiExtras($dish->getId(), $em);

        $extrasTransformer = new JsonExtraToExtraEntityTransformer($em, $dish->getCompany(), $dish, 1);
        $groupsTransformer = new JsonGroupToGroupEntityTransformer($em, $dish->getCompany(), $dish);
        $extrasJSON = $extrasTransformer->transform($extras);
        $groupsJSON = isset($groups['groups']) ? $groupsTransformer->transform($groups['groups']) : '{}';

        return $this->render('edit_product/index.html.twig', [
            'dish' => $dish,
            'groups' => $groupsJSON !== [] ? $groupsJSON : '{}',
            'extras' => $extrasJSON !== [] ? $extrasJSON : '{}',
        ]);
    }


    #[Route('/dishes', name: 'list_dishes')]
    public function listAllDishes(EntityManagerInterface $em, Request $request): Response
    {
        $dishes = [];
        $user = $this->getUser();

        $status = 200;
        $message = '';

        if (!Utility::isCompanyAccount($user)) {
            $status = 403;
            $message = 'This page is only accessible by commercial accounts.';
        } elseif (!Utility::accountIsSetUp($user)) {
            $status = 403;
            $message = 'Your company has not been set up yet.';
        }

        if ($user instanceof User) {
            $company = $user->getCompany();
            if ($company instanceof Company) {
                $companyId = $company->getId();
                if ($companyId) {
                    $dishes = $em->getRepository(Dish::class)->findBy(['company' => $companyId]);
                }
            }
        }
        dump($dishes);
        return $this->render('dish/list.html.twig', [
            'dishes' => $dishes,
            'status' => $status,
            'message' => $message,
        ]);
    }

    #[Route('/view/{id}', name: 'view_dish')]
    public function viewDish(EntityManagerInterface $em, Request $request): Response
    {
        $id = $request->attributes->get('id');
        $dish = $em->getRepository(Dish::class)->find($id);

        if ($dish instanceof Dish) {
            $dish->setSizes(array_filter($dish->getSizes(), fn($value) => intval($value) > 0));
        }

        return $this->render('dish/view.html.twig', [
            'dish' => $dish,
            'groups' => [],
            'extras' => [],
        ]);
    }

    private function _getMultiExtras(mixed $dishId, EntityManagerInterface $em, ?string $encodedExtras = NULL): array|int
    {
        $dish = NULL;
        $extras = [];

        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return 500;
        }

        $company = $user->getCompany();

        if (!$company || !$company instanceof Company) {
            return 500;
        }

        if ($dishId && $dishId > 0) {
            $dish = $em->getRepository(Dish::class)->find($dishId);
            if (!$dish || !$dish instanceof Dish) {
                return 404;
            }
            $extras = $em->getRepository(Extra::class)->findBy(['company' => $company, 'dish' => $dish, 'selectType' => 1]);
            if (!is_array($extras)) {
                return 500;
            }
        }

        if (!$extras || $extras === []) {
            try {
                $decodedExtras = json_decode($encodedExtras, true);
                if (!is_array($decodedExtras)) {
                    return 500;
                }
                foreach ($decodedExtras as $decodedExtra) {
                    if (!is_array($decodedExtra)) {
                        return 500;
                    }
                    $extraEntity = new Extra();
                    $extraEntity->setName($decodedExtra['name']);
                    $extraEntity->setPrice($decodedExtra['price']);
                }
            } catch (\Throwable $th) {
                return 500;
            }
        }
        return $extras;
    }

    private function _getGroups(mixed $dishId, EntityManagerInterface $em, ?string $encodedGroups = NULL): array|int
    {
        $dish = NULL;
        $groups = [];

        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return 500;
        }

        $company = $user->getCompany();

        if (!$company || !$company instanceof Company) {
            return 500;
        }

        if ($dishId && $dishId > 0) {
            $dish = $em->getRepository(Dish::class)->findOneBy(['id' => $dishId, 'company' => $company]);
            
            if (!$dish || !$dish instanceof Dish) {
                return 404;
            }

            $groups = $em->getRepository(ExtrasGroup::class)->findByReturnAssociativeByName(['company' => $company, 'dish' => $dish]);

            if (!is_array($groups)) {
                return 500;
            }
            foreach ($groups as $group) {
                if (!$group->getExtras()->isInitialized()) {
                    $group->getExtras()->initialize();
                }
            }
        }

        if ($encodedGroups && $encodedGroups !== '') {
            try {
                $decodedGroups = json_decode($encodedGroups, true);
                if (!is_array($decodedGroups)) {
                    return 500;
                }
                $extrasTransformer = new JsonExtraToExtraEntityTransformer($em, $company, $dish, 2);
                foreach ($decodedGroups as $decodedGroup) {
                    if (!is_array($decodedGroup)) {
                        return 500;
                    }
                    if (!isset($decodedGroup['group']) || !isset($decodedGroup['group']['name'])) {
                        return 500;
                    }
                    if (!isset($decodedGroup['extras'])) {
                        return 500;
                    }
                    if (!isset($groups[$decodedGroup['group']['name']])) {
                        $groupEntity = new ExtrasGroup();
                        $groupEntity->setName($decodedGroup['group']['name']);
    
                        $extras = $extrasTransformer->reverseTransform($decodedGroup['extras']);
                        foreach ($extras as $extra) {
                            $groupEntity->addExtra($extra);
                        }
                        $groups[] = $groupEntity;
                    }
                }
            } catch (\Throwable $th) {
                return 500;
            }
        }
        return ['dish' => $dish, 'groups' => $groups];
    }

    #[Route('/addExtra', name: 'addExtra')]
    public function addExtra(Request $request, EntityManagerInterface $em): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $encodedGroups = $request->request->get('groups');
        $dishId = $request->request->get('dish');

        $groups = [];

        if ($encodedGroups) {
            $result = $this->_getGroups($dishId, $em, $encodedGroups);
            if (!is_array($result)) {
                return $this->render('create_product/_extras_add_extra.stream.html.twig', [
                    'status' => is_int($result) ? $result : 500,
                ]);
            }
            $groups = $result;
        } else {
            return $this->render('create_product/_extras_add_extra.stream.html.twig', [
                'status' => 500,
            ]);
        }

        return $this->render('create_product/_extras_add_extra.stream.html.twig', [
            'groups' => $groups,
            'status' => 200,
        ]);
    }

    #[Route('/manageGroups', name: 'manageGroups')]
    public function manageGroups(Request $request, EntityManagerInterface $em): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return $this->render('create_product/_extras_manage_groups.stream.html.twig', [
                'status' => 403,
                'message' => 'You need to be logged in to perform this action.'
            ]);
        }

        $company = $user->getCompany();
        if (!$company || !$company instanceof Company) {
            return $this->render('create_product/_extras_manage_groups.stream.html.twig', [
                'status' => 403,
                'message' => 'No associated company has been found.',
            ]);
        }

        $encodedGroups = $request->request->get('groups');
        $dishId = $request->request->get('dish') ?? 0;

        $groups = [];

        if ($encodedGroups || $dishId) {
            $result = $this->_getGroups($dishId, $em, $encodedGroups);
            if (!is_array($result) || !isset($result['groups']) || !is_array($result['groups'])) {
                return $this->render('create_product/_extras_manage_groups.stream.html.twig', [
                    'status' => is_int($result) ? $result : 500,
                ]);
            }
            $groups = $result['groups'];
            $dish = $result['dish'] ?? NULL;
            return $this->render('create_product/_extras_manage_groups.stream.html.twig', [
                'groups' => $groups,
                'dish' => $dish,
                'status' => 200,
            ]);
        } else {
            return $this->render('create_product/_extras_manage_groups.stream.html.twig', [
                'status' => 500,
                'message' => 'An unknown error occured.',
            ]);
        }
    }

    #[Route('/deleteGroup', name: 'deleteGroup')]
    public function deleteGroup(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $groupId = $request->request->get('group');
        $dish = $request->request->get('dish');

        if (!$groupId || !$dish) {
            return new JsonResponse(['message' => 'Missing parameters.'], 400);
        }

        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return new JsonResponse(['message' => 'You need to be logged in to perform this action.'], 403);
        }

        $company = $user->getCompany();
        if (!$company || !$company instanceof Company) {
            return new JsonResponse(['message' => 'No associated company has been found.'], 403);
        }

        $group = $em->getRepository(ExtrasGroup::class)->findOneBy(['id' => $groupId, 'company' => $company]);
        $em->remove($group);
        $em->flush();

        return new JsonResponse([], 200);
    }

    #[Route('/deleteExtra', name: 'deleteExtra')]
    public function deleteExtra(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $extraId = $request->request->get('extra');
        $dish = $request->request->get('dish');

        if (!$extraId || !$dish) {
            return new JsonResponse(['message' => 'Missing parameters.'], 400);
        }

        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return new JsonResponse(['message' => 'You need to be logged in to perform this action.'], 403);
        }

        $company = $user->getCompany();
        if (!$company || !$company instanceof Company) {
            return new JsonResponse(['message' => 'No associated company has been found.'], 403);
        }

        $extra = $em->getRepository(Extra::class)->findOneBy(['id' => $extraId, 'company' => $company]);
        $em->remove($extra);
        $em->flush();

        return new JsonResponse([], 200);
    }
}
