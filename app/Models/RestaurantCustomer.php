<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantCustomer extends Model
{
    protected $fillable = [
        'restaurant_id',
        'customer_id',
        'status',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
