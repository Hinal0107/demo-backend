<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantUser extends Model
{
    protected $fillable = [
        'restaurant_id',
        'user_id',
        'role',
        'status',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
