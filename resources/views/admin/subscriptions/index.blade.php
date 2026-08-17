@extends('layouts.admin')

@section('title', 'Manage Subscriptions - Admin')

@section('content')
    <div class="header-section">
        <div>
            <h1 class="page-title">Platform Subscriptions</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Track and audit recurring thali subscriptions</p>
        </div>
    </div>

    <!-- Filters Block -->
    <form action="{{ route('admin.subscriptions.index') }}" method="GET" class="filter-card">
        <div class="filter-item">
            <label class="form-label">Search Customer Name</label>
            <input type="text" name="search" class="form-control" placeholder="Search by customer name..." value="{{ request('search') }}">
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
            <label class="form-label">Subscription Status</label>
            <select name="status" class="form-control">
                <option value="">All</option>
                <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                <option value="PAUSED" {{ request('status') === 'PAUSED' ? 'selected' : '' }}>Paused</option>
                <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                <option value="EXPIRED" {{ request('status') === 'EXPIRED' ? 'selected' : '' }}>Expired</option>
                <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- Subscriptions List Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Sub ID</th>
                    <th>Customer</th>
                    <th>Restaurant</th>
                    <th>Plan Name</th>
                    <th>Price</th>
                    <th>Timeline</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                    <tr>
                        <td>#{{ $sub->id }}</td>
                        <td style="font-weight: 600;">{{ $sub->customer?->name }}</td>
                        <td>{{ $sub->restaurant?->name }}</td>
                        <td>{{ $sub->plan?->name }}</td>
                        <td style="font-weight: 700;">£{{ number_format($sub->price, 2) }}</td>
                        <td>{{ $sub->start_date?->toDateString() }} to {{ $sub->end_date?->toDateString() }}</td>
                        <td>
                            @if($sub->status === 'ACTIVE')
                                <span class="badge badge-success">Active</span>
                            @elseif($sub->status === 'PAUSED')
                                <span class="badge badge-warning">Paused</span>
                            @elseif($sub->status === 'CANCELLED')
                                <span class="badge badge-danger">Cancelled</span>
                            @else
                                <span class="badge badge-info">{{ $sub->status }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.subscriptions.show', $sub->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Inspect</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-secondary);">No subscriptions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 20px;">
        {{ $subscriptions->appends(request()->query())->links() }}
    </div>
@endsection
