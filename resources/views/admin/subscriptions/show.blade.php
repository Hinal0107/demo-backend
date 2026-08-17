@extends('layouts.admin')

@section('title', 'Subscription #' . $subscription->id . ' - Details')

@section('content')
    <div class="header-section">
        <div>
            <a href="{{ route('admin.subscriptions.index') }}" style="color: var(--accent-primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Back to Subscriptions</a>
            <h1 class="page-title" style="margin-top: 10px;">Subscription Plan Details</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Subscription ID: #{{ $subscription->id }} &bull; Plan Name: {{ $subscription->plan?->name }}</p>
        </div>
    </div>

    <!-- Details card -->
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; backdrop-filter: blur(10px); margin-bottom: 40px;">
        <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Configuration Metrics</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; font-size: 14px;">
            <div>
                <span style="color: var(--text-secondary); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Customer</span>
                <span style="font-weight: 600; font-size: 15px;">{{ $subscription->customer?->name }}</span>
            </div>
            <div>
                <span style="color: var(--text-secondary); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Restaurant</span>
                <span style="font-weight: 600; font-size: 15px;">{{ $subscription->restaurant?->name }}</span>
            </div>
            <div>
                <span style="color: var(--text-secondary); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Price paid</span>
                <span style="font-weight: 700; color: var(--success); font-size: 16px;">£{{ number_format($subscription->price, 2) }}</span>
            </div>
            <div>
                <span style="color: var(--text-secondary); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Delivery Window</span>
                <span>{{ $subscription->start_date?->toDateString() }} to {{ $subscription->end_date?->toDateString() }}</span>
            </div>
            <div>
                <span style="color: var(--text-secondary); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Delivery Frequency</span>
                <span>{{ ucfirst($subscription->plan?->delivery_frequency) }} &bull; {{ ucfirst($subscription->plan?->meal_type) }}</span>
            </div>
            <div>
                <span style="color: var(--text-secondary); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Status</span>
                @if($subscription->status === 'ACTIVE')
                    <span class="badge badge-success">Active</span>
                @elseif($subscription->status === 'PAUSED')
                    <span class="badge badge-warning">Paused</span>
                @else
                    <span class="badge badge-danger">{{ $subscription->status }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Scheduled delivery orders logs -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Scheduled Delivery Orders</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Scheduled Date</th>
                    <th>Associated Order No</th>
                    <th>Delivery Status</th>
                    <th>Order Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscription->subscriptionOrders as $subOrder)
                    <tr>
                        <td style="font-weight: 600;">{{ $subOrder->scheduled_date?->toDateString() }}</td>
                        <td style="font-family: monospace;">
                            @if($subOrder->order)
                                <a href="{{ route('admin.orders.show', $subOrder->order_id) }}" style="color: var(--accent-primary); text-decoration: none;">
                                    {{ $subOrder->order->order_number }}
                                </a>
                            @else
                                <span style="color: var(--text-secondary);">Generation Failed</span>
                            @endif
                        </td>
                        <td>
                            @if($subOrder->delivery_status === 'DELIVERED')
                                <span class="badge badge-success">Delivered</span>
                            @elseif($subOrder->delivery_status === 'OUT_FOR_DELIVERY')
                                <span class="badge badge-info">Out For Delivery</span>
                            @else
                                <span class="badge badge-secondary" style="color: var(--text-secondary); border: 1px solid var(--border-color);">{{ $subOrder->delivery_status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($subOrder->order_status === 'COMPLETED')
                                <span class="badge badge-success">Completed</span>
                            @elseif($subOrder->order_status === 'CANCELLED')
                                <span class="badge badge-danger">Cancelled</span>
                            @else
                                <span class="badge badge-warning">{{ $subOrder->order_status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($subOrder->order_id)
                                <a href="{{ route('admin.orders.show', $subOrder->order_id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Inspect</a>
                            @else
                                <span style="color: var(--text-secondary);">N/A</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary);">No scheduled delivery orders generated yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
