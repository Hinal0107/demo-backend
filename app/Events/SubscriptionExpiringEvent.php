<?php

namespace App\Events;

use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringEvent
{
    use Dispatchable, SerializesModels;

    public Subscription $subscription;
    public int $daysLeft;

    public function __construct(Subscription $subscription, int $daysLeft)
    {
        $this->subscription = $subscription;
        $this->daysLeft = $daysLeft;
    }
}
