@extends('layouts.admin')

@section('title', 'Manage Customers - Admin')

@section('content')
    <div class="header-section">
        <div>
            <h1 class="page-title">Customers Management</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Platform customers registry</p>
        </div>
    </div>

    <!-- Filters Block -->
    <form action="{{ route('admin.customers.index') }}" method="GET" class="filter-card">
        <div class="filter-item">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone..." value="{{ request('search') }}">
        </div>
        <div class="filter-item">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                <option value="INACTIVE" {{ request('status') === 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                <option value="BLOCKED" {{ request('status') === 'BLOCKED' ? 'selected' : '' }}>Blocked</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- Customers List Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td style="font-weight: 600;">{{ $customer->name }}</td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->phone ?: 'N/A' }}</td>
                        <td>
                            @if($customer->status === 'ACTIVE')
                                <span class="badge badge-success">Active</span>
                            @elseif($customer->status === 'BLOCKED')
                                <span class="badge badge-danger">Blocked</span>
                            @else
                                <span class="badge badge-info">{{ $customer->status }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">View Activity</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary);">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 20px;">
        {{ $customers->appends(request()->query())->links() }}
    </div>
@endsection
