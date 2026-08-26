<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'restaurant_id',
        'customer_id',
        'subscription_plan_id',
        'total_meals',
        'used_meals',
        'remaining_meals',
        'max_validity_days',
        'max_validity_date',
        'start_date',
        'end_date',
        'price',
        'payment_status',
        'status',
        'expiration_reason',
        'auto_renew',
        'cancelled_at',
    ];

    protected $casts = [
        'total_meals' => 'integer',
        'used_meals' => 'integer',
        'remaining_meals' => 'integer',
        'max_validity_days' => 'integer',
        'max_validity_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
        'auto_renew' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function daysUntilExpiry(): int
    {
        $expiryDate = $this->max_validity_date ?? $this->end_date;
        if (!$expiryDate) {
            return 0;
        }

        $today = \Carbon\Carbon::today();
        if ($today->isAfter($expiryDate)) {
            return 0;
        }

        return (int)$today->diffInDays($expiryDate, false);
    }

    public function isExpired(): bool
    {
        if ($this->status === 'EXPIRED' || $this->status === 'COMPLETED' || $this->status === 'CANCELLED') {
            return true;
        }

        $expiryDate = $this->max_validity_date ?? $this->end_date;
        if ($expiryDate && \Carbon\Carbon::today()->isAfter($expiryDate)) {
            return true;
        }

        if ($this->remaining_meals <= 0) {
            return true;
        }

        return false;
    }

    public function checkAndAutoExpire(): bool
    {
        if ($this->status !== 'ACTIVE') {
            return false;
        }

        $expiryDate = $this->max_validity_date ?? $this->end_date;
        $isPastExpiryDate = $expiryDate && \Carbon\Carbon::today()->isAfter($expiryDate);
        $noMealsLeft = $this->remaining_meals <= 0;

        if ($isPastExpiryDate || $noMealsLeft) {
            $this->status = 'EXPIRED';
            $this->remaining_meals = 0;
            $this->expiration_reason = $noMealsLeft ? 'MEALS_EXHAUSTED' : 'MAX_VALIDITY_EXCEEDED';
            $this->save();
            return true;
        }

        return false;
    }

    public function getExpiryReminderMessage(): ?string
    {
        if ($this->status !== 'ACTIVE') {
            return null;
        }

        $days = $this->daysUntilExpiry();

        if ($days <= 2 && $days >= 0) {
            if ($days === 0) {
                return "Your plan will expire today.";
            } elseif ($days === 1) {
                return "Your plan will expire in 1 day.";
            } else {
                return "Your plan will expire in 2 days.";
            }
        }

        return null;
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function subscriptionOrders()
    {
        return $this->hasMany(SubscriptionOrder::class, 'subscription_id');
    }
}
