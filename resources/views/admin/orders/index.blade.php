@extends('layouts.admin')

@section('title', 'Manage Orders - Admin')

@section('content')
    <div class="header-section">
        <div>
            <h1 class="page-title">Platform Orders</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">View and track all meal deliveries</p>
        </div>
    </div>

    <!-- Filters Block -->
    <form action="{{ route('admin.orders.index') }}" method="GET" class="filter-card">
        <div class="filter-item">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Order No, Customer name..." value="{{ request('search') }}">
        </div>
        <div class="filter-item">
            <label class="form-label">Restaurant</label>
            <select name="restaurant_id" class="form-control">
                <option value="">All Restaurants</option>
                @foreach($restaurants as $res)
                    <option value="{{ $res->id }}" {{ request('restaurant_id') == $res->id ? 'selected' : '' }}>{{ $res->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-item">
            <label class="form-label">Order Status</label>
            <select name="order_status" class="form-control">
                <option value="">All</option>
                <option value="PENDING_PAYMENT" {{ request('order_status') === 'PENDING_PAYMENT' ? 'selected' : '' }}>Pending Payment</option>
                <option value="PAID" {{ request('order_status') === 'PAID' ? 'selected' : '' }}>Paid</option>
                <option value="CONFIRMED" {{ request('order_status') === 'CONFIRMED' ? 'selected' : '' }}>Confirmed</option>
                <option value="PREPARING" {{ request('order_status') === 'PREPARING' ? 'selected' : '' }}>Preparing</option>
                <option value="READY" {{ request('order_status') === 'READY' ? 'selected' : '' }}>Ready</option>
                <option value="COMPLETED" {{ request('order_status') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                <option value="CANCELLED" {{ request('order_status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <div class="filter-item">
            <label class="form-label">Delivery Status</label>
            <select name="delivery_status" class="form-control">
                <option value="">All</option>
                <option value="PENDING" {{ request('delivery_status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                <option value="OUT_FOR_DELIVERY" {{ request('delivery_status') === 'OUT_FOR_DELIVERY' ? 'selected' : '' }}>Out for Delivery</option>
                <option value="DELIVERED" {{ request('delivery_status') === 'DELIVERED' ? 'selected' : '' }}>Delivered</option>
                <option value="FAILED" {{ request('delivery_status') === 'FAILED' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- Orders List Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Restaurant</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Order Status</th>
                    <th>Delivery Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td style="font-weight: 600; font-family: monospace;">{{ $order->order_number }}</td>
                        <td>{{ $order->restaurant?->name }}</td>
                        <td>{{ $order->customer?->name }}</td>
                        <td style="font-weight: 700;">£{{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            @if($order->order_status === 'COMPLETED')
                                <span class="badge badge-success">Completed</span>
                            @elseif($order->order_status === 'CANCELLED')
                                <span class="badge badge-danger">Cancelled</span>
                            @elseif($order->order_status === 'CONFIRMED' || $order->order_status === 'PREPARING')
                                <span class="badge badge-info">{{ $order->order_status }}</span>
                            @else
                                <span class="badge badge-warning">{{ $order->order_status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($order->delivery_status === 'DELIVERED')
                                <span class="badge badge-success">Delivered</span>
                            @elseif($order->delivery_status === 'OUT_FOR_DELIVERY')
                                <span class="badge badge-info">Out For Delivery</span>
                            @elseif($order->delivery_status === 'FAILED')
                                <span class="badge badge-danger">Failed</span>
                            @else
                                <span class="badge badge-secondary" style="color: var(--text-secondary); border: 1px solid var(--border-color);">Pending</span>
                            @endif
                        </td>
                        <td>{{ $order->created_at?->toDateString() }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-secondary);">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 20px;">
        {{ $orders->appends(request()->query())->links() }}
    </div>
@endsection
