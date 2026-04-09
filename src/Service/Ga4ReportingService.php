<?php

namespace App\Service;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Ga4ReportingService
{
    private const CACHE_TTL_SECONDS = 1800;
    private string $resolvedCredentialsPath;

    public function __construct(
        private readonly string $ga4PropertyId,
        private readonly string $ga4CredentialsPath,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
        $this->resolvedCredentialsPath = $this->resolveCredentialsPath($this->ga4CredentialsPath);
    }

    public function getOverview(string $period = '7d'): array
    {
        $normalizedPeriod = $this->normalizePeriod($period);
        $cacheKey = sprintf('ga4_dashboard_overview_%s', $normalizedPeriod);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($normalizedPeriod) {
            $item->expiresAfter(self::CACHE_TTL_SECONDS);

            try {
                return $this->fetchOverview($normalizedPeriod);
            } catch (\Throwable $exception) {
                $this->logger->error('Impossible de recuperer les statistiques GA4.', [
                    'period' => $normalizedPeriod,
                    'error' => $exception->getMessage(),
                ]);

                return $this->getEmptyPayload($normalizedPeriod);
            }
        });
    }

    private function fetchOverview(string $period): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($period);
        $property = sprintf('properties/%s', $this->ga4PropertyId);

        $client = new BetaAnalyticsDataClient([
            'credentials' => $this->resolvedCredentialsPath,
        ]);

        try {
            $dateRange = new DateRange([
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            $kpiReport = $client->runReport(
                new RunReportRequest([
                    'property' => $property,
                    'date_ranges' => [$dateRange],
                    'metrics' => [
                        new Metric(['name' => 'totalUsers']),
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'screenPageViews']),
                        new Metric(['name' => 'userEngagementDuration']),
                    ],
                ])
            );

            $trendReport = $client->runReport(
                new RunReportRequest([
                    'property' => $property,
                    'date_ranges' => [$dateRange],
                    'dimensions' => [new Dimension(['name' => 'date'])],
                    'metrics' => [
                        new Metric(['name' => 'totalUsers']),
                        new Metric(['name' => 'sessions']),
                    ],
                ])
            );

            $sourcesReport = $client->runReport(
                new RunReportRequest([
                    'property' => $property,
                    'date_ranges' => [$dateRange],
                    'dimensions' => [new Dimension(['name' => 'sessionDefaultChannelGroup'])],
                    'metrics' => [new Metric(['name' => 'sessions'])],
                    'limit' => 8,
                ])
            );

            $pagesReport = $client->runReport(
                new RunReportRequest([
                    'property' => $property,
                    'date_ranges' => [$dateRange],
                    'dimensions' => [new Dimension(['name' => 'pagePath'])],
                    'metrics' => [new Metric(['name' => 'screenPageViews'])],
                    'limit' => 8,
                ])
            );

            $campaignsReport = $client->runReport(
                new RunReportRequest([
                    'property' => $property,
                    'date_ranges' => [$dateRange],
                    'dimensions' => [
                        new Dimension(['name' => 'sessionCampaignName']),
                        new Dimension(['name' => 'sessionSourceMedium']),
                    ],
                    'metrics' => [new Metric(['name' => 'sessions'])],
                    'limit' => 8,
                ])
            );
        } finally {
            $client->close();
        }

        $kpis = $this->extractKpis($kpiReport->getRows());

        return [
            'period' => $period,
            'kpis' => $kpis,
            'trend' => $this->extractTrend($trendReport->getRows()),
            'sources' => $this->extractSimpleRows($sourcesReport->getRows(), 'label', 'sessions'),
            'topPages' => $this->extractSimpleRows($pagesReport->getRows(), 'path', 'views'),
            'campaigns' => $this->extractCampaignRows($campaignsReport->getRows()),
            'lastUpdatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param iterable<int, mixed> $rows
     */
    private function extractKpis(iterable $rows): array
    {
        foreach ($rows as $row) {
            $metrics = $row->getMetricValues();
            $engagementSeconds = (float) ($metrics[3]?->getValue() ?? 0);
            $sessions = (int) ($metrics[1]?->getValue() ?? 0);

            return [
                'visitors' => (int) ($metrics[0]?->getValue() ?? 0),
                'sessions' => $sessions,
                'pageViews' => (int) ($metrics[2]?->getValue() ?? 0),
                'avgEngagementSeconds' => $sessions > 0 ? (int) round($engagementSeconds / $sessions) : 0,
            ];
        }

        return [
            'visitors' => 0,
            'sessions' => 0,
            'pageViews' => 0,
            'avgEngagementSeconds' => 0,
        ];
    }

    /**
     * @param iterable<int, mixed> $rows
     */
    private function extractTrend(iterable $rows): array
    {
        $trend = [];

        foreach ($rows as $row) {
            $dateRaw = $row->getDimensionValues()[0]?->getValue() ?? '';
            if (strlen($dateRaw) !== 8) {
                continue;
            }

            $trend[] = [
                'date' => sprintf('%s-%s-%s', substr($dateRaw, 0, 4), substr($dateRaw, 4, 2), substr($dateRaw, 6, 2)),
                'visitors' => (int) ($row->getMetricValues()[0]?->getValue() ?? 0),
                'sessions' => (int) ($row->getMetricValues()[1]?->getValue() ?? 0),
            ];
        }

        usort($trend, static fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        return $trend;
    }

    /**
     * @param iterable<int, mixed> $rows
     */
    private function extractSimpleRows(iterable $rows, string $labelKey, string $valueKey): array
    {
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                $labelKey => $row->getDimensionValues()[0]?->getValue() ?? 'Non defini',
                $valueKey => (int) ($row->getMetricValues()[0]?->getValue() ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @param iterable<int, mixed> $rows
     */
    private function extractCampaignRows(iterable $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $name = $row->getDimensionValues()[0]?->getValue() ?? '(not set)';
            $sourceMedium = $row->getDimensionValues()[1]?->getValue() ?? '(not set)';

            if ($name === '(not set)' && $sourceMedium === '(not set)') {
                continue;
            }

            $result[] = [
                'name' => $name,
                'sourceMedium' => $sourceMedium,
                'sessions' => (int) ($row->getMetricValues()[0]?->getValue() ?? 0),
            ];
        }

        return $result;
    }

    private function normalizePeriod(string $period): string
    {
        $allowed = ['today', '7d', '30d'];

        return in_array($period, $allowed, true) ? $period : '7d';
    }

    private function resolveDateRange(string $period): array
    {
        return match ($period) {
            'today' => ['today', 'today'],
            '30d' => ['30daysAgo', 'today'],
            default => ['7daysAgo', 'today'],
        };
    }

    private function getEmptyPayload(string $period): array
    {
        return [
            'period' => $period,
            'kpis' => [
                'visitors' => 0,
                'sessions' => 0,
                'pageViews' => 0,
                'avgEngagementSeconds' => 0,
            ],
            'trend' => [],
            'sources' => [],
            'topPages' => [],
            'campaigns' => [],
            'lastUpdatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'error' => 'Les donnees GA4 ne sont pas disponibles pour le moment.',
        ];
    }

    private function resolveCredentialsPath(string $configuredPath): string
    {
        $projectDir = dirname(__DIR__, 2);
        $path = str_replace('%kernel.project_dir%', $projectDir, $configuredPath);

        if (!str_starts_with($path, '/') && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            $path = $projectDir . '/' . ltrim($path, '/\\');
        }

        return str_replace('\\', '/', $path);
    }
}
