<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Restaurant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'logo',
        'description',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'latitude',
        'longitude',
        'delivery_radius_km',
        'opening_time',
        'closing_time',
        'bank_account_holder',
        'bank_account_number',
        'bank_ifsc',
        'bank_branch',
        'status',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeNearby($query, $latitude, $longitude, $maxDistanceKm = null)
    {
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        return $query->select('*')
            ->selectRaw("{$haversine} AS distance", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("{$haversine} <= COALESCE(?, delivery_radius_km)", [$latitude, $longitude, $latitude, $maxDistanceKm])
            ->orderBy('distance');
    }

    // Relationships
    public function restaurantUsers()
    {
        return $this->hasMany(RestaurantUser::class, 'restaurant_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'restaurant_users', 'restaurant_id', 'user_id');
    }

    public function menuCategories()
    {
        return $this->hasMany(MenuCategory::class, 'restaurant_id');
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'restaurant_id');
    }

    public function subscriptionPlans()
    {
        return $this->hasMany(SubscriptionPlan::class, 'restaurant_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'restaurant_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'restaurant_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'restaurant_id');
    }

    public function dailyMeals()
    {
        return $this->hasMany(DailyMeal::class, 'restaurant_id');
    }

    public function addons()
    {
        return $this->hasMany(Addon::class, 'restaurant_id');
    }

    public function taxes()
    {
        return $this->hasMany(Tax::class, 'restaurant_id');
    }
}
