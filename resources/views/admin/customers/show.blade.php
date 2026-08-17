@extends('layouts.admin')

@section('title', 'Customer Profile - ' . $customer->name)

@section('content')
    <div class="header-section">
        <div>
            <a href="{{ route('admin.customers.index') }}" style="color: var(--accent-primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Back to Customers</a>
            <h1 class="page-title" style="margin-top: 10px;">{{ $customer->name }}</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Customer ID: #{{ $customer->id }} &bull; Registered: {{ $customer->created_at?->toDateString() }}</p>
        </div>
    </div>

    <div style="display: flex; gap: 30px; margin-bottom: 40px; flex-wrap: wrap;">
        
        <!-- Contact Summary -->
        <div style="flex: 2; min-width: 320px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; backdrop-filter: blur(10px);">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Contact Information</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Email Address</span>
                    <span>{{ $customer->email }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Phone Number</span>
                    <span>{{ $customer->phone ?: 'N/A' }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Firebase UID</span>
                    <span style="font-family: monospace;">{{ $customer->firebase_uid ?: 'N/A' }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Member Since</span>
                    <span>{{ $customer->created_at?->toFormattedDateString() }}</span>
                </div>
            </div>
        </div>

        <!-- Account Controls -->
        <div style="flex: 1; min-width: 250px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; backdrop-filter: blur(10px); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Security Controls</h2>
                <div style="margin-bottom: 20px;">
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">Status</span>
                    @if($customer->status === 'ACTIVE')
                        <span class="badge badge-success" style="font-size: 13px; padding: 6px 14px;">Active</span>
                    @elseif($customer->status === 'BLOCKED')
                        <span class="badge badge-danger" style="font-size: 13px; padding: 6px 14px;">Blocked</span>
                    @else
                        <span class="badge badge-info" style="font-size: 13px; padding: 6px 14px;">{{ $customer->status }}</span>
                    @endif
                </div>
            </div>

            <form action="{{ route('admin.customers.status', $customer->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                @csrf
                <select name="status" class="form-control" style="margin-bottom: 5px;">
                    <option value="ACTIVE" {{ $customer->status === 'ACTIVE' ? 'selected' : '' }}>Activate Account</option>
                    <option value="INACTIVE" {{ $customer->status === 'INACTIVE' ? 'selected' : '' }}>Set Inactive</option>
                    <option value="BLOCKED" {{ $customer->status === 'BLOCKED' ? 'selected' : '' }}>Block Account</option>
                </select>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Account Status</button>
            </form>
        </div>
    </div>

    <!-- Active Subscriptions -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Subscriptions</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Sub ID</th>
                    <th>Restaurant</th>
                    <th>Plan Name</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->subscriptions as $sub)
                    <tr>
                        <td>#{{ $sub->id }}</td>
                        <td>{{ $sub->restaurant?->name }}</td>
                        <td style="font-weight: 600;">{{ $sub->plan?->name }}</td>
                        <td>{{ $sub->start_date?->toDateString() }} to {{ $sub->end_date?->toDateString() }}</td>
                        <td>£{{ number_format($sub->price, 2) }}</td>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary);">No subscription plans purchased.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Customer Addresses -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Addresses Book</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Phone</th>
                    <th>Full Address Address</th>
                    <th>Coordinates</th>
                    <th>Default</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->addresses as $address)
                    <tr>
                        <td style="font-weight: 600;">{{ $address->name }}</td>
                        <td>{{ $address->phone }}</td>
                        <td>{{ $address->address_line_1 }}, {{ $address->address_line_2 ? $address->address_line_2 . ',' : '' }} {{ $address->city }}, {{ $address->state }}, {{ $address->country }} - {{ $address->pincode }}</td>
                        <td style="font-family: monospace; font-size: 12px;">{{ $address->latitude ?: 'N/A' }}, {{ $address->longitude ?: 'N/A' }}</td>
                        <td>
                            @if($address->is_default)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-secondary" style="border: 1px solid var(--border-color); color: var(--text-secondary);">No</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary);">No delivery addresses registered.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
