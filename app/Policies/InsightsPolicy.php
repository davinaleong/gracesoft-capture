<?php

namespace App\Policies;

use App\Policies\Concerns\ResolvesAccountAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class InsightsPolicy
{
    use ResolvesAccountAuthorization;

    public function viewForAccount(Authenticatable $actor, string $accountId): bool
    {
        return $this->canReadAccount($actor, $accountId);
    }
}
