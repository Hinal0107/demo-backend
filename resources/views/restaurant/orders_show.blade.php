@extends('layouts.restaurant')

@section('title', 'Manage Order #' . $order->order_number . ' - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">Order #{{ $order->order_number }}</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Manage delivery lifecycle and preparation states</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
    
    <!-- Order Details & Items -->
    <div>
        <div class="table-container" style="padding: 25px; margin-bottom: 30px;">
            <h2 class="table-title" style="margin-bottom: 20px;">Order Summary</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; font-size: 14px;">
                <div>
                    <p style="color: var(--text-secondary); font-weight: 600; margin-bottom: 5px;">CUSTOMER CONTACT</p>
                    <p style="font-weight: 600;">{{ $order->customer->name ?? 'Guest Customer' }}</p>
                    <p style="color: var(--text-secondary);">{{ $order->customer->email ?? 'N/A' }}</p>
                    <p style="color: var(--text-secondary);">{{ $order->customer->phone ?? 'N/A' }}</p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); font-weight: 600; margin-bottom: 5px;">DELIVERY ADDRESS</p>
                    @if($order->address)
                        <p>{{ $order->address->address_line_1 }}</p>
                        @if($order->address->address_line_2) <p>{{ $order->address->address_line_2 }}</p> @endif
                        <p>{{ $order->address->city }}, {{ $order->address->state }} - {{ $order->address->pincode }}</p>
                    @else
                        <p style="color: var(--text-secondary);">No delivery address assigned.</p>
                    @endif
                </div>
            </div>

            @if($order->notes)
                <div style="background-color: rgba(249, 115, 22, 0.05); border: 1px solid rgba(249, 115, 22, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                    <strong style="color: var(--accent-primary);">Special Instructions:</strong> {{ $order->notes }}
                </div>
            @endif

            <table style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Menu Item</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderItems as $item)
                        <tr>
                            <td>
                                <div style="font-weight: 600;">{{ $item->item_name }}</div>
                            </td>
                            <td>£{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td style="text-align: right; font-weight: 600;">£{{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr style="border-top: 2px solid var(--border-color);">
                        <td colspan="3" style="text-align: right; font-weight: 600; padding-top: 15px;">Subtotal:</td>
                        <td style="text-align: right; font-weight: 600; padding-top: 15px;">£{{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 600; padding-top: 5px;">Delivery Fee:</td>
                        <td style="text-align: right; font-weight: 600; padding-top: 5px;">£{{ number_format($order->delivery_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 600; padding-top: 5px;">Tax:</td>
                        <td style="text-align: right; font-weight: 600; padding-top: 5px;">£{{ number_format($order->tax, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 700; padding-top: 10px; font-size: 16px;">Grand Total:</td>
                        <td style="text-align: right; font-weight: 700; padding-top: 10px; font-size: 16px; color: var(--accent-primary);">£{{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Status Managers Panel -->
    <div>
        <div class="table-container" style="padding: 25px;">
            <h2 class="table-title" style="margin-bottom: 20px;">Manage Status</h2>
            
            <form action="{{ route('restaurant.orders.status', $order->id) }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="order_status">Order Status</label>
                    <select class="form-control" name="order_status" id="order_status" required>
                        <option value="PENDING_PAYMENT" {{ $order->order_status === 'PENDING_PAYMENT' ? 'selected' : '' }} disabled>PENDING PAYMENT</option>
                        <option value="CONFIRMED" {{ $order->order_status === 'CONFIRMED' ? 'selected' : '' }}>CONFIRMED (Accept)</option>
                        <option value="PREPARING" {{ $order->order_status === 'PREPARING' ? 'selected' : '' }}>PREPARING (In Kitchen)</option>
                        <option value="READY" {{ $order->order_status === 'READY' ? 'selected' : '' }}>READY FOR PICKUP/DELIVERY</option>
                        <option value="COMPLETED" {{ $order->order_status === 'COMPLETED' ? 'selected' : '' }} disabled>COMPLETED (Delivered)</option>
                        <option value="CANCELLED" {{ $order->order_status === 'CANCELLED' ? 'selected' : '' }}>CANCELLED (Reject)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="delivery_status">Delivery Status</label>
                    <select class="form-control" name="delivery_status" id="delivery_status">
                        <option value="PENDING" {{ $order->delivery_status === 'PENDING' ? 'selected' : '' }}>PENDING</option>
                        <option value="OUT_FOR_DELIVERY" {{ $order->delivery_status === 'OUT_FOR_DELIVERY' ? 'selected' : '' }}>OUT FOR DELIVERY</option>
                        <option value="DELIVERED" {{ $order->delivery_status === 'DELIVERED' ? 'selected' : '' }}>DELIVERED</option>
                    </select>
                </div>

                @if($order->delivery_otp)
                    <div style="background-color: rgba(22, 163, 74, 0.05); border: 1px solid rgba(22, 163, 74, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center;">
                        <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Handover OTP</span>
                        <strong style="color: var(--success); font-size: 22px; letter-spacing: 2px;">{{ $order->delivery_otp }}</strong>
                    </div>
                @endif

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Order State</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
