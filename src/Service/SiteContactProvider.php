<?php

namespace App\Service;

use App\Entity\ContactPhone;
use App\Entity\OpeningHour;
use App\Entity\SiteContactSettings;
use App\Entity\SocialLink;
use App\Repository\SiteContactSettingsRepository;

class SiteContactProvider
{
    private const DAY_SHORT = [
        1 => 'Lun',
        2 => 'Mar',
        3 => 'Mer',
        4 => 'Jeu',
        5 => 'Ven',
        6 => 'Sam',
        7 => 'Dim',
    ];

    private const DAY_LONG = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];

    public function __construct(
        private readonly SiteContactSettingsRepository $repository
    ) {
    }

    public function getSettings(): SiteContactSettings
    {
        return $this->repository->getSettings();
    }

    /** @return list<ContactPhone> */
    public function getPhones(): array
    {
        return $this->getSettings()->getPhones()->toArray();
    }

    public function getWhatsappPhone(): ?ContactPhone
    {
        foreach ($this->getPhones() as $phone) {
            if ($phone->isWhatsapp()) {
                return $phone;
            }
        }

        return null;
    }

    public function getPrimaryPhone(): ?ContactPhone
    {
        foreach ($this->getPhones() as $phone) {
            if ($phone->isPrimary()) {
                return $phone;
            }
        }

        $phones = $this->getPhones();

        return $phones[0] ?? null;
    }

    /** @return list<OpeningHour> */
    public function getOpeningHours(): array
    {
        return $this->getSettings()->getOpeningHours()->toArray();
    }

    /** @return list<SocialLink> */
    public function getSocialLinks(): array
    {
        return array_values(array_filter(
            $this->getSettings()->getSocialLinks()->toArray(),
            static fn (SocialLink $l) => trim($l->getUrl()) !== ''
        ));
    }

    /** @return list<string> */
    public function getAddressLinesList(): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $this->getSettings()->getAddressLines()) ?: [];

        return array_values(array_filter(array_map('trim', $lines), static fn ($l) => $l !== ''));
    }

    public function toDigits(string $number): string
    {
        return preg_replace('/\D+/', '', $number) ?? '';
    }

    public function toTelHref(string $number): string
    {
        $digits = $this->toDigits($number);

        return 'tel:+' . ltrim($digits, '+');
    }

    public function getWhatsappUrl(): ?string
    {
        $phone = $this->getWhatsappPhone();
        if (!$phone) {
            return null;
        }

        $digits = $this->toDigits($phone->getNumber());
        $msg = $this->getSettings()->getWhatsappDefaultMessage() ?? '';
        $query = $msg !== '' ? '?text=' . rawurlencode($msg) : '';

        return 'https://wa.me/' . $digits . $query;
    }

    public function formatFooterHours(): string
    {
        $groups = $this->groupOpenDays();
        if ($groups === []) {
            return '';
        }

        $g = $groups[0];

        return sprintf('%s: %s', $g['label_short'], $g['hours']);
    }

    /** @return list<string> */
    public function formatContactHoursLines(): array
    {
        $lines = [];
        foreach ($this->groupAllDays() as $g) {
            $lines[] = $g['is_closed']
                ? sprintf('%s: Fermé', $g['label_long'])
                : sprintf('%s: %s', $g['label_long'], $g['hours']);
        }

        return $lines;
    }

    public function socialIconClass(string $network): string
    {
        return match ($network) {
            SocialLink::NETWORK_FACEBOOK => 'facebook-f',
            SocialLink::NETWORK_X => 'x-twitter',
            SocialLink::NETWORK_OTHER => 'link',
            default => $network,
        };
    }

    /** @return list<array{label_short: string, label_long: string, hours: string, is_closed: bool}> */
    private function groupOpenDays(): array
    {
        return array_values(array_filter($this->groupAllDays(), static fn ($g) => !$g['is_closed']));
    }

    /**
     * @return list<array{label_short: string, label_long: string, hours: string, is_closed: bool}>
     */
    private function groupAllDays(): array
    {
        $hours = $this->getOpeningHours();
        usort($hours, static fn (OpeningHour $a, OpeningHour $b) => $a->getDayOfWeek() <=> $b->getDayOfWeek());

        $groups = [];
        $i = 0;
        $n = count($hours);
        while ($i < $n) {
            $start = $hours[$i];
            $j = $i;
            while (
                $j + 1 < $n
                && $this->sameSlot($hours[$j + 1], $start)
                && $hours[$j + 1]->getDayOfWeek() === $hours[$j]->getDayOfWeek() + 1
            ) {
                ++$j;
            }
            $end = $hours[$j];
            $labelShort = $start->getDayOfWeek() === $end->getDayOfWeek()
                ? self::DAY_SHORT[$start->getDayOfWeek()]
                : self::DAY_SHORT[$start->getDayOfWeek()] . ' - ' . self::DAY_SHORT[$end->getDayOfWeek()];
            $labelLong = $start->getDayOfWeek() === $end->getDayOfWeek()
                ? self::DAY_LONG[$start->getDayOfWeek()]
                : self::DAY_LONG[$start->getDayOfWeek()] . ' - ' . self::DAY_LONG[$end->getDayOfWeek()];
            $groups[] = [
                'label_short' => $labelShort,
                'label_long' => $labelLong,
                'hours' => $start->isClosed() ? 'Fermé' : $this->formatTimeRange($start),
                'is_closed' => $start->isClosed(),
            ];
            $i = $j + 1;
        }

        return $groups;
    }

    private function sameSlot(OpeningHour $a, OpeningHour $b): bool
    {
        if ($a->isClosed() && $b->isClosed()) {
            return true;
        }
        if ($a->isClosed() || $b->isClosed()) {
            return false;
        }

        return $this->formatTimeRange($a) === $this->formatTimeRange($b);
    }

    private function formatTimeRange(OpeningHour $h): string
    {
        $fmt = static function (?\DateTimeInterface $t): string {
            if (!$t) {
                return '';
            }

            return ((int) $t->format('G')) . 'h' . $t->format('i');
        };

        return $fmt($h->getOpenTime()) . ' - ' . $fmt($h->getCloseTime());
    }
}
