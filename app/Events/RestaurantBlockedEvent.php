<?php

namespace App\Events;

use App\Models\Restaurant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestaurantBlockedEvent
{
    use Dispatchable, SerializesModels;

    public Restaurant $restaurant;

    public function __construct(Restaurant $restaurant)
    {
        $this->restaurant = $restaurant;
    }
}
