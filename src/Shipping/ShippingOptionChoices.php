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
}
