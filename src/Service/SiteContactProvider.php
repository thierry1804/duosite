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
        $parts = [];
        foreach ($this->groupOpenDays() as $g) {
            $parts[] = sprintf('%s: %s', $g['label_short'], $g['hours']);
        }

        return implode(' · ', $parts);
    }

    /** @return list<string> */
    public function formatContactHoursLines(): array
    {
        $lines = [];
        foreach ($this->groupOpenDays() as $g) {
            $lines[] = sprintf('%s: %s', $g['label_long'], $g['hours']);
        }

        return $lines;
    }

    /** @return list<array{label_short: string, label_long: string, hours: string, is_closed: bool}> */
    private function groupOpenDays(): array
    {
        return array_values(array_filter(
            $this->groupAllDays(),
            static fn (array $g) => !$g['is_closed']
        ));
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

        return $this->normalizeTime($a->getOpenTime()) === $this->normalizeTime($b->getOpenTime())
            && $this->normalizeTime($a->getCloseTime()) === $this->normalizeTime($b->getCloseTime());
    }

    private function formatTimeRange(OpeningHour $h): string
    {
        $open = $this->normalizeTime($h->getOpenTime());
        $close = $this->normalizeTime($h->getCloseTime());
        if ($open === null || $close === null) {
            return '';
        }

        return $this->formatHi($open) . ' - ' . $this->formatHi($close);
    }

    private function normalizeTime(?\DateTimeInterface $t): ?string
    {
        return $t?->format('H:i');
    }

    private function formatHi(string $hi): string
    {
        [$h, $m] = array_pad(explode(':', $hi, 2), 2, '00');

        return ((int) $h) . 'h' . $m;
    }
}
