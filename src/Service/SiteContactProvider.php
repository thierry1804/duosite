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

    /** @return list<string> Une ligne par jour ouvré (libellé court) */
    public function formatFooterHoursLines(): array
    {
        return $this->formatOpenDayLines(true);
    }

    /** @return list<string> Une ligne par jour ouvré (libellé long) */
    public function formatContactHoursLines(): array
    {
        return $this->formatOpenDayLines(false);
    }

    /**
     * @return list<string>
     */
    private function formatOpenDayLines(bool $short): array
    {
        $hours = $this->getOpeningHours();
        usort($hours, static fn (OpeningHour $a, OpeningHour $b) => $a->getDayOfWeek() <=> $b->getDayOfWeek());

        $lines = [];
        foreach ($hours as $hour) {
            if ($hour->isClosed()) {
                continue;
            }
            $range = $this->formatTimeRange($hour);
            if ($range === '') {
                continue;
            }
            $day = $hour->getDayOfWeek();
            $label = $short
                ? (self::DAY_SHORT[$day] ?? (string) $day)
                : (self::DAY_LONG[$day] ?? (string) $day);
            $lines[] = sprintf('%s: %s', $label, $range);
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
