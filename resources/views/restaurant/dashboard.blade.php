@extends('layouts.restaurant')

@section('title', 'Restaurant Dashboard - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Overview statistics and updates for your kitchen</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-title">Today's Orders</span>
        <span class="stat-value">{{ $stats['today_orders'] }}</span>
    </div>
    <div class="stat-card">
        <span class="stat-title">Total Revenue</span>
        <span class="stat-value">£{{ number_format($stats['total_revenue'], 2) }}</span>
    </div>
    <div class="stat-card">
        <span class="stat-title">Active Menu Items</span>
        <span class="stat-value">{{ $stats['active_menu_items'] }}</span>
    </div>
    <div class="stat-card">
        <span class="stat-title">Active Subscriptions</span>
        <span class="stat-value">{{ $stats['active_subscriptions'] }}</span>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h2 class="table-title">Recent Orders</h2>
        <a href="{{ route('restaurant.orders.index') }}" class="btn btn-secondary">View All Orders</a>
    </div>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Customer Name</th>
                    <th>Subtotal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                    <tr>
                        <td style="font-weight: 600;">#{{ $order->order_number }}</td>
                        <td>{{ $order->customer->name ?? 'Guest Customer' }}</td>
                        <td>£{{ number_format($order->subtotal, 2) }}</td>
                        <td style="font-weight: 600;">£{{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            @php
                                $statusClass = 'badge-warning';
                                if(in_array($order->order_status, ['COMPLETED', 'DELIVERED'])) $statusClass = 'badge-success';
                                elseif($order->order_status === 'CANCELLED') $statusClass = 'badge-danger';
                                elseif($order->order_status === 'PREPARING') $statusClass = 'badge-info';
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ $order->order_status }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $order->payment_status === 'PAID' ? 'badge-success' : 'badge-warning' }}">
                                {{ $order->payment_status }}
                            </span>
                        </td>
                        <td>{{ $order->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <a href="{{ route('restaurant.orders.show', $order->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                            No recent orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
