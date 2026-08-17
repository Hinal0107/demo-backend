@extends('layouts.admin')

@section('title', $restaurant->name . ' - Restaurant Details')

@section('content')
    <div class="header-section">
        <div>
            <a href="{{ route('admin.restaurants.index') }}" style="color: var(--accent-primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Back to Restaurants</a>
            <h1 class="page-title" style="margin-top: 10px;">{{ $restaurant->name }}</h1>
        </div>
        <form action="{{ route('admin.restaurants.destroy', $restaurant->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this restaurant?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete Restaurant</button>
        </form>
    </div>

    <!-- Quick Stats and Approval Actions -->
    <div style="display: flex; gap: 30px; margin-bottom: 40px; flex-wrap: wrap;">
        
        <!-- Details Card -->
        <div style="flex: 2; min-width: 320px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; backdrop-filter: blur(10px);">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Contact & Location Details</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Email</span>
                    <span>{{ $restaurant->email }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Phone</span>
                    <span>{{ $restaurant->phone }}</span>
                </div>
                <div style="grid-column: span 2;">
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Address</span>
                    <span>{{ $restaurant->address }}, {{ $restaurant->city }}, {{ $restaurant->state }}, {{ $restaurant->country }} - {{ $restaurant->pincode }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Opening Hours</span>
                    <span>{{ $restaurant->opening_time }} - {{ $restaurant->closing_time }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Total Platform Revenue</span>
                    <span style="color: var(--success); font-weight: 700;">£{{ number_format($totalRevenue, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Approval and status change block -->
        <div style="flex: 1; min-width: 250px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; backdrop-filter: blur(10px); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Status Configuration</h2>
                <div style="margin-bottom: 20px;">
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">Current Status</span>
                    @if($restaurant->status === 'ACTIVE')
                        <span class="badge badge-success" style="font-size: 13px; padding: 6px 14px;">Active</span>
                    @elseif($restaurant->status === 'PENDING_APPROVAL')
                        <span class="badge badge-warning" style="font-size: 13px; padding: 6px 14px;">Pending Approval</span>
                    @elseif($restaurant->status === 'BLOCKED')
                        <span class="badge badge-danger" style="font-size: 13px; padding: 6px 14px;">Blocked</span>
                    @else
                        <span class="badge badge-info" style="font-size: 13px; padding: 6px 14px;">{{ $restaurant->status }}</span>
                    @endif
                </div>
            </div>

            <form action="{{ route('admin.restaurants.status', $restaurant->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                @csrf
                <select name="status" class="form-control" style="margin-bottom: 5px;">
                    <option value="ACTIVE" {{ $restaurant->status === 'ACTIVE' ? 'selected' : '' }}>Activate Restaurant</option>
                    <option value="INACTIVE" {{ $restaurant->status === 'INACTIVE' ? 'selected' : '' }}>Set Inactive</option>
                    <option value="PENDING_APPROVAL" {{ $restaurant->status === 'PENDING_APPROVAL' ? 'selected' : '' }}>Set Pending Approval</option>
                    <option value="BLOCKED" {{ $restaurant->status === 'BLOCKED' ? 'selected' : '' }}>Block Restaurant</option>
                </select>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Status</button>
            </form>
        </div>
    </div>

    <!-- Staff Users list -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Restaurant Staff / Associated Users</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>User Account Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($restaurant->users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td style="font-weight: 600;">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone ?: 'N/A' }}</td>
                        <td>
                            @if($user->status === 'ACTIVE')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">{{ $user->status }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary);">No associated staff accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
