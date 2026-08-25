<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyMeal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'date',
        'name',
        'description',
        'image',
        'price',
        'discount_price',
        'veg_type',
        'meal_type',
        'addons',
        'availability',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'availability' => 'boolean',
        'addons' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }
}
