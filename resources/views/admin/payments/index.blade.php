@extends('layouts.admin')

@section('title', 'Payments Audit Registry - Admin')

@section('content')
    <div class="header-section">
        <div>
            <h1 class="page-title">Platform Payments</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Audit financial transactions and Worldpay gateway logs</p>
        </div>
    </div>

    <!-- Filters Block -->
    <form action="{{ route('admin.payments.index') }}" method="GET" class="filter-card">
        <div class="filter-item">
            <label class="form-label">Worldpay Transaction ID</label>
            <input type="text" name="transaction_id" class="form-control" placeholder="tx_..." value="{{ request('transaction_id') }}">
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
            <label class="form-label">Payment Status</label>
            <select name="status" class="form-control">
                <option value="">All</option>
                <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>Paid</option>
                <option value="FAILED" {{ request('status') === 'FAILED' ? 'selected' : '' }}>Failed</option>
                <option value="REFUNDED" {{ request('status') === 'REFUNDED' ? 'selected' : '' }}>Refunded</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- Payments List Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Order No</th>
                    <th>Customer</th>
                    <th>Restaurant</th>
                    <th>Worldpay ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Paid At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>#{{ $payment->id }}</td>
                        <td>
                            @if($payment->order)
                                <a href="{{ route('admin.orders.show', $payment->order_id) }}" style="color: var(--accent-primary); text-decoration: none; font-family: monospace;">
                                    {{ $payment->order->order_number }}
                                </a>
                            @else
                                <span style="color: var(--text-secondary);">Subscription</span>
                            @endif
                        </td>
                        <td>{{ $payment->customer?->name }}</td>
                        <td>{{ $payment->restaurant?->name }}</td>
                        <td style="font-family: monospace; font-size: 13px;">{{ $payment->worldpay_transaction_id ?: 'N/A' }}</td>
                        <td style="font-weight: 700;">£{{ number_format($payment->amount, 2) }}</td>
                        <td>
                            @if($payment->status === 'PAID')
                                <span class="badge badge-success">Paid</span>
                            @elseif($payment->status === 'REFUNDED')
                                <span class="badge badge-info">Refunded</span>
                            @elseif($payment->status === 'FAILED')
                                <span class="badge badge-danger">Failed</span>
                            @else
                                <span class="badge badge-warning">{{ $payment->status }}</span>
                            @endif
                        </td>
                        <td>{{ $payment->paid_at ? $payment->paid_at->toDateTimeString() : 'N/A' }}</td>
                        <td>
                            <a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Logs</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-secondary);">No payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 20px;">
        {{ $payments->appends(request()->query())->links() }}
    </div>
@endsection
