<?php

namespace App\Controller\Admin;

use App\Entity\ImportOrder;
use App\Repository\ImportOrderRepository;
use App\Service\ImportOrderTrackerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/import-orders')]
class ImportOrderController extends AbstractController
{
    public function __construct(
        private ImportOrderRepository $orderRepository,
        private ImportOrderTrackerService $trackerService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'app_admin_import_orders', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $status = $request->query->get('status');
        $orders = $this->orderRepository->findRecent(100);
        if ($status !== null && $status !== '') {
            $orders = array_filter($orders, fn (ImportOrder $o) => $o->getStatus() === $status);
        }
        return $this->render('admin/import_order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_import_order_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(Request $request, ImportOrder $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $possibleTransitions = $this->trackerService->getPossibleTransitions($order->getStatus());
        $trackingUrl = $this->generateUrl('app_import_order_tracking_show', ['token' => $order->getTrackingToken()], true);

        if ($request->isMethod('POST')) {
            $newStatus = $request->request->get('status');
            $weight = $request->request->get('weight');
            $dimensions = $request->request->get('dimensions');
            $shippingEstimate = $request->request->get('shippingEstimate');
            $shippingInfo = $request->request->get('shippingInfo');
            $comment = $request->request->get('comment');

            if ($weight !== null) {
                $order->setWeight($weight);
            }
            if ($dimensions !== null) {
                $order->setDimensions($dimensions);
            }
            if ($shippingEstimate !== null) {
                $order->setShippingEstimate($shippingEstimate);
            }
            if ($shippingInfo !== null) {
                $order->setShippingInfo($shippingInfo);
            }
            $this->entityManager->flush();

            if ($newStatus !== null && $newStatus !== '' && $newStatus !== $order->getStatus() && isset($possibleTransitions[$newStatus])) {
                try {
                    $this->trackerService->changeStatus($order, $newStatus, $comment);
                    $this->addFlash('success', 'Statut et informations mis à jour.');
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
                return $this->redirectToRoute('app_admin_import_order_show', ['id' => $order->getId()]);
            }
            $this->addFlash('success', 'Informations mises à jour.');
            return $this->redirectToRoute('app_admin_import_order_show', ['id' => $order->getId()]);
        }

        return $this->render('admin/import_order/show.html.twig', [
            'order' => $order,
            'possibleTransitions' => $possibleTransitions,
            'trackingUrl' => $trackingUrl,
            'statusLabels' => ImportOrderTrackerService::VALID_STATUSES,
        ]);
    }
}
