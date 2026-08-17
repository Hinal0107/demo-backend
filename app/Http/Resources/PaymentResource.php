<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'restaurant_id' => $this->restaurant_id,
            'customer_id' => $this->customer_id,
            'worldpay_transaction_id' => $this->worldpay_transaction_id,
            'worldpay_reference' => $this->worldpay_reference,
            'amount' => (float)$this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'gateway_response_code' => $this->gateway_response_code,
            'refund_amount' => $this->refund_amount ? (float)$this->refund_amount : null,
            'refund_reason' => $this->refund_reason,
            'refund_status' => $this->refund_status,
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'failed_at' => $this->failed_at?->toDateTimeString(),
            'refunded_at' => $this->refunded_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
