<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    protected $fillable = [
        'event_id',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'json',
        'processed_at' => 'datetime',
    ];
}
