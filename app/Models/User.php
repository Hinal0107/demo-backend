<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'firebase_uid',
        'role',
        'profile_image',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Role verification helpers
    public function isSuperAdmin(): bool
    {
        return $this->role === 'SUPER_ADMIN';
    }

    public function isRestaurant(): bool
    {
        return $this->role === 'RESTAURANT';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'CUSTOMER';
    }

    // Relationships
    public function restaurantUsers()
    {
        return $this->hasMany(RestaurantUser::class, 'user_id');
    }

    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'restaurant_users', 'user_id', 'restaurant_id');
    }

    public function getRestaurantAttribute()
    {
        return $this->restaurants()->first();
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'customer_id');
    }

    public function userDevices()
    {
        return $this->hasMany(UserDevice::class, 'user_id');
    }
}
