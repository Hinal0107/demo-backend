@extends('layouts.admin')

@section('title', 'Manage Restaurants - Admin')

@section('content')
    <div class="header-section">
        <div>
            <h1 class="page-title">Restaurants Management</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Configure and approve restaurant tenants</p>
        </div>
        <a href="{{ route('admin.restaurants.create') }}" class="btn btn-primary">Add Restaurant</a>
    </div>

    <!-- Filters Block -->
    <form action="{{ route('admin.restaurants.index') }}" method="GET" class="filter-card">
        <div class="filter-item">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Search by name, city..." value="{{ request('search') }}">
        </div>
        <div class="filter-item">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                <option value="INACTIVE" {{ request('status') === 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                <option value="PENDING_APPROVAL" {{ request('status') === 'PENDING_APPROVAL' ? 'selected' : '' }}>Pending Approval</option>
                <option value="BLOCKED" {{ request('status') === 'BLOCKED' ? 'selected' : '' }}>Blocked</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.restaurants.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- Restaurants List Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($restaurants as $restaurant)
                    <tr>
                        <td style="font-weight: 600;">{{ $restaurant->name }}</td>
                        <td>{{ $restaurant->email }}</td>
                        <td>{{ $restaurant->phone }}</td>
                        <td>{{ $restaurant->city }}</td>
                        <td>
                            @if($restaurant->status === 'ACTIVE')
                                <span class="badge badge-success">Active</span>
                            @elseif($restaurant->status === 'PENDING_APPROVAL')
                                <span class="badge badge-warning">Pending Approval</span>
                            @elseif($restaurant->status === 'BLOCKED')
                                <span class="badge badge-danger">Blocked</span>
                            @else
                                <span class="badge badge-info">{{ $restaurant->status }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <a href="{{ route('admin.restaurants.show', $restaurant->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">View</a>
                                <a href="{{ route('admin.restaurants.edit', $restaurant->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; color: var(--accent-primary); border-color: rgba(99, 102, 241, 0.3);">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary);">No restaurants found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 20px;">
        {{ $restaurants->appends(request()->query())->links() }}
    </div>
@endsection
