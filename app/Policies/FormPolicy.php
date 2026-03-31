<?php

namespace App\Policies;

use App\Models\Form;
use App\Policies\Concerns\ResolvesAccountAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class FormPolicy
{
    use ResolvesAccountAuthorization;

    public function viewAny(Authenticatable $actor): bool
    {
        if (! $this->accessContextEnforced()) {
            return true;
        }

        if ($this->isAdminOverride()) {
            return true;
        }

        return $actor instanceof \App\Models\User;
    }

    public function create(Authenticatable $actor, string $accountId): bool
    {
        return $this->canWriteAccount($actor, $accountId);
    }

    public function view(Authenticatable $actor, Form $form): bool
    {
        return $this->canReadAccount($actor, $form->account_id);
    }

    public function update(Authenticatable $actor, Form $form): bool
    {
        return $this->canWriteAccount($actor, $form->account_id);
    }

    public function toggleActive(Authenticatable $actor, Form $form): bool
    {
        return $this->canWriteAccount($actor, $form->account_id);
    }
}
