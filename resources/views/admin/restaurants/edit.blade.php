@extends('layouts.admin')

@section('title', 'Edit Restaurant - ' . $restaurant->name)

@section('content')
    <div class="header-section">
        <div>
            <a href="{{ route('admin.restaurants.show', $restaurant->id) }}" style="color: var(--accent-primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Back to Details</a>
            <h1 class="page-title" style="margin-top: 10px;">Edit Restaurant</h1>
        </div>
    </div>

    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 30px; max-width: 800px; backdrop-filter: blur(10px);">
        <form action="{{ route('admin.restaurants.update', $restaurant->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Restaurant Name</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name', $restaurant->name) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required value="{{ old('email', $restaurant->email) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="phone" class="form-control" required value="{{ old('phone', $restaurant->phone) }}">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Address Line</label>
                    <input type="text" name="address" class="form-control" required value="{{ old('address', $restaurant->address) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" required value="{{ old('city', $restaurant->city) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" required value="{{ old('state', $restaurant->state) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control" required value="{{ old('country', $restaurant->country) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" class="form-control" required value="{{ old('pincode', $restaurant->pincode) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Opening Time</label>
                    <input type="time" name="opening_time" class="form-control" required value="{{ old('opening_time', substr($restaurant->opening_time, 0, 5)) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Closing Time</label>
                    <input type="time" name="closing_time" class="form-control" required value="{{ old('closing_time', substr($restaurant->closing_time, 0, 5)) }}">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="ACTIVE" {{ old('status', $restaurant->status) === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                        <option value="INACTIVE" {{ old('status', $restaurant->status) === 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                        <option value="PENDING_APPROVAL" {{ old('status', $restaurant->status) === 'PENDING_APPROVAL' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="BLOCKED" {{ old('status', $restaurant->status) === 'BLOCKED' ? 'selected' : '' }}>Blocked</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;">
                <a href="{{ route('admin.restaurants.show', $restaurant->id) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
@endsection
