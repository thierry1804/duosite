<?php

namespace App\Tests\Service;

use App\Entity\ContactPhone;
use App\Entity\SiteContactSettings;
use App\Entity\SocialLink;
use App\Repository\SiteContactSettingsRepository;
use App\Service\SiteContactProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SiteContactProviderTest extends TestCase
{
    private SiteContactSettingsRepository&MockObject $repository;
    private SiteContactProvider $provider;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SiteContactSettingsRepository::class);
        $this->provider = new SiteContactProvider($this->repository);
    }

    /**
     * @param list<array{number: string, wa?: bool, primary?: bool, label?: ?string}> $defs
     */
    private function settingsWithPhones(array $defs): SiteContactSettings
    {
        $s = new SiteContactSettings();
        $s->setEmail('contact@duoimport.mg');
        $s->setWhatsappDefaultMessage('Bonjour Duo Import MDG, ');
        foreach ($defs as $i => $d) {
            $p = (new ContactPhone())
                ->setNumber($d['number'])
                ->setIsWhatsapp($d['wa'] ?? false)
                ->setIsPrimary($d['primary'] ?? false)
                ->setPosition($i)
                ->setLabel($d['label'] ?? null);
            $s->addPhone($p);
        }

        return $s;
    }

    public function testGetWhatsappPhoneReturnsFlaggedNumber(): void
    {
        $s = $this->settingsWithPhones([
            ['number' => '+261 33 64 554 78'],
            ['number' => '+261 38 42 711 68', 'wa' => true],
        ]);
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertSame('+261 38 42 711 68', $this->provider->getWhatsappPhone()?->getNumber());
    }

    public function testGetPrimaryPhoneFallsBackToFirst(): void
    {
        $s = $this->settingsWithPhones([
            ['number' => '+261 33 64 554 78'],
            ['number' => '+261 38 42 711 68'],
        ]);
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertSame('+261 33 64 554 78', $this->provider->getPrimaryPhone()?->getNumber());
    }

    public function testGetWhatsappUrlBuildsWaMeLink(): void
    {
        $s = $this->settingsWithPhones([
            ['number' => '+261 38 42 711 68', 'wa' => true],
        ]);
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertSame(
            'https://wa.me/261384271168?text=Bonjour%20Duo%20Import%20MDG%2C%20',
            $this->provider->getWhatsappUrl()
        );
    }

    public function testGetWhatsappUrlReturnsNullWhenNoWhatsapp(): void
    {
        $s = $this->settingsWithPhones([['number' => '+261 33 64 554 78']]);
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertNull($this->provider->getWhatsappUrl());
    }

    public function testFormatFooterHoursGroupsWeekdays(): void
    {
        $s = SiteContactSettings::createWithDefaults();
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertSame('Lun - Ven: 8h00 - 17h00', $this->provider->formatFooterHours());
    }

    public function testGetSocialLinksSkipsEmptyUrl(): void
    {
        $s = new SiteContactSettings();
        $s->addSocialLink((new SocialLink())->setNetwork('facebook')->setUrl('https://www.facebook.com/duoimportmdg')->setPosition(0));
        $s->addSocialLink((new SocialLink())->setNetwork('tiktok')->setUrl('')->setPosition(1));
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertCount(1, $this->provider->getSocialLinks());
    }
}
