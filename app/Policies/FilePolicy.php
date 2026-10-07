<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    public function delete(User $user, File $file): bool
    {
        return $user->isAdmin() || $file->user_id === $user->id;
    }
}
