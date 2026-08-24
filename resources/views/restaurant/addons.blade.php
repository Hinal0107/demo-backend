@extends('layouts.restaurant')

@section('title', 'Add-ons Directory - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">Add-ons Management</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Manage extra side dishes, desserts, or beverages</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
    
    <!-- Addons Listing -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Add-on Items</h2>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Availability</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($addons as $addon)
                        <tr>
                            <td>
                                @if($addon->image)
                                    <img src="{{ $addon->image }}" alt="Addon Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                @else
                                    <div style="width: 50px; height: 50px; border-radius: 6px; background-color: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; font-size: 10px; color: var(--text-secondary);">No Image</div>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $addon->name }}</div>
                                <div style="font-size: 11px; color: var(--text-secondary);">{{ $addon->description ?: 'No description' }}</div>
                            </td>
                            <td style="font-weight: 600;">£{{ number_format($addon->price, 2) }}</td>
                            <td>
                                <span class="badge {{ $addon->availability ? 'badge-success' : 'badge-danger' }}">
                                    {{ $addon->availability ? 'In Stock' : 'Out of Stock' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $addon->status === 'ACTIVE' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $addon->status }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="populateEditForm({{ json_encode($addon) }})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                                    
                                    <form action="{{ route('restaurant.addons.destroy', $addon->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this add-on?')">
                                        @csrf
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                                No add-on items found. Create one on the right.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create / Edit Panel -->
    <div class="table-container" style="padding: 25px;">
        <h2 class="table-title" id="panel-title" style="margin-bottom: 20px;">Add New Add-on</h2>
        
        <form id="addon-form" action="{{ route('restaurant.addons.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <input type="hidden" name="_method" id="form-method" value="POST">

            <div class="form-group">
                <label class="form-label" for="addon-name">Item Name</label>
                <input class="form-control" type="text" id="addon-name" name="name" required placeholder="e.g. Buttermilk / Sweet Lassi">
            </div>

            <div class="form-group">
                <label class="form-label" for="addon-desc">Description</label>
                <textarea class="form-control" id="addon-desc" name="description" rows="2" placeholder="e.g. Spiced yogurt drink, 250ml"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="addon-image">Item Image</label>
                <input class="form-control" type="file" id="addon-image" name="image" accept="image/*">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="addon-price">Price (£)</label>
                    <input class="form-control" type="number" step="0.01" id="addon-price" name="price" required min="0" placeholder="1.50">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="addon-availability">Availability</label>
                    <select class="form-control" id="addon-availability" name="availability" required>
                        <option value="1">In Stock</option>
                        <option value="0">Out of Stock</option>
                    </select>
                </div>
            </div>

            <div class="form-group" id="status-group" style="display: none;">
                <label class="form-label" for="addon-status">Status</label>
                <select class="form-control" id="addon-status" name="status">
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="INACTIVE">INACTIVE</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="submit" class="btn btn-primary" id="submit-btn" style="flex: 1;">Create Add-on</button>
                <button type="button" id="cancel-btn" onclick="resetForm()" class="btn btn-secondary" style="display: none;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function populateEditForm(addon) {
        document.getElementById('panel-title').innerText = 'Edit Add-on: ' + addon.name;
        document.getElementById('addon-name').value = addon.name;
        document.getElementById('addon-desc').value = addon.description || '';
        document.getElementById('addon-price').value = addon.price;
        document.getElementById('addon-availability').value = addon.availability ? "1" : "0";
        document.getElementById('addon-status').value = addon.status;
        
        document.getElementById('status-group').style.display = 'block';
        document.getElementById('cancel-btn').style.display = 'block';
        document.getElementById('submit-btn').innerText = 'Save Changes';
        
        var form = document.getElementById('addon-form');
        form.action = '/restaurant/addons/' + addon.id;
        document.getElementById('form-method').value = 'POST';
    }

    function resetForm() {
        document.getElementById('panel-title').innerText = 'Add New Add-on';
        document.getElementById('addon-form').reset();
        document.getElementById('status-group').style.display = 'none';
        document.getElementById('cancel-btn').style.display = 'none';
        document.getElementById('submit-btn').innerText = 'Create Add-on';
        
        var form = document.getElementById('addon-form');
        form.action = '{{ route('restaurant.addons.store') }}';
        document.getElementById('form-method').value = 'POST';
    }
</script>
@endsection
