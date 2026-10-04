<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class UniquePhoneFlags extends Constraint
{
    public string $noPhoneMessage = 'Ajoutez au moins un numéro de téléphone.';
    public string $whatsappMessage = 'Un seul numéro peut être marqué WhatsApp.';
    public string $primaryMessage = 'Un seul numéro peut être marqué principal.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
