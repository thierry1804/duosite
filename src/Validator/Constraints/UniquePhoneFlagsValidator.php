<?php

namespace App\Validator\Constraints;

use App\Entity\SiteContactSettings;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniquePhoneFlagsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniquePhoneFlags) {
            throw new UnexpectedTypeException($constraint, UniquePhoneFlags::class);
        }

        if (!$value instanceof SiteContactSettings) {
            return;
        }

        $phones = $value->getPhones();
        if ($phones->count() < 1) {
            $this->context->buildViolation($constraint->noPhoneMessage)
                ->atPath('phones')
                ->addViolation();
        }

        $wa = 0;
        $pr = 0;
        foreach ($phones as $phone) {
            if ($phone->isWhatsapp()) {
                ++$wa;
            }
            if ($phone->isPrimary()) {
                ++$pr;
            }
        }

        if ($wa > 1) {
            $this->context->buildViolation($constraint->whatsappMessage)
                ->atPath('phones')
                ->addViolation();
        }

        if ($pr > 1) {
            $this->context->buildViolation($constraint->primaryMessage)
                ->atPath('phones')
                ->addViolation();
        }
    }
}
