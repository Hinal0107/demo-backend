@extends('layouts.admin')

@section('title', 'Notification Logs & Monitoring - Admin')

@section('content')
    <div class="header-section">
        <div>
            <h1 class="page-title">Notification Logs & Monitoring</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Monitor all real-time and background push notification transmissions</p>
        </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-title">Total Notifications</span>
            <span class="stat-value">{{ $stats['total_count'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Unread Notifications</span>
            <span class="stat-value" style="color: var(--warning); -webkit-text-fill-color: initial;">{{ $stats['unread_count'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Successfully Sent</span>
            <span class="stat-value" style="color: var(--success); -webkit-text-fill-color: initial;">{{ $stats['sent_count'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Failed Transfers</span>
            <span class="stat-value" style="color: var(--danger); -webkit-text-fill-color: initial;">{{ $stats['failed_count'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Today's Traffic</span>
            <span class="stat-value" style="color: var(--info); -webkit-text-fill-color: initial;">{{ $stats['today_count'] }}</span>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.notifications.monitor') }}" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%; align-items: flex-end;">
            <div class="filter-item" style="flex: 2; min-width: 200px;">
                <label class="form-label">Search Title/Message/User</label>
                <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
            </div>
            
            <div class="filter-item">
                <label class="form-label">Type</label>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    @foreach($types as $t)
                        <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $t)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-item">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="SENT" {{ request('status') === 'SENT' ? 'selected' : '' }}>SENT</option>
                    <option value="FAILED" {{ request('status') === 'FAILED' ? 'selected' : '' }}>FAILED</option>
                </select>
            </div>

            <div class="filter-item">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>

            <div class="filter-item" style="min-width: 220px;">
                <label class="form-label">Restaurant</label>
                <select name="restaurant_id" class="form-control">
                    <option value="">All Restaurants</option>
                    @foreach($restaurants as $r)
                        <option value="{{ $r->id }}" {{ (int)request('restaurant_id') === $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-item" style="min-width: 220px;">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-control">
                    <option value="">All Customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ (int)request('customer_id') === $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->email }})</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.notifications.monitor') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Notification Logs Table -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Notification History Log</h2>
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="width: 15%;">Recipient</th>
                    <th style="width: 12%;">Type</th>
                    <th style="width: 20%;">Title</th>
                    <th style="width: 25%;">Message</th>
                    <th style="width: 8%;">Status</th>
                    <th style="width: 10%;">Read At</th>
                    <th style="width: 10%;">Created At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $noti)
                    <tr>
                        <td>
                            <strong style="color: var(--text-primary);">{{ $noti->user?->name ?? 'N/A' }}</strong><br>
                            <span style="font-size: 11px; color: var(--text-secondary);">{{ $noti->user?->role }} (ID: {{ $noti->user_id }})</span>
                        </td>
                        <td>
                            <span class="badge badge-info" style="font-size: 9px; font-weight: 800;">{{ strtoupper(str_replace('_', ' ', $noti->type)) }}</span>
                        </td>
                        <td><strong>{{ $noti->title }}</strong></td>
                        <td>
                            <span style="font-size: 13px; color: var(--text-secondary);">{{ $noti->message }}</span>
                            @if($noti->order_id)
                                <br><span style="font-size: 10px; color: var(--accent-primary);">Order ID: {{ $noti->order_id }}</span>
                            @endif
                            @if($noti->subscription_id)
                                <br><span style="font-size: 10px; color: var(--accent-primary);">Subscription ID: {{ $noti->subscription_id }}</span>
                            @endif
                        </td>
                        <td>
                            @if($noti->status === 'SENT')
                                <span class="badge badge-success">SENT</span>
                            @else
                                <span class="badge badge-danger">FAILED</span>
                            @endif
                        </td>
                        <td>
                            @if($noti->read_at)
                                <span style="color: var(--success); font-size: 12px; font-weight: 600;">✓ Read</span><br>
                                <span style="font-size: 10px; color: var(--text-secondary);">{{ $noti->read_at }}</span>
                            @else
                                <span style="color: var(--warning); font-size: 12px; font-weight: 600;">Unread</span>
                            @endif
                        </td>
                        <td style="font-size: 12px; color: var(--text-secondary);">
                            {{ $noti->created_at?->toDateTimeString() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                            No matching notification records found in history logs.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Pagination Links -->
        <div style="padding: 20px 25px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
            {{ $notifications->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
