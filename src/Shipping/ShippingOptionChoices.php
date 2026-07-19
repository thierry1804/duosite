<?php

namespace App\Shipping;

/**
 * Les trois modes d'expédition disponibles pour une offre (sélection unique par ligne).
 */
final class ShippingOptionChoices
{
    public const AIR_EXPRESS = 'Aérien Express';

    public const AIR_STANDARD = 'Aérien Standard';

    public const MARITIME = 'Maritime';

    /**
     * @return array<string, string>
     */
    public static function formChoices(): array
    {
        return [
            self::AIR_EXPRESS => self::AIR_EXPRESS,
            self::AIR_STANDARD => self::AIR_STANDARD,
            self::MARITIME => self::MARITIME,
        ];
    }

    /**
     * Description et délai max par nom de mode (préremplissage formulaire).
     *
     * @return array<string, array{description: string, estimatedDeliveryDays: int}>
     */
    public static function defaultsByName(): array
    {
        return [
            self::AIR_EXPRESS => [
                'description' => "Les départs sont effectués chaque lundi et jeudi matin. Après la validation de votre commande, la réception des articles à l'entrepôt peut prendre 2 à 7 jours, selon le fournisseur et sa province.\nLe délai de transport express (3 à 5 jours) commence à être compté à partir du jour du départ du vol.\nLe poids minimum facturé est de 250 g. Tout article de moins de 250 g sera donc facturé à 250 g.\nLes frais d'expédition sont calculés au kilo.",
                'estimatedDeliveryDays' => 7,
            ],
            self::AIR_STANDARD => [
                'description' => "Le départ est effectué tout les vendredis matin. Après la validation de votre commande, la réception des articles à l'entrepôt peut prendre 2 à 7 jours, selon le fournisseur et sa province.\nLe délai de transport normal (10 à 15 jours) commence à être compté à partir du jour du départ du vol.\nLe poids minimum facturé est de 250 g. Tout article de moins de 250 g sera donc facturé à 250 g.\nLes frais d'expédition sont calculés au kilo.",
                'estimatedDeliveryDays' => 15,
            ],
            self::MARITIME => [
                'description' => "Il y a deux départs chaque semaine (les jours exacts peuvent varier selon le planning des navires). Après la validation de votre commande, la réception des articles à l'entrepôt peut prendre 2 à 7 jours, selon le fournisseur et sa province.\nLe délai de transport maritime est estimé entre 55 et 75 jours, à compter du départ du bateau.\nLes frais d'expédition sont calculés au CBM (mètre cube). Pour les volumes inférieurs à 0,25 CBM, le tarif appliqué est plus élevé que pour les volumes supérieurs à 0,25 CBM.",
                'estimatedDeliveryDays' => 45,
            ],
        ];
    }
}
