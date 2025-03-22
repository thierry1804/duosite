<?php

// Script pour mettre à jour le statut de paiement pour le devis #16

// Chemin vers le fichier d'entrée Symfony
require_once 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// Créer le noyau Symfony
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

// Obtenir le gestionnaire d'entités
$entityManager = $kernel->getContainer()->get('doctrine')->getManager();

// Obtenir le service de calcul des frais
$feeCalculator = $kernel->getContainer()->get('App\Service\QuoteFeeCalculator');

// Récupérer le devis #16
$quoteRepository = $entityManager->getRepository('App\Entity\Quote');
$quote = $quoteRepository->find(16);

if (!$quote) {
    echo "Devis #16 non trouvé.\n";
    exit(1);
}

echo "Mise à jour du statut de paiement pour le devis #{$quote->getId()}\n";
echo "Nombre d'articles: " . count($quote->getItems()) . "\n";

// Recalcul des frais et mise à jour du statut de paiement
$result = $feeCalculator->calculateFee($quote);

echo "Résultat du calcul: " . print_r($result, true) . "\n";
echo "Nouveau statut de paiement: " . $quote->getPaymentStatus() . "\n";

// Sauvegarder les modifications
$entityManager->flush();

echo "Mise à jour terminée.\n"; 