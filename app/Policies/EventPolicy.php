<?php

namespace App\Policies;

use App\Models\MotoringEvent;
use App\Models\User;

class EventPolicy
{
    public function update(User $user, MotoringEvent $event): bool
    {
        return $event->user_id === $user->id;
    }

    public function review(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function evidence(User $user, MotoringEvent $event): bool
    {
        return $this->review($user);
    }
}
