<?php

namespace App\Services;

use App\Models\Enquiry;
use Illuminate\Support\Carbon;

class InsightsService
{
    /**
     * @return array<string, mixed>
     */
    public function summaryForAccount(string $accountId, int $days): array
    {
        $days = max($days, 1);
        $startDate = now()->startOfDay()->subDays($days - 1);

        $baseQuery = Enquiry::query()
            ->where('account_id', $accountId);

        $totalEnquiries = (clone $baseQuery)->count();

        $respondedCount = (clone $baseQuery)
            ->whereNotNull('contacted_at')
            ->count();

        $conversionRatePercent = $totalEnquiries > 0
            ? round(($respondedCount / $totalEnquiries) * 100, 1)
            : 0.0;

        $avgFirstResponseSeconds = (float) (clone $baseQuery)
            ->whereNotNull('contacted_at')
            ->get(['created_at', 'contacted_at'])
            ->avg(function (Enquiry $enquiry): float {
                $createdAt = $enquiry->created_at;
                $contactedAt = $enquiry->contacted_at;

                if (! $createdAt instanceof Carbon || ! $contactedAt instanceof Carbon) {
                    return 0.0;
                }

                return max($createdAt->diffInSeconds($contactedAt), 0);
            });

        $avgFirstResponseMinutes = round($avgFirstResponseSeconds / 60, 1);

        $dailyBuckets = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $day = (clone $startDate)->addDays($offset)->toDateString();
            $dailyBuckets[$day] = 0;
        }

        $dailyCounts = (clone $baseQuery)
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->get(['created_at'])
            ->groupBy(fn (Enquiry $enquiry): string => $enquiry->created_at->toDateString())
            ->map(fn ($group): int => $group->count())
            ->all();

        foreach ($dailyCounts as $date => $count) {
            if (array_key_exists($date, $dailyBuckets)) {
                $dailyBuckets[$date] = $count;
            }
        }

        return [
            'total_enquiries' => $totalEnquiries,
            'conversion_rate_percent' => $conversionRatePercent,
            'avg_first_response_minutes' => $avgFirstResponseMinutes,
            'daily_enquiries' => collect($dailyBuckets)
                ->map(fn (int $count, string $date): array => [
                    'date' => $date,
                    'count' => $count,
                ])
                ->values()
                ->all(),
        ];
    }
}
