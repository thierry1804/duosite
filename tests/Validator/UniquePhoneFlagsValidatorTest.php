<?php

namespace App\Tests\Validator;

use App\Entity\ContactPhone;
use App\Entity\SiteContactSettings;
use App\Validator\Constraints\UniquePhoneFlags;
use App\Validator\Constraints\UniquePhoneFlagsValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniquePhoneFlagsValidatorTest extends TestCase
{
    private function validate(SiteContactSettings $settings): int
    {
        $violationCount = 0;
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('atPath')->willReturnSelf();
        $builder->method('addViolation')->willReturnCallback(function () use (&$violationCount) {
            ++$violationCount;
        });

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->method('buildViolation')->willReturn($builder);

        $validator = new UniquePhoneFlagsValidator();
        $validator->initialize($context);
        $validator->validate($settings, new UniquePhoneFlags());

        return $violationCount;
    }

    public function testRejectsEmptyPhoneList(): void
    {
        $this->assertSame(1, $this->validate(new SiteContactSettings()));
    }

    public function testRejectsTwoWhatsappFlags(): void
    {
        $s = new SiteContactSettings();
        $s->addPhone((new ContactPhone())->setNumber('+261 38 42 711 68')->setIsWhatsapp(true)->setPosition(0));
        $s->addPhone((new ContactPhone())->setNumber('+261 33 64 554 78')->setIsWhatsapp(true)->setPosition(1));
        $this->assertSame(1, $this->validate($s));
    }

    public function testRejectsTwoPrimaryFlags(): void
    {
        $s = new SiteContactSettings();
        $s->addPhone((new ContactPhone())->setNumber('+261 38 42 711 68')->setIsPrimary(true)->setPosition(0));
        $s->addPhone((new ContactPhone())->setNumber('+261 33 64 554 78')->setIsPrimary(true)->setPosition(1));
        $this->assertSame(1, $this->validate($s));
    }

    public function testAcceptsValidConfiguration(): void
    {
        $s = new SiteContactSettings();
        $s->addPhone(
            (new ContactPhone())
                ->setNumber('+261 38 42 711 68')
                ->setIsWhatsapp(true)
                ->setIsPrimary(true)
                ->setPosition(0)
        );
        $this->assertSame(0, $this->validate($s));
    }
}
