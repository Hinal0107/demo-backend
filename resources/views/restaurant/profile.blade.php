@extends('layouts.restaurant')

@section('title', 'My Profile - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">My Profile</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Manage contact details and operating parameters</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div class="table-container" style="max-width: 800px; padding: 30px;">
    <form action="{{ route('restaurant.profile.update') }}" method="POST">
        @csrf
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label" for="name">Restaurant Name</label>
                <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $restaurant->name) }}" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input class="form-control" type="text" id="phone" name="phone" value="{{ old('phone', $restaurant->phone) }}" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" style="resize: vertical;">{{ old('description', $restaurant->description) }}</textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="address">Street Address</label>
            <input class="form-control" type="text" id="address" name="address" value="{{ old('address', $restaurant->address) }}" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label" for="city">City</label>
                <input class="form-control" type="text" id="city" name="city" value="{{ old('city', $restaurant->city) }}" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="state">State</label>
                <input class="form-control" type="text" id="state" name="state" value="{{ old('state', $restaurant->state) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="pincode">Pincode</label>
                <input class="form-control" type="text" id="pincode" name="pincode" value="{{ old('pincode', $restaurant->pincode) }}" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label" for="opening_time">Opening Time</label>
                <input class="form-control" type="time" id="opening_time" name="opening_time" value="{{ old('opening_time', $restaurant->opening_time ? substr($restaurant->opening_time, 0, 5) : '') }}" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="closing_time">Closing Time</label>
                <input class="form-control" type="time" id="closing_time" name="closing_time" value="{{ old('closing_time', $restaurant->closing_time ? substr($restaurant->closing_time, 0, 5) : '') }}" required>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save Profile Settings</button>
        </div>
    </form>
</div>
@endsection
