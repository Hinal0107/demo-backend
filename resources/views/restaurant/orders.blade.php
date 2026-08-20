@extends('layouts.restaurant')

@section('title', 'Manage Orders - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">Orders Management</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Track delivery tickets and preparation pipelines</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h2 class="table-title">All Customer Orders</h2>
    </div>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Customer Name</th>
                    <th>Subtotal</th>
                    <th>Total Amount</th>
                    <th>Order Status</th>
                    <th>Delivery Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td style="font-weight: 600;">#{{ $order->order_number }}</td>
                        <td>{{ $order->customer->name ?? 'Guest Customer' }}</td>
                        <td>£{{ number_format($order->subtotal, 2) }}</td>
                        <td style="font-weight: 600;">£{{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            @php
                                $oStatusClass = 'badge-warning';
                                if($order->order_status === 'COMPLETED') $oStatusClass = 'badge-success';
                                elseif($order->order_status === 'CANCELLED') $oStatusClass = 'badge-danger';
                                elseif($order->order_status === 'PREPARING') $oStatusClass = 'badge-info';
                                elseif($order->order_status === 'CONFIRMED') $oStatusClass = 'badge-success';
                            @endphp
                            <span class="badge {{ $oStatusClass }}">{{ $order->order_status }}</span>
                        </td>
                        <td>
                            @php
                                $dStatusClass = 'badge-warning';
                                if($order->delivery_status === 'DELIVERED') $dStatusClass = 'badge-success';
                                elseif($order->delivery_status === 'OUT_FOR_DELIVERY') $dStatusClass = 'badge-info';
                            @endphp
                            <span class="badge {{ $dStatusClass }}">{{ str_replace('_', ' ', $order->delivery_status) }}</span>
                        </td>
                        <td>{{ $order->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <a href="{{ route('restaurant.orders.show', $order->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Manage Ticket</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                            No orders found for your restaurant.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
