<?php

namespace App\Controller;

use App\Entity\LegalPage;
use App\Form\LegalPageType;
use App\Repository\LegalPageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminLegalPageController extends AbstractController
{
    #[Route('/legal-pages', name: 'app_admin_legal_pages', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        LegalPageRepository $legalPageRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $tab = $request->query->get('tab', 'cgv');
        if (!\in_array($tab, ['cgv', 'privacy'], true)) {
            $tab = 'cgv';
        }

        $page = $tab === 'privacy'
            ? $legalPageRepository->getOrCreate(LegalPage::SLUG_PRIVACY, 'Politique de confidentialité')
            : $legalPageRepository->getOrCreate(LegalPage::SLUG_CGV, 'Conditions Générales de Vente');

        $form = $this->createNamedForm($tab, LegalPageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $page->touchUpdatedAt();
            $entityManager->flush();

            $this->addFlash(
                'success',
                $tab === 'privacy'
                    ? 'La politique de confidentialité a été enregistrée.'
                    : 'Les CGV ont été enregistrées.'
            );

            return $this->redirectToRoute('app_admin_legal_pages', ['tab' => $tab]);
        }

        return $this->render('admin/legal_pages.html.twig', [
            'tab' => $tab,
            'form' => $form->createView(),
            'page' => $page,
        ]);
    }
}
