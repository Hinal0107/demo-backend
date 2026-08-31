<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'image',
        'sort_order',
        'status',
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

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'category_id');
    }
}
