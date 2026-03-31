<?php

namespace App\Policies;

use App\Models\Enquiry;
use App\Policies\Concerns\ResolvesAccountAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class EnquiryPolicy
{
    use ResolvesAccountAuthorization;

    public function view(Authenticatable $actor, Enquiry $enquiry): bool
    {
        return $this->canReadAccount($actor, $enquiry->account_id);
    }

    public function updateStatus(Authenticatable $actor, Enquiry $enquiry): bool
    {
        return $this->canWriteAccount($actor, $enquiry->account_id);
    }

    public function createNote(Authenticatable $actor, Enquiry $enquiry): bool
    {
        return $this->canWriteAccount($actor, $enquiry->account_id);
    }

    public function createReply(Authenticatable $actor, Enquiry $enquiry): bool
    {
        return $this->canWriteAccount($actor, $enquiry->account_id);
    }
}
