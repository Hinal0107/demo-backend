<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'image',
        'price',
        'discount_price',
        'veg_type',
        'availability',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'availability' => 'boolean',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
    ];

    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereIn('status', ['ACTIVE', 'active'])->orWhereNull('status');
        });
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function getActivePriceAttribute()
    {
        return $this->discount_price !== null && $this->discount_price > 0 
            ? $this->discount_price 
            : $this->price;
    }
}
