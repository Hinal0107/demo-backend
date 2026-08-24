@extends('layouts.admin')

@section('title', 'Add New Restaurant')

@section('content')
    <div class="header-section">
        <div>
            <a href="{{ route('admin.restaurants.index') }}" style="color: var(--accent-primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Back to Restaurants</a>
            <h1 class="page-title" style="margin-top: 10px;">Add New Restaurant</h1>
        </div>
    </div>

    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 30px; max-width: 800px; backdrop-filter: blur(10px);">
        <form action="{{ route('admin.restaurants.store') }}" method="POST">
            @csrf
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Restaurant Name</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="Royal Tiffin Service">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required value="{{ old('email') }}" placeholder="royal@tiffin.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="phone" class="form-control" required value="{{ old('phone') }}" placeholder="+447111222333">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Address Line</label>
                    <input type="text" name="address" class="form-control" required value="{{ old('address') }}" placeholder="123 Food Street">
                </div>

                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" required value="{{ old('city') }}" placeholder="London">
                </div>

                <div class="form-group">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" required value="{{ old('state') }}" placeholder="Greater London">
                </div>

                <div class="form-group">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control" required value="{{ old('country') }}" placeholder="United Kingdom">
                </div>

                <div class="form-group">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" class="form-control" required value="{{ old('pincode') }}" placeholder="EC1A 1BB">
                </div>

                <div class="form-group">
                    <label class="form-label">Opening Time (24h format)</label>
                    <input type="time" name="opening_time" class="form-control" required value="{{ old('opening_time', '08:00') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Closing Time (24h format)</label>
                    <input type="time" name="closing_time" class="form-control" required value="{{ old('closing_time', '22:00') }}">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="PENDING_APPROVAL" {{ old('status') === 'PENDING_APPROVAL' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="ACTIVE" {{ old('status') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                        <option value="INACTIVE" {{ old('status') === 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div style="grid-column: span 2; margin-top: 15px;">
                    <hr style="border: 0; border-top: 1px solid var(--border-color); margin-bottom: 15px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--text-primary);">Manager Account Setup</h3>
                </div>

                <div class="form-group">
                    <label class="form-label">Login Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Minimum 6 characters">
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required placeholder="Retype password">
                </div>

                <div style="grid-column: span 2; margin-top: 15px;">
                    <hr style="border: 0; border-top: 1px solid var(--border-color); margin-bottom: 15px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--text-primary);">Banking & Settlement Information</h3>
                </div>

                <div class="form-group">
                    <label class="form-label">Bank Account Holder Name</label>
                    <input type="text" name="bank_account_holder" class="form-control" value="{{ old('bank_account_holder') }}" placeholder="e.g. Royal Tiffin Ltd">
                </div>

                <div class="form-group">
                    <label class="form-label">Bank Account Number</label>
                    <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number') }}" placeholder="e.g. 12345678">
                </div>

                <div class="form-group">
                    <label class="form-label">IFSC / Sort Code</label>
                    <input type="text" name="bank_ifsc" class="form-control" value="{{ old('bank_ifsc') }}" placeholder="e.g. 60-00-04">
                </div>

                <div class="form-group">
                    <label class="form-label">Bank Branch Name</label>
                    <input type="text" name="bank_branch" class="form-control" value="{{ old('bank_branch') }}" placeholder="e.g. London High Street">
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px; justify-content: flex-end;">
                <a href="{{ route('admin.restaurants.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Restaurant</button>
            </div>
        </form>
    </div>
@endsection
