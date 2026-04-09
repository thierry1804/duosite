<?php

namespace App\Controller\Admin;

use App\Service\Ga4ReportingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/traffic')]
class TrafficController extends AbstractController
{
    #[Route('', name: 'app_admin_traffic_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/traffic/dashboard.html.twig');
    }

    #[Route('/api/overview', name: 'app_admin_traffic_overview', methods: ['GET'])]
    public function overview(Request $request, Ga4ReportingService $ga4ReportingService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $period = (string) $request->query->get('period', '7d');

        return $this->json($ga4ReportingService->getOverview($period));
    }
}
