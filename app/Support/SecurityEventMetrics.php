<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class SecurityEventMetrics
{
    public function incrementVerificationBlocked(string $guard, string $scope): void
    {
        $key = $this->counterKey('verification_blocked', $guard, $scope);

        Cache::add($key, 0, now()->addDays(2));
        Cache::increment($key);
    }

    /**
     * @return array{total:int, breakdown:array<string, int>}
     */
    public function verificationBlockedSummary(): array
    {
        $pairs = [
            ['web', 'collaborator_acceptance'],
            ['admin', 'sensitive_admin_operation'],
        ];

        $breakdown = [];
        $total = 0;

        foreach ($pairs as [$guard, $scope]) {
            $value = (int) Cache::get($this->counterKey('verification_blocked', $guard, $scope), 0);
            $breakdown[$guard . ':' . $scope] = $value;
            $total += $value;
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown,
        ];
    }

    private function counterKey(string $event, string $guard, string $scope): string
    {
        return implode(':', [
            'capture',
            'security',
            'metric',
            $event,
            now()->format('Ymd'),
            $guard,
            $scope,
        ]);
    }
}
