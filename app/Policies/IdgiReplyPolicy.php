<?php

namespace App\Policies;

use App\Models\IdgiReply;
use App\Models\User;

/**
 * Who may handle readers' "I don't get it" replies.
 */
class IdgiReplyPolicy
{
    /**
     * Anyone who can edit the term marks its replies dealt with, or new again.
     */
    public function update(User $user, IdgiReply $reply): bool
    {
        return $user->canEdit($reply->term);
    }

    /**
     * Only administrators delete replies, for spam.
     */
    public function delete(User $user, IdgiReply $reply): bool
    {
        return (bool) $user->is_admin;
    }
}
