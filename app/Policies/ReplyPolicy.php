<?php

namespace App\Policies;

use App\Models\Enquiry;
use App\Models\Reply;
use App\Policies\Concerns\ResolvesAccountAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class ReplyPolicy
{
    use ResolvesAccountAuthorization;

    public function view(Authenticatable $actor, Reply $reply): bool
    {
        return $this->canReadAccount($actor, $reply->account_id);
    }

    public function createForEnquiry(Authenticatable $actor, Enquiry $enquiry): bool
    {
        return $this->canWriteAccount($actor, $enquiry->account_id);
    }
}
