<?php

namespace Jiny\Partner\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerPerformanceMetric;
use Exception;

class PartnerPerformanceService
{
    /**
     * Record performance metric for partner
     */
    public function recordMetric(string $partnerUuid, string $metricType, $metricValue, ?array $additionalData = null): void
    {
        try {
            PartnerPerformanceMetric::create([
                'partner_uuid' => $partnerUuid,
                'metric_type' => $metricType,
                'metric_value' => $metricValue,
                'period_type' => 'daily',
                'period_date' => now()->toDateString(),
                'additional_data' => $additionalData,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info("Performance metric recorded", [
                'partner_uuid' => $partnerUuid,
                'metric_type' => $metricType,
                'metric_value' => $metricValue
            ]);

        } catch (Exception $e) {
            Log::error("Failed to record performance metric", [
                'error' => $e->getMessage(),
                'partner_uuid' => $partnerUuid,
                'metric_type' => $metricType
            ]);
        }
    }

    /**
     * Record sales performance
     */
    public function recordSalesPerformance(string $partnerUuid, float $salesAmount, int $salesCount = 1, ?array $salesDetails = null): void
    {
        $this->recordMetric($partnerUuid, 'sales_amount', $salesAmount, [
            'sales_count' => $salesCount,
            'sales_details' => $salesDetails
        ]);

        $this->recordMetric($partnerUuid, 'sales_count', $salesCount, [
            'sales_amount' => $salesAmount,
            'sales_details' => $salesDetails
        ]);
    }

    /**
     * Record customer service performance
     */
    public function recordServicePerformance(string $partnerUuid, int $ticketsResolved, float $avgResolutionTime, float $satisfactionScore = null): void
    {
        $this->recordMetric($partnerUuid, 'tickets_resolved', $ticketsResolved, [
            'avg_resolution_time' => $avgResolutionTime,
            'satisfaction_score' => $satisfactionScore
        ]);

        if ($avgResolutionTime !== null) {
            $this->recordMetric($partnerUuid, 'avg_resolution_time', $avgResolutionTime, [
                'tickets_resolved' => $ticketsResolved,
                'satisfaction_score' => $satisfactionScore
            ]);
        }

        if ($satisfactionScore !== null) {
            $this->recordMetric($partnerUuid, 'satisfaction_score', $satisfactionScore, [
                'tickets_resolved' => $ticketsResolved,
                'avg_resolution_time' => $avgResolutionTime
            ]);
        }
    }

    /**
     * Record marketing performance
     */
    public function recordMarketingPerformance(string $partnerUuid, int $leadsGenerated, int $conversions, float $conversionRate): void
    {
        $this->recordMetric($partnerUuid, 'leads_generated', $leadsGenerated, [
            'conversions' => $conversions,
            'conversion_rate' => $conversionRate
        ]);

        $this->recordMetric($partnerUuid, 'conversions', $conversions, [
            'leads_generated' => $leadsGenerated,
            'conversion_rate' => $conversionRate
        ]);

        $this->recordMetric($partnerUuid, 'conversion_rate', $conversionRate, [
            'leads_generated' => $leadsGenerated,
            'conversions' => $conversions
        ]);
    }

    /**
     * Record training performance
     */
    public function recordTrainingPerformance(string $partnerUuid, int $sessionsCompleted, float $avgScore, int $participantsCount): void
    {
        $this->recordMetric($partnerUuid, 'training_sessions_completed', $sessionsCompleted, [
            'avg_score' => $avgScore,
            'participants_count' => $participantsCount
        ]);

        $this->recordMetric($partnerUuid, 'training_avg_score', $avgScore, [
            'sessions_completed' => $sessionsCompleted,
            'participants_count' => $participantsCount
        ]);

        $this->recordMetric($partnerUuid, 'training_participants', $participantsCount, [
            'sessions_completed' => $sessionsCompleted,
            'avg_score' => $avgScore
        ]);
    }

    /**
     * Get performance summary for partner
     */
    public function getPerformanceSummary(string $partnerUuid, string $periodType = 'monthly', int $periodCount = 12): array
    {
        try {
            $startDate = $this->getStartDateForPeriod($periodType, $periodCount);

            $metrics = PartnerPerformanceMetric::where('partner_uuid', $partnerUuid)
                ->where('created_at', '>=', $startDate)
                ->get();

            return [
                'partner_uuid' => $partnerUuid,
                'period_type' => $periodType,
                'period_count' => $periodCount,
                'start_date' => $startDate->toDateString(),
                'end_date' => now()->toDateString(),
                'metrics' => $this->groupMetricsByType($metrics),
                'summary' => $this->calculateSummaryStatistics($metrics),
                'trends' => $this->calculateTrends($metrics, $periodType)
            ];

        } catch (Exception $e) {
            Log::error("Failed to get performance summary", [
                'error' => $e->getMessage(),
                'partner_uuid' => $partnerUuid,
                'period_type' => $periodType
            ]);
            return [];
        }
    }

    /**
     * Get partner performance rankings
     */
    public function getPerformanceRankings(string $metricType, string $periodType = 'monthly', int $limit = 10): array
    {
        try {
            $startDate = $this->getStartDateForPeriod($periodType, 1);

            $rankings = PartnerPerformanceMetric::select('partner_uuid')
                ->selectRaw('SUM(metric_value) as total_value')
                ->selectRaw('AVG(metric_value) as avg_value')
                ->selectRaw('COUNT(*) as metric_count')
                ->where('metric_type', $metricType)
                ->where('created_at', '>=', $startDate)
                ->groupBy('partner_uuid')
                ->orderBy('total_value', 'desc')
                ->limit($limit)
                ->get();

            // Enrich with partner information
            $enrichedRankings = [];
            foreach ($rankings as $ranking) {
                $partner = PartnerUser::where('user_uuid', $ranking->partner_uuid)->first();

                $enrichedRankings[] = [
                    'partner_uuid' => $ranking->partner_uuid,
                    'partner_name' => $partner->name ?? 'Unknown',
                    'partner_tier' => $partner->tier_name ?? 'Bronze',
                    'total_value' => $ranking->total_value,
                    'avg_value' => $ranking->avg_value,
                    'metric_count' => $ranking->metric_count
                ];
            }

            return [
                'metric_type' => $metricType,
                'period_type' => $periodType,
                'start_date' => $startDate->toDateString(),
                'rankings' => $enrichedRankings
            ];

        } catch (Exception $e) {
            Log::error("Failed to get performance rankings", [
                'error' => $e->getMessage(),
                'metric_type' => $metricType,
                'period_type' => $periodType
            ]);
            return [];
        }
    }

    /**
     * Calculate tier promotion eligibility
     */
    public function calculateTierPromotionEligibility(string $partnerUuid): array
    {
        try {
            $partner = PartnerUser::where('user_uuid', $partnerUuid)->first();
            if (!$partner) {
                return ['eligible' => false, 'reason' => 'Partner not found'];
            }

            $currentTier = $partner->tier_name ?? 'Bronze';
            $summary = $this->getPerformanceSummary($partnerUuid, 'monthly', 6);

            $requirements = $this->getTierRequirements($currentTier);
            $eligibility = $this->checkTierRequirements($summary['metrics'], $requirements);

            return [
                'partner_uuid' => $partnerUuid,
                'current_tier' => $currentTier,
                'next_tier' => $this->getNextTier($currentTier),
                'eligible' => $eligibility['eligible'],
                'requirements_met' => $eligibility['requirements_met'],
                'requirements_needed' => $eligibility['requirements_needed'],
                'performance_summary' => $summary['summary']
            ];

        } catch (Exception $e) {
            Log::error("Failed to calculate tier promotion eligibility", [
                'error' => $e->getMessage(),
                'partner_uuid' => $partnerUuid
            ]);
            return ['eligible' => false, 'reason' => 'Calculation error'];
        }
    }

    /**
     * Get system-wide performance statistics
     */
    public function getSystemPerformanceStats(string $periodType = 'monthly'): array
    {
        try {
            $startDate = $this->getStartDateForPeriod($periodType, 1);

            $stats = [
                'period' => [
                    'type' => $periodType,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => now()->toDateString()
                ],
                'total_partners' => PartnerUser::count(),
                'active_partners' => $this->getActivePartnersCount($startDate),
                'metrics_summary' => $this->getMetricsSummaryByType($startDate),
                'tier_distribution' => $this->getTierDistribution(),
                'top_performers' => [
                    'sales' => $this->getPerformanceRankings('sales_amount', $periodType, 5),
                    'service' => $this->getPerformanceRankings('tickets_resolved', $periodType, 5),
                    'marketing' => $this->getPerformanceRankings('leads_generated', $periodType, 5)
                ]
            ];

            return $stats;

        } catch (Exception $e) {
            Log::error("Failed to get system performance stats", [
                'error' => $e->getMessage(),
                'period_type' => $periodType
            ]);
            return [];
        }
    }

    /**
     * Helper methods
     */
    private function getStartDateForPeriod(string $periodType, int $periodCount): \Carbon\Carbon
    {
        switch ($periodType) {
            case 'daily':
                return now()->subDays($periodCount);
            case 'weekly':
                return now()->subWeeks($periodCount);
            case 'monthly':
                return now()->subMonths($periodCount);
            case 'yearly':
                return now()->subYears($periodCount);
            default:
                return now()->subMonths($periodCount);
        }
    }

    private function groupMetricsByType($metrics): array
    {
        $grouped = [];
        foreach ($metrics as $metric) {
            $type = $metric->metric_type;
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = [
                'value' => $metric->metric_value,
                'date' => $metric->period_date,
                'additional_data' => $metric->additional_data
            ];
        }
        return $grouped;
    }

    private function calculateSummaryStatistics($metrics): array
    {
        $summary = [];
        $groupedMetrics = $this->groupMetricsByType($metrics);

        foreach ($groupedMetrics as $type => $values) {
            $numericValues = array_column($values, 'value');
            $summary[$type] = [
                'total' => array_sum($numericValues),
                'average' => count($numericValues) > 0 ? array_sum($numericValues) / count($numericValues) : 0,
                'count' => count($numericValues),
                'max' => count($numericValues) > 0 ? max($numericValues) : 0,
                'min' => count($numericValues) > 0 ? min($numericValues) : 0
            ];
        }

        return $summary;
    }

    private function calculateTrends($metrics, $periodType): array
    {
        // Basic trend calculation - could be enhanced with more sophisticated algorithms
        $trends = [];
        $groupedMetrics = $this->groupMetricsByType($metrics);

        foreach ($groupedMetrics as $type => $values) {
            if (count($values) < 2) {
                $trends[$type] = 'insufficient_data';
                continue;
            }

            $recent = array_slice($values, -30); // Last 30 data points
            $older = array_slice($values, 0, -30);

            if (empty($older)) {
                $trends[$type] = 'insufficient_data';
                continue;
            }

            $recentAvg = array_sum(array_column($recent, 'value')) / count($recent);
            $olderAvg = array_sum(array_column($older, 'value')) / count($older);

            $changePercent = $olderAvg > 0 ? (($recentAvg - $olderAvg) / $olderAvg) * 100 : 0;

            if ($changePercent > 10) {
                $trends[$type] = 'increasing';
            } elseif ($changePercent < -10) {
                $trends[$type] = 'decreasing';
            } else {
                $trends[$type] = 'stable';
            }
        }

        return $trends;
    }

    private function getTierRequirements(string $currentTier): array
    {
        $requirements = [
            'Bronze' => [
                'sales_amount' => 500000,
                'tickets_resolved' => 50,
                'satisfaction_score' => 4.0
            ],
            'Silver' => [
                'sales_amount' => 1500000,
                'tickets_resolved' => 150,
                'satisfaction_score' => 4.5
            ],
            'Gold' => [
                'sales_amount' => 3000000,
                'tickets_resolved' => 300,
                'satisfaction_score' => 4.7
            ]
        ];

        return $requirements[$currentTier] ?? [];
    }

    private function getNextTier(string $currentTier): ?string
    {
        $tiers = ['Bronze' => 'Silver', 'Silver' => 'Gold', 'Gold' => 'Platinum'];
        return $tiers[$currentTier] ?? null;
    }

    private function checkTierRequirements(array $metrics, array $requirements): array
    {
        $metSummary = [];
        $neededSummary = [];
        $allMet = true;

        foreach ($requirements as $requirement => $threshold) {
            $currentValue = $metrics[$requirement]['total'] ?? 0;
            $met = $currentValue >= $threshold;

            $metSummary[$requirement] = [
                'required' => $threshold,
                'current' => $currentValue,
                'met' => $met
            ];

            if (!$met) {
                $allMet = false;
                $neededSummary[$requirement] = $threshold - $currentValue;
            }
        }

        return [
            'eligible' => $allMet,
            'requirements_met' => $metSummary,
            'requirements_needed' => $neededSummary
        ];
    }

    private function getActivePartnersCount($startDate): int
    {
        return PartnerPerformanceMetric::where('created_at', '>=', $startDate)
            ->distinct('partner_uuid')
            ->count('partner_uuid');
    }

    private function getMetricsSummaryByType($startDate): array
    {
        return PartnerPerformanceMetric::select('metric_type')
            ->selectRaw('SUM(metric_value) as total_value')
            ->selectRaw('AVG(metric_value) as avg_value')
            ->selectRaw('COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('metric_type')
            ->get()
            ->pluck('total_value', 'metric_type')
            ->toArray();
    }

    private function getTierDistribution(): array
    {
        return PartnerUser::select('tier_name')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('tier_name')
            ->pluck('count', 'tier_name')
            ->toArray();
    }
}