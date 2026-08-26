<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'addon_id',
        'daily_meal_id',
        'item_name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }

    public function dailyMeal()
    {
        return $this->belongsTo(DailyMeal::class, 'daily_meal_id');
    }
}
