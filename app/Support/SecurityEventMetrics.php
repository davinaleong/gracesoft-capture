<?php

namespace App\Support;

use App\Models\SecurityEventSnapshot;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class SecurityEventMetrics
{
    public function incrementVerificationBlocked(string $guard, string $scope): void
    {
        $key = $this->counterKey('verification_blocked', $guard, $scope, now());

        Cache::add($key, 0, now()->addDays(2));
        Cache::increment($key);
    }

    /**
     * @return array{total:int, breakdown:array<string, int>}
     */
    public function verificationBlockedSummary(?CarbonInterface $date = null): array
    {
        $date ??= now();

        $breakdown = [];
        $total = 0;

        foreach ($this->verificationBlockedPairs() as [$guard, $scope]) {
            $value = (int) Cache::get($this->counterKey('verification_blocked', $guard, $scope, $date), 0);
            $breakdown[$guard . ':' . $scope] = $value;
            $total += $value;
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * @return array{date:string, rows_written:int, total:int, breakdown:array<string, int>}
     */
    public function persistVerificationBlockedSnapshot(?CarbonInterface $date = null): array
    {
        $targetDate = ($date ?? now())->copy()->startOfDay();
        $summary = $this->verificationBlockedSummary($targetDate);
        $rowsWritten = 0;

        foreach ($summary['breakdown'] as $scope => $count) {
            SecurityEventSnapshot::query()->updateOrCreate(
                [
                    'snapshot_date' => $targetDate->toDateString(),
                    'metric_key' => 'verification_blocked:' . $scope,
                ],
                [
                    'metric_value' => $count,
                    'metadata' => [
                        'source' => 'cache_counter',
                    ],
                ]
            );

            $rowsWritten++;
        }

        return [
            'date' => $targetDate->toDateString(),
            'rows_written' => $rowsWritten,
            'total' => $summary['total'],
            'breakdown' => $summary['breakdown'],
        ];
    }

    private function counterKey(string $event, string $guard, string $scope, CarbonInterface $date): string
    {
        return implode(':', [
            'capture',
            'security',
            'metric',
            $event,
            $date->format('Ymd'),
            $guard,
            $scope,
        ]);
    }

    /**
     * @return array<int, array{string, string}>
     */
    private function verificationBlockedPairs(): array
    {
        return [
            ['web', 'collaborator_acceptance'],
            ['admin', 'sensitive_admin_operation'],
        ];
    }
}
