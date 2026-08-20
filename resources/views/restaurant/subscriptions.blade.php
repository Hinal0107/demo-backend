@extends('layouts.restaurant')

@section('title', 'Subscriptions - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">Subscriptions</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Track daily meal subscription programs and clients</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h2 class="table-title">Active Subscription Plans</h2>
    </div>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Plan Name</th>
                    <th>Price</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Frequency</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                    <tr>
                        <td style="font-weight: 600;">{{ $sub->customer->name ?? 'Guest Customer' }}</td>
                        <td style="font-weight: 600;">{{ $sub->plan->name ?? 'Custom Plan' }}</td>
                        <td>£{{ number_format($sub->price, 2) }}</td>
                        <td>{{ $sub->start_date ? $sub->start_date->format('M d, Y') : 'N/A' }}</td>
                        <td>{{ $sub->end_date ? $sub->end_date->format('M d, Y') : 'N/A' }}</td>
                        <td><span class="badge badge-info">{{ $sub->plan->delivery_frequency ?? 'daily' }}</span></td>
                        <td>
                            @php
                                $statusClass = 'badge-warning';
                                if($sub->status === 'ACTIVE') $statusClass = 'badge-success';
                                elseif($sub->status === 'CANCELLED') $statusClass = 'badge-danger';
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ $sub->status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                            No active subscription plans found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
