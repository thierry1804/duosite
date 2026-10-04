<?php

namespace App\Controller;

use App\Form\SiteContactSettingsType;
use App\Repository\SiteContactSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminSiteContactController extends AbstractController
{
    #[Route('/site-contact', name: 'app_admin_site_contact')]
    public function edit(
        Request $request,
        SiteContactSettingsRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $settings = $repository->getSettings();
        $settings->ensureDefaultOpeningHours();

        $form = $this->createForm(SiteContactSettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $i = 0;
            foreach ($settings->getPhones() as $phone) {
                $phone->setPosition($i++);
            }
            $i = 0;
            foreach ($settings->getSocialLinks() as $link) {
                $link->setPosition($i++);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Les coordonnées du site ont été mises à jour avec succès.');

            return $this->redirectToRoute('app_admin_site_contact');
        }

        return $this->render('admin/site_contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
