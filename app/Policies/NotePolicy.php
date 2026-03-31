<?php

namespace App\Policies;

use App\Models\Enquiry;
use App\Models\Note;
use App\Policies\Concerns\ResolvesAccountAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class NotePolicy
{
    use ResolvesAccountAuthorization;

    public function view(Authenticatable $actor, Note $note): bool
    {
        return $this->canReadAccount($actor, (string) $note->enquiry?->account_id);
    }

    public function createForEnquiry(Authenticatable $actor, Enquiry $enquiry): bool
    {
        return $this->canWriteAccount($actor, $enquiry->account_id);
    }
}
