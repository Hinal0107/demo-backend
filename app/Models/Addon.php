<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Addon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'price',
        'image',
        'availability',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'availability' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'addon_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'addon_id');
    }
}
