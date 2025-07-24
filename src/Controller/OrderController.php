<?php

namespace App\Controller;

use App\Dto\CompanyDto;
use App\Entity\Company;
use App\Entity\Dish;
use App\Entity\Extra;
use App\Entity\ExtrasGroup;
use App\Entity\Order;
use App\Entity\OrderPartial;
use App\Entity\User;
use App\Enum\OrderStatusEnum;
use App\Mapper\CompanyMapper;
use App\Mapper\DishMapper;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Utility\Utility;
use DateTime;
use Doctrine\ORM\EntityManager;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class OrderController extends AbstractController
{
    private \Redis|Relay|RelayCluster|\RedisArray|\RedisCluster|\Predis\ClientInterface $_client;

    const COMPLETE_ACTION = 1;
    const CANCEL_ACTION = 2;
    const RATE_ACTION_BEFORE = 3;
    const RATE_ACTION_DONE = 4;

    const MAX_RATING_STARS = 5;

    function __construct()
    {
        $this->_client = RedisAdapter::createConnection('redis://DefSecPW-39173!@surukesh.ddnss.de:11111');
    }
    private function _validateDataForPersisting(int $id, mixed $order, string $address, EntityManagerInterface $em, LoggerInterface $logger): array
    {
        $company = $em->getRepository(Company::class)->find($id);

        if (!Utility::isValidCompany($company)) {
            return ['status' => 404, 'message' => 'No restaurant is associated with this order.'];
        }

        $user = $this->getUser();
        if (!Utility::isValidUser($user)) {
            return ['status' => 401, 'message' => 'You need to be logged in.'];
        }

        if (Utility::isCompanyAccount($user)) {
            return ['status' => 400, 'message' => 'You can\'t place orders with commercial accounts.'];
        }

        if ($address === '') {
            $city = $user->getCity();
            $zip = $user->getZipcode();
            $street = $user->getStreet();
            $sn = $user->getSn();
            $address = [
                'city' => $city,
                'zip' => $zip,
                'street' => $street,
                'sn' => $sn,
            ];
        } else {
            try {
                $address = json_decode($address, true);
                if (
                    !isset($address['city'])
                    || !isset($address['zip'])
                    || !isset($address['street'])
                    || !isset($address['sn'])
                ) {
                    return ['status' => 400, 'message' => 'Wrong address format.'];
                } elseif ($address['zip'] === '' || $address['city'] === '' || $address['street'] === '' || $address['sn'] === '') {
                    return ['status' => 400, 'message' => 'Please set up a delivery address.'];
                }

            } catch (\Throwable $th) {
                $logger->error('Error: '.$th->getMessage(), ['exception' => $th]);
                return ['status' => 400, 'message' => 'Wrong address format.'];
            }
        }

        if (!Utility::isInDeliveryRange($company, $user, $address['zip'])) {
            $zip = $address['zip'];
            return ['status' => 400, 'message' => "The company does not deliver to the zip $zip"];
        }

        try {
            $order = json_decode($order, true);
        } catch (\Throwable $th) {
            $logger->error('Error: '.$th->getMessage(), ['exception' => $th]);
            return ['status' => 500];
        }

        if (!is_array($order)) {
            return ['status' => 500];
        }

        $dishesCollection = [];

        foreach ($order as $dishId => $dishes) {
            if (!is_array($dishes)) {
                return ['status' => 400];
            }

            $dishCollection = [];
            $dishEntity = $em->getRepository(Dish::class)->findOneBy(['id' => $dishId, 'company' => $company, 'deleted' => false]);
            

            if (!$dishEntity || !$dishEntity instanceof Dish) {
                return ['status' => 400];
            }

            foreach ($dishes as $partialId => $dish) {
                if (!is_array($dish)) {
                    return ['status' => 400];
                }
                if (!isset($dish['extras']) || !is_array($dish['extras'])) {
                    return ['status' => 400];
                }
                if (!isset($dish['groups']) || !is_array($dish['groups'])) {
                    return ['status' => 400];
                }

                $dishPersistMapper = new DishMapper();

                $dishReadyForPersisting = $dishPersistMapper->dishEntityToDishPersistDto($dishEntity, $dish);

                if (!$dishReadyForPersisting) {
                    return ['status' => 400];
                }
                $dishCollection[$partialId] = $dishReadyForPersisting;
            }
            $dishesCollection[$dishId] = $dishCollection;
        }

        return ['status' => 1, 'dishes' => $dishesCollection, 'company' => $company->getId(), 'address' => $address];
    }

    private function _validateAndProcessData(?string $order, EntityManagerInterface $em, LoggerInterface $logger): array
    {
        if (!$order || $order === '') {
            return ['status' => 400, 'message' => 'No order found. Either it\'s expired or invalid.'];
        }

        $data = json_decode($order, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['status' => 400, 'message' => 'Invalid order format.'];
        }
        $user = $this->getUser();

        if (Utility::isValidUser($user)) {
            if ($data['user'] !== $user->getId()) {
                return ['status' => 401, 'message' => 'Order is not assigned to user.'];
            }
        } else {
            return ['status' => 401, 'message' => 'You are not logged in.'];
        }

        if (Utility::isCompanyAccount($user)) {
            return ['status' => 400, 'message' => 'You can\'t place orders with commercial accounts.'];
        }

        if (!isset($data['company'])) {
            return ['status' => 400, 'message' => 'Missing data, please try again.'];
        }

        if (!isset($data['address'])) {
            return ['status' => 400, 'message' => 'No valid address is assigned to the order.'];
        }

        $address = NULL;

        try {
            $address = json_decode($data['address'], true);
            if (
                !isset($address['city'])
                || !isset($address['zip'])
                || !isset($address['street'])
                || !isset($address['sn'])
            ) {
                return ['status' => 400, 'message' => 'Wrong address format.'];
            } elseif ($address['zip'] === '' || $address['city'] === '' || $address['street'] === '' || $address['sn'] === '') {
                return ['status' => 400, 'message' => 'Please set up a delivery address.'];
            }
        } catch (\Throwable $th) {
            $logger->error('Error: '.$th->getMessage(), ['exception' => $th]);
            return ['status' => 400, 'message' => 'Wrong address format.'];
        }

        $company = $em->getRepository(Company::class)->findOneBy(['id' => (int) $data['company']]);

        if (!Utility::isValidCompany($company)) {
            return ['status' => 404, 'message' => 'The requested company could not be found.'];
        }

        if (!Utility::isInDeliveryRange($company, $user, $address['zip'])) {
            return ['status' => 403, 'message' => 'The restaurant does not deliver to your address.'];
        }

        $dishes = json_decode($data['dishes'], true);

        $processedDishes = [];
        foreach ($dishes as $dish) {
            foreach ($dish as $partialId => $partialDish) {
                $processedDish = [
                    'dish' => $partialDish['id'],
                    'size' => $partialDish['size'],
                    'extras' => [],
                    'total' => $partialDish['totalPrice'],
                    'name' => $partialDish['name'],
                    'partialId' => $partialId,
                ];
                foreach ($partialDish['extrasGroups'] as $group => $extra) {
                    $processedDish['extras'][] = [
                        'extra' => current($extra),
                        'type' => 2,
                        'group' => $group,
                    ];
                }
                foreach ($partialDish['extras'] as $extra) {
                    $processedDish['extras'][] = [
                        'extra' => $extra,
                        'type' => 1,
                        'group' => '',
                    ];
                }
                $processedDishes[] = $processedDish;
            }
        }
        return ['status' => 200, 'company' => $company, 'dishes' => $processedDishes, 'address' => $address];
    }

    #[Route('/ttl/{order}', name: 'ttl')]
    public function remainingTtl(Request $request): JsonResponse
    {
        $order = $request->get('order');
        $ttl = $this->_client->ttl($order);
        $ttl = $ttl > 0 ? $ttl : 0;
        $status = $ttl > 0 ? 200 : 400;
        return new JsonResponse($ttl, $status);
    }

    #[Route('/persist_order', name: 'persist_order')]
    public function persistOrder(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ): JsonResponse
    {
        $order = $request->request->get('dishes');
        $id = $request->request->get('id');
        $address = $request->request->get('address');

        $data = $this->_validateDataForPersisting($id, $order, $address, $em, $logger);

        if ($data['status'] !== 1) {
            return isset($data['message'])
                ? new JsonResponse($data['message'], $data['status'])
                : new JsonResponse('An unknown error occured.', $data['status'])
            ;
        }

        $uuid = Uuid::uuid7();

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {

                if ($object instanceof Company) {
                    return $object->getId();
                }
                return $object->getId();
            },
        ];
        
        $user = $this->getUser();
        if (Utility::isValidUser($user)) {
            $companyJson = $serializer->serialize($data['company'], 'json');
            $address = $serializer->serialize($data['address'], 'json');
            $dishesJson = $serializer->serialize($data['dishes'], 'json', $context);
            $this->_client->set(
                $uuid,
                json_encode(
                    [
                        'company' => $companyJson,
                        'dishes' => $dishesJson,
                        'user' => $user->getId(),
                        'address' => $address
                    ]
                ),
                'EX',
                900
            );
        }
        return new JsonResponse($uuid, 200);
    }

    #[Route('/finalize/{order}', name: 'finalize')]
    public function finalizeOrder(Request $request, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $uuid = $request->get('order');

        
        $order = $this->_client->get($uuid);

        $data = $this->_validateAndProcessData($order, $em, $logger);

        $status = $data['status'] ?? 500;
        $company = $data['company'] ?? NULL;
        $dishes = $data['dishes'] ?? [];
        $address = $data['address'] ?? [];
        $message = $data['message'] ?? 'An unknown issue occured';
        $ttl = -1;

        if ($status === 200) {
            $ttl = $this->_client->ttl($uuid);
        } else {
            $this->_client->del($uuid);
        }

        return $this->render('order/index.html.twig', [
            'status' => $status,
            'company' => $company,
            'dishes' => $dishes,
            'address' => $address,
            'ttl' => $ttl,
            'message' => $message,
            'orderId' => $uuid,
        ]);
    }

    #[Route('/order_confirm', name: 'order_confirm')]
    public function confirmOrder(Request $request, EntityManagerInterface $em, LoggerInterface $logger): JsonResponse
    {
        $uuid = $request->request->get('orderId');
        $specialRequest = $request->request->get('specialRequest');

        if (!$uuid || !is_string($uuid) || $uuid === '') {
            return new JsonResponse('The order is invalid.', 400);
        }

        $order = $this->_client->get($uuid);

        if (!$order || !is_string($order) || $order === '') {
            return new JsonResponse('The order is invalid or expired.', 400);
        }

        $data = $this->_validateAndProcessData($order, $em, $logger);

        if(!isset($data['status'])) {
            return new JsonResponse($data['message'] ?? 'An unknown error occured, please try again.', 500);
        }

        if ($data['status'] !== 200) {
            return new JsonResponse($data['message'] ?? 'An unknown error occured, please try again.', $data['status']);
        }

        if (!isset($data['dishes'])) {
            return new JsonResponse('An unknown error occured, please try again.', 500);
        }

        if (!isset($data['company'])) {
            return new JsonResponse('No valid restaurant is assigned to the order.', 404);
        }

        if (!isset($data['address'])) {
            return new JsonResponse('No valid address is assigned to the order.', 404);
        }

        $company = $data['company'];
        if (!Utility::isValidCompany($company)) {
            return new JsonResponse('An unknown error occured, please try again.', 500);
        }

        $orderEntity = new Order();
        $orderEntity->setCompany($company);
        $orderEntity->setUser($this->getUser());
        $orderEntity->setCreated(new DateTime());

        $orderEntity->setDeliveryCity($data['address']['city']);
        $orderEntity->setDeliveryZip($data['address']['zip']);
        $orderEntity->setDeliveryStreet($data['address']['street']);
        $orderEntity->setDeliverySn($data['address']['sn']);

        if (count($data['dishes']) < 1) {
            return new JsonResponse('The order is containing no dishes.', 400);
        }
        $dishArray = [];
        $dish = NULL;
        foreach ($data['dishes'] as $dishData) {
            try {
                if (!isset($dishArray[$dishData['dish']])) {
                    $dish = $em->getRepository(Dish::class)->find($dishData['dish']);
                    if (!$dish instanceof Dish) {
                        return new JsonResponse('Could not process one or more selected dishes.', 400);
                    }
                    $dishArray[$dishData['dish']] = $dish;
                } else {
                    $dish = $dishArray[$dishData['dish']];
                }
                $orderPartialEntity = new OrderPartial();
                $orderPartialEntity->setDish($dish);
                $orderPartialEntity->setFoodOrder($orderEntity);
                $orderPartialEntity->setSize($dishData['size'] ?? '');
                $orderPartialEntity->setPriceSnapshot($dishData['total']);
                $orderPartialEntity->setExtras($dishData['extras']);
                $em->persist($orderPartialEntity);
                $orderEntity->addOrderPartial($orderPartialEntity);
            } catch (\Throwable $th) {
                $logger->error('Error: '.$th->getMessage(), ['exception' => $th]);
                return new JsonResponse('Could not process one or more selected dishes.', 500);
            }
        }
        $orderEntity->setCustomerNote($specialRequest);

        $orderEntity->setStatus(OrderStatusEnum::PENDING);
        $em->persist($orderEntity);
        $em->flush();
        return new JsonResponse($orderEntity->getId(), 200);
        return new JsonResponse(null, 200);
    }

    #[Route('/order_confirmed/{orderId?}/{uuid?}', name: 'order_confirmed')]
    public function comfirmedOrder(Request $request, EntityManagerInterface $em): Response
    {
        $orderId = $request->get('orderId');

        if (!$orderId || $orderId !== (string)(int)$orderId) {
            return $this->render('order/confirmed.html.twig', [
                'status' => 400,
                'order' => NULL
            ]);
        }

        $uuid = $request->get('uuid');
        $order = $this->_client->get($uuid); //Just show the confirmation once
        if (!$order) {
            return $this->render('order/confirmed.html.twig', [
                'status' => 404,
                'order' => NULL
            ]);
        }

        $user = $this->getUser();
        if (!Utility::isValidUser($user)) {
            return $this->render('order/confirmed.html.twig', [
                'status' => 401,
                'order' => NULL
            ]);
        }

        $order = $em->getRepository(Order::class)->findOneBy(['id' => $orderId, 'user' => $user]);
        if (!$order || !$order instanceof Order) {
            return $this->render('order/confirmed.html.twig', [
                'status' => 404,
                'order' => NULL
            ]);
        }

        $this->_client->del($uuid);

        return $this->render('order/confirmed.html.twig', [
            'status' => 200,
            'order' => $order
        ]);
    }

    #[Route('order_list/{pending?}', name: 'order_list')]
    public function list(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): Response
    {
        $pending = $request->get('pending') !== 'done';
        $maxRes = $request->get('maxRes') ?? 1;
        $page = $request->get('page') ?? 1;

        $pagerfanta = NULL;

        $user = $this->getUser();
        if (!Utility::isValidUser($user)) {
            return $this->render('order/list.html.twig', [
                'status' => 401,
                'isUser' => false,
                'pending' => $pending,
                'pager' => $pagerfanta,
                'page' => $page,
                'maxRes' => $maxRes,
            ]);
        }

        $company = $user->getCompany();

        if (!Utility::accountIsSetUp($user)) {
            return $this->render('order/list.html.twig', [
                'status' => 200,
                'isUser' => false,
                'pending' => $pending,
                'pager' => $pagerfanta,
                'page' => $page,
                'maxRes' => $maxRes,
            ]);
        }

        try {
            $pagerfanta = new Pagerfanta(
                new QueryAdapter(
                    Utility::isValidCompany($company)
                    ? (
                        $pending
                            ? $em->getRepository(Order::class)->findByCompanyAndPending($company)
                            : $em->getRepository(Order::class)->findByCompanyAndDone($company)
                    )
                    : (
                        $pending
                            ? $em->getRepository(Order::class)->findByUserAndPending($user)
                            : $em->getRepository(Order::class)->findByUserAndDone($user)
                    )
                )
            );
            $pagerfanta->setMaxPerPage($maxRes);
            $pagerfanta->setCurrentPage($page);
        } catch (\Throwable $th) {
            return $this->render('order/list.html.twig', [
                'status' => 500,
                'isUser' => false,
                'pending' => $pending,
                'pager' => $pagerfanta,
                'page' => $page,
                'maxRes' => $maxRes,
            ]);
        }

        return $this->render('order/list.html.twig', [
            'status' => 200,
            'isUser' => Utility::isValidCompany($company) ? false : true,
            'pending' => $pending,
            'pager' => $pagerfanta,
            'page' => $page,
            'maxRes' => $maxRes,
        ]);

        //$request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        /*$user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return $this->render('order/list.html.twig', [
                'status' => 401,
                'isUser' => false,
                'pending' => $pending,
                'orders' => [],
                'pages' => 0,
                'totalElements' => 0,
                'maxRes' => 0,
            ]);
        }

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {

                if ($object instanceof Company) {
                    return $object->getId();
                }
                return $object->getId();
            },
        ];
        $company = $user->getCompany();
        $total = 0;
        $defaultMaxRes = 1;
        if ($company && $company instanceof Company) {
            $orders = $pending
            ? json_decode(
                $serializer->serialize(
                    $em->getRepository(Order::class)->findByCompanyAndPending($company, $total, $defaultMaxRes),
                    'json',
                    $context
                ),
                true
            )
            : json_decode(
                $serializer->serialize(
                    $em->getRepository(Order::class)->findByCompanyAndDone($company, $total, $defaultMaxRes),
                    'json',
                    $context
                ),
                true
            );

            return $this->render('order/list.html.twig', [
                'status' => 200,
                'isUser' => false,
                'pending' => $pending,
                'orders' => $orders,
                'pages' => ceil($total / $defaultMaxRes),
                'totalElements' => $total,
                'maxRes' => $defaultMaxRes,
            ]);
        } else {
            $orders = $pending
            ? json_decode(
                $serializer->serialize(
                    $em->getRepository(Order::class)->findByUserAndPending($user, $total, $defaultMaxRes),
                    'json',
                    $context
                ),
                true
            )
            : json_decode(
                $serializer->serialize(
                    $em->getRepository(Order::class)->findByUserAndDone($user, $total, $defaultMaxRes),
                    'json',
                    $context
                ),
                true
            );
            return $this->render('order/list.html.twig', [
                'status' => 200,
                'isUser' => true,
                'pending' => $pending,
                'orders' => $$orders,
                'pages'=> ceil($total / $defaultMaxRes),
                'totalElements' => $total,
                'maxRes' => $defaultMaxRes,
            ]);
        }*/


    }

    #[Route('/order_view/{id?}', name: 'order_view')]
    public function show(Request $request, EntityManagerInterface $em): Response
    {
        $id = $request->get('id');
        if (!$id|| $id !== (string)(int)$id) {
            return $this->render('order/view.html.twig', [
                'status' => 400,
                'message' => 'Whoa! Seems you forgot to tell us the order to view?!',
            ]);
        }

        $user = $this->getUser();
        if (!Utility::isValidUser($user)) {
            return $this->render('order/view.html.twig', [
                'status' => 401,
                'message' => 'You need to be logged in to view this page.',
            ]);
        }

        $order = NULL;

        if (Utility::isCompanyAccount($user)) {
            $company = $user->getCompany();
            if (!Utility::isValidCompany($company)) {
                return new JsonResponse(['message' => "Could not find order #$id."], 404);
            }
            $order = $em->getRepository(Order::class)->findOneBy(['id' => $id, 'company' => $company]);
        } else {
            $order = $em->getRepository(Order::class)->findOneBy(['id' => $id, 'user' => $user]);
        }

        if (!$order) {
            return $this->render('order/view.html.twig', [
                'status' => 404,
                'message' => "Could not find order #$id.",
            ]);
        }

        return $this->render('order/view.html.twig', [
            'status' => 200,
            'message' => '',
            'order' => $order,
        ]);
    }

    private function handleAction (
        int $type,
        EntityManagerInterface $em,
        Request $request,
        ?Order &$order = NULL,
        ?float $rating = NULL,
        ?string $ratingText = '',
    ): JsonResponse {
        $infoText = '';
        $infoTextDT = '';
        $status = 0;
        switch ($type) {
            case self::COMPLETE_ACTION:
                $infoText = 'complete';
                $infoTextDT = 'completable';
                $status = OrderStatusEnum::DONE;
                break;
            case self::CANCEL_ACTION:
                $infoText = 'cancel';
                $infoTextDT = 'cancelable';
                $status = OrderStatusEnum::CANCELLED;
                break;
            case self::RATE_ACTION_BEFORE:
            case self::RATE_ACTION_DONE:
                $infoText = 'rate';
                $infoTextDT = 'rateable';
                break;
            default:
                return new JsonResponse(['message' => "Internal error, please try again."], 500);
                break;
        }

        $id = $request->get('id');
        if (!$id|| $id !== (string)(int)$id) {
            return new JsonResponse(['message' => "We\'ve received no order to $infoText..."], 400);
        }

        $user = $this->getUser();
        if (!Utility::isValidUser($user)) {
            return new JsonResponse(['message' => "You need to be logged in to $infoText an order."], 401);
        }

        $order = NULL;
        $isCompany = in_array('ROLE_COMPANY', $user->getRoles(), true);
        $company = $user->getCompany();

        if ($isCompany && !$company) {
            return new JsonResponse(
                [
                    'message' =>
                        'You are logged in with a commercial account, but no associated ' .
                        'company was found. Have you set up your company details already?'
                ],
                401
            );
        }

        switch ($type) {
            case self::CANCEL_ACTION:
                if ($isCompany) {
                    $order = $em->getRepository(Order::class)->findOneBy(['id' => $id, 'company' => $company]);
                } else {
                    $order = $em->getRepository(Order::class)->findOneBy(['id' => $id, 'user' => $user]);
                }
                break;
            case self::COMPLETE_ACTION:
                $order = $em->getRepository(Order::class)->findOneBy(['id' => $id, 'company' => $company]);
                break;
            case self::RATE_ACTION_BEFORE:
            case self::RATE_ACTION_DONE:
                $order = $em->getRepository(Order::class)->findOneBy(['id' => $id, 'user' => $user]);
                break;
            default:
                return new JsonResponse(['message' => "Internal error, please try again."], 500);
                break;
        }

        if (!$order || !$order instanceof Order) {
            return new JsonResponse(['message' => "Could not find order #$id."], 404);
        }

        $createdDT = $order->getCreated()->getTimestamp();
        $nowDT = strtotime('now');

        if ($type === self::CANCEL_ACTION && ($nowDT - $createdDT) > 300) {
            return new JsonResponse(['message' => "Orders are cancelable for the first 5 minutes only."], 403);
        } elseif (
            (
                $type === self::COMPLETE_ACTION
                || $type === self::RATE_ACTION_BEFORE
                || $type === self::RATE_ACTION_DONE
            )
            && ($nowDT - $createdDT) <= 300
        ) {
            return new JsonResponse(['message' => "Orders are $infoTextDT 5 minutes after creation."], 403);
        }

        switch ($type) {
            case self::CANCEL_ACTION:
            case self::COMPLETE_ACTION:
                $order->setStatus($status);
                $order->setDone(new DateTime());
            case self::RATE_ACTION_DONE:
                if ($rating) {
                    if ($order->getRating()) {
                        return new JsonResponse(['message' => "Order #$id has been rated already."], 403);
                    }
                    $order->setRating($rating);
                    $order->setRatingText($ratingText);
                }
                $em->persist($order);
                $em->flush();
                $_company = $order->getCompany();
                if (Utility::isValidCompany($_company)) {
                    $rating = $em->getRepository(Order::class)->getCompanyRating($_company);
                    if (is_array($rating) && isset($rating['totalRatings']) && isset($rating['avgRating'])) {
                        $_company->setTotalRatings($rating['totalRatings']);
                        $_company->setAverageRating($rating['avgRating']);
                        $em->persist($_company);
                        $em->flush();
                    }
                }
                break;
            case self::RATE_ACTION_BEFORE:
                return new JsonResponse(['order' => json_encode($order)], 200);
        }

        return new JsonResponse(['message' => ''], 200);
    }

    #[Route('/cancelOrder/{id?}', name: 'cancelOrder')]
    public function cancel(Request $request, EntityManagerInterface $em): JsonResponse
    {
         return $this->handleAction(self::CANCEL_ACTION, $em, $request);
    }

    #[Route('/completeOrder/{id?}', name: 'completeOrder')]
    public function complete(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handleAction(self::COMPLETE_ACTION, $em, $request);
    }

    private function _extractJsonResponseData(JsonResponse $jsonResponse): array
    {
        $responseData = [];
        $responseData['message'] = 'An unknown error occured';
        $responseData['status'] = 500;
        $parsed = NULL;
        try {
            $parsed = json_decode($jsonResponse->getContent(), true);
            $responseData['status'] = $jsonResponse->getStatusCode();
            $responseData['message'] = $parsed['message'];
        } catch (\Throwable $th) {
            error_log($th->getMessage());
        }
        return $responseData;
    }

    #[Route('/rateOrder/{id?}', name: 'rateOrder')]
    public function rateBefore(Request $request, EntityManagerInterface $em): Response
    {
        $order = NULL;
        $jsonResponse = $this->handleAction(self::RATE_ACTION_BEFORE, $em, $request, $order);

        if ($jsonResponse->getStatusCode() !== 200) {
            $responseData = $this->_extractJsonResponseData($jsonResponse);
            return $this->render('order/rating.html.twig', [
                'status' => $responseData['status'],
                'message' => $responseData['message'],
                'max' => self::MAX_RATING_STARS,
                'rating' => 0.0,
                'order' => 0,
                'ratingText' => '',
            ]);
        }
        
        return $this->render('order/rating.html.twig', [
            'status' => 200,
            'message' => '',
            'max' => self::MAX_RATING_STARS,
            'rating' => $order->getRating() ?? 0.0,
            'order' => $order->getId(),
            'ratingText' => $order->getRatingText(),
        ]);
    }

    #[Route('/sendRating/{id?}', name: 'sendRating')]
    public function rateDone(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $order = NULL;

        $rating = $request->request->get('rating');

        if ($rating === NULL || $rating !== (string)(float)$rating) {
            return new JsonResponse(['message' => "Invalid rating value1."], 400);
        }

        if ($rating < 0.0 || $rating > 4.0) {
            return new JsonResponse(['message' => "Invalid rating value2."], 400);
        }

        $ratingText = $request->request->get('ratingText');

        if ($ratingText && is_string($ratingText) && $ratingText !== '') {
            if (mb_strlen($ratingText, 'UTF-8') > 300) {
                if (!$rating|| $rating !== (string)(int)$rating) {
                    return new JsonResponse(['message' => "Review text is longer than 300 characters."], 400);
                }
            }
        }

        $jsonResponse = $this->handleAction(self::RATE_ACTION_DONE, $em, $request, $order, ($rating+1), $ratingText);

        return $jsonResponse;
    }
}