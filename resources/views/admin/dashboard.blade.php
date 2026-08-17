@extends('layouts.admin')

@section('title', 'Admin Dashboard - Tiffin Service')

@section('content')
    <div class="header-section">
        <div>
            <h1 class="page-title">Dashboard Overview</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Platform statistics and logs</p>
        </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-title">Total Restaurants</span>
            <span class="stat-value">{{ $stats['total_restaurants'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Active Restaurants</span>
            <span class="stat-value" style="color: var(--success); -webkit-text-fill-color: initial;">{{ $stats['active_restaurants'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Total Customers</span>
            <span class="stat-value">{{ $stats['total_customers'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Today's Orders</span>
            <span class="stat-value" style="color: var(--info); -webkit-text-fill-color: initial;">{{ $stats['today_orders'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Today's Revenue</span>
            <span class="stat-value" style="color: var(--success); -webkit-text-fill-color: initial;">£{{ number_format($stats['today_revenue'], 2) }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Total Revenue</span>
            <span class="stat-value" style="color: var(--success); -webkit-text-fill-color: initial;">£{{ number_format($stats['total_revenue'], 2) }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Active Subscriptions</span>
            <span class="stat-value" style="color: var(--accent-primary); -webkit-text-fill-color: initial;">{{ $stats['active_subscriptions'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Delivered Orders</span>
            <span class="stat-value">{{ $stats['delivered_orders'] }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Cancelled Orders</span>
            <span class="stat-value" style="color: var(--danger); -webkit-text-fill-color: initial;">{{ $stats['cancelled_orders'] }}</span>
        </div>
    </div>

    <!-- Recent Activity Logs -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Recent System Activities</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Record ID</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentActivities as $log)
                    <tr>
                        <td>{{ $log->admin?->name }}</td>
                        <td>
                            <span class="badge badge-info">{{ $log->action }}</span>
                        </td>
                        <td>{{ strtoupper($log->module) }}</td>
                        <td>{{ $log->record_id ?: 'N/A' }}</td>
                        <td>{{ $log->ip_address }}</td>
                        <td>{{ $log->created_at?->toDateTimeString() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary);">No recent activities recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
