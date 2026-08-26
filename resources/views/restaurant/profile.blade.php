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
    <form action="{{ route('restaurant.profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div style="display: flex; align-items: center; gap: 25px; margin-bottom: 25px;">
            <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 2px solid var(--border-color); background: #eee; display: flex; align-items: center; justify-content: center;">
                @if($restaurant->logo)
                    <img src="{{ app(\App\Services\ImageUploadService::class)->formatUrl($restaurant->logo) }}" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    <span style="font-size: 12px; color: var(--text-secondary);">No Logo</span>
                @endif
            </div>
            <div>
                <label class="form-label" for="logo">Restaurant Logo</label>
                <input class="form-control" type="file" id="logo" name="logo" accept="image/*">
                <span style="font-size: 11px; color: var(--text-secondary);">Recommended dimensions: 512x512px. Max 2MB.</span>
            </div>
        </div>

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

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label class="form-label" for="city">City</label>
                <input class="form-control" type="text" id="city" name="city" value="{{ old('city', $restaurant->city) }}" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="state">State</label>
                <input class="form-control" type="text" id="state" name="state" value="{{ old('state', $restaurant->state) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="country">Country</label>
                <input class="form-control" type="text" id="country" name="country" value="{{ old('country', $restaurant->country ?? 'United Kingdom') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="pincode">Pincode</label>
                <input class="form-control" type="text" id="pincode" name="pincode" value="{{ old('pincode', $restaurant->pincode) }}" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label class="form-label" for="opening_time">Opening Time</label>
                <input class="form-control" type="time" id="opening_time" name="opening_time" value="{{ old('opening_time', $restaurant->opening_time ? substr($restaurant->opening_time, 0, 5) : '') }}" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="closing_time">Closing Time</label>
                <input class="form-control" type="time" id="closing_time" name="closing_time" value="{{ old('closing_time', $restaurant->closing_time ? substr($restaurant->closing_time, 0, 5) : '') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="latitude">Latitude</label>
                <input class="form-control" type="number" step="any" id="latitude" name="latitude" value="{{ old('latitude', $restaurant->latitude) }}" placeholder="e.g. 51.5074">
            </div>

            <div class="form-group">
                <label class="form-label" for="longitude">Longitude</label>
                <input class="form-control" type="number" step="any" id="longitude" name="longitude" value="{{ old('longitude', $restaurant->longitude) }}" placeholder="e.g. -0.1278">
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 25px 0;">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 15px;">Business & Banking Details</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label" for="bank_account_holder">Account Holder Name</label>
                <input class="form-control" type="text" id="bank_account_holder" name="bank_account_holder" value="{{ old('bank_account_holder', $restaurant->bank_account_holder) }}" placeholder="e.g. Royal Tiffin Ltd">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="bank_account_number">Bank Account Number</label>
                <input class="form-control" type="text" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $restaurant->bank_account_number) }}" placeholder="e.g. 12345678">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label" for="bank_ifsc">IFSC / Sort Code</label>
                <input class="form-control" type="text" id="bank_ifsc" name="bank_ifsc" value="{{ old('bank_ifsc', $restaurant->bank_ifsc) }}" placeholder="e.g. 60-00-04 or BARC0INBB">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="bank_branch">Bank Branch</label>
                <input class="form-control" type="text" id="bank_branch" name="bank_branch" value="{{ old('bank_branch', $restaurant->bank_branch) }}" placeholder="e.g. Central London Branch">
            </div>
        </div>

        <div style="margin-top: 25px;">
            <button type="submit" class="btn btn-primary">Save Profile Settings</button>
        </div>
    </form>
</div>
@endsection
