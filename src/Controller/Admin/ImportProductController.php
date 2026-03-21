<?php

namespace App\Controller\Admin;

use App\Entity\ImportProduct;
use App\Form\ImportProductType;
use App\Repository\ImportProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/import-products')]
class ImportProductController extends AbstractController
{
    public function __construct(
        private ImportProductRepository $productRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'app_admin_import_products', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $products = $this->productRepository->findBy([], ['code' => 'ASC']);
        return $this->render('admin/import_product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'app_admin_import_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $product = new ImportProduct();
        $form = $this->createForm(ImportProductType::class, $product);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $colorsText = $form->get('availableColorsText')->getData();
            $product->setAvailableColors($this->parseColors($colorsText));
            $this->handlePhotoUpload($form, $product);
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $this->addFlash('success', 'Produit enregistré.');
            return $this->redirectToRoute('app_admin_import_products');
        }
        return $this->render('admin/import_product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_import_product_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, ImportProduct $product): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $colorsInitial = implode(', ', $product->getAvailableColors());
        $form = $this->createForm(ImportProductType::class, $product, ['colors_initial' => $colorsInitial]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $colorsText = $form->get('availableColorsText')->getData();
            $product->setAvailableColors($this->parseColors($colorsText));
            $this->handlePhotoUpload($form, $product);
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $this->addFlash('success', 'Produit mis à jour.');
            return $this->redirectToRoute('app_admin_import_products');
        }
        return $this->render('admin/import_product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    private function parseColors(?string $text): array
    {
        if ($text === null || $text === '') {
            return [];
        }
        $parts = array_map('trim', explode(',', $text));
        return array_values(array_filter($parts));
    }

    private function handlePhotoUpload($form, ImportProduct $product): void
    {
        $file = $form->get('photoFile')->getData();
        if (!$file) {
            return;
        }
        $uploadDir = $this->getParameter('import_product_photos_directory');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            $this->addFlash('error', 'Le dossier d\'upload des photos n\'est pas accessible.');
            return;
        }
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalFilename) ?: 'photo';
        $ext = $file->guessExtension();
        if (!$ext) {
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg';
        }
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $ext;
        try {
            $file->move($uploadDir, $newFilename);
            if ($product->getPhotoFilename()) {
                $oldPath = $uploadDir . '/' . $product->getPhotoFilename();
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $product->setPhotoFilename($newFilename);
            $this->addFlash('success', 'Photo enregistrée.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible d\'enregistrer la photo : ' . $e->getMessage());
        }
    }
}
