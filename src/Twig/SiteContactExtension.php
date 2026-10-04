<?php

namespace App\Twig;

use App\Service\SiteContactProvider;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SiteContactExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly SiteContactProvider $siteContactProvider
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'site_contact' => $this->siteContactProvider,
        ];
    }
}
