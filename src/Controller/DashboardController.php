<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Order;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    const PERIOD = 6;

    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return $this->render('dashboard/index.html.twig', [], new Response('You are not eligible to view this page.', 401));
        }
        $company = $user->getCompany();

        $companyId = -1;

        if ($company && $company instanceof Company) {
            $companyId = $company->getId();
        }

        return $this->render('dashboard/index.html.twig', [
            'company_id' => $companyId,
            'user' => $user,
        ]);
    }

    private function _getLastMonths(int $period = self::PERIOD): array
    {
        $months = [];
        for ($i = $period; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-$i months");
            $months[] = $date->format('F Y');
        }
        return $months;
    }

    private function _setChart(ChartBuilderInterface $chartBuilder, array $months, ?array $data = NULL): Chart
    {
        if (!$data) {
            for ($i=0; $i < self::PERIOD; $i++) { 
                $data[] = 0;
            }
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Orders quickview',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $data,
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => [
                    'Min' => 0,
                    'suggestedMax' => 10,
                ],
            ],
        ]);

        return $chart;
    }

    #[Route('/07e3b1546a627bb4f13a7b70ea00a71b7cd0be0d', name: 'getdashboardchart')]
    public function getDashboardChart(ChartBuilderInterface $chartBuilder, EntityManagerInterface $em)
    {
        $user = $this->getUser();
        $lastMonths = $this->_getLastMonths();
        $months = array_map(fn($month) => explode(' ', $month)[0], $lastMonths);

        if (!$user || !$user instanceof User) {
            return $this->render('dashboard/chart.html.twig', [
                'chart' => null,
            ]);
        }

        $roles = $user->getRoles();
        $isCompany = array_search('ROLE_COMPANY', $roles, true) !== false;

        $company = $user->getCompany();

        //Company account has not set up company information yet, so no orders should be available
        if ($isCompany && (!$company || !$company instanceof Company)) {
            $chart = $this->_setChart($chartBuilder, $months);
            return $this->render('dashboard/chart.html.twig', [
                'chart' => $chart,
            ]);
        }

        $orderRepository = $em->getRepository(Order::class); 

        $orderCountByMonth = [];

        foreach ($lastMonths as $month) {
            $orderCountByMonth[] = $orderRepository->getOrdersByMonth($month, $company, $user);
        }

        $chart = $this->_setChart($chartBuilder, $months, $orderCountByMonth);

        return $this->render('dashboard/chart.html.twig', [
            'chart' => $chart,
        ]);
    }
}
