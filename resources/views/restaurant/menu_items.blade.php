@extends('layouts.restaurant')

@section('title', 'Menu Items - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">Menu Items</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Manage individual dishes, price structures, and availability</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
    
    <!-- Items Listing -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Dish Directory</h2>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Sort</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Veg Type</th>
                        <th>Availability</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menuItems as $item)
                        <tr>
                            <td>
                                @if($item->image)
                                    <img src="{{ app(\App\Services\ImageUploadService::class)->formatUrl($item->image) }}" alt="Dish Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                @else
                                    <div style="width: 50px; height: 50px; border-radius: 6px; background-color: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; font-size: 10px; color: var(--text-secondary);">No Image</div>
                                @endif
                            </td>
                            <td style="font-weight: 600;">{{ $item->sort_order }}</td>
                            <td>
                                <div style="font-weight: 600;">{{ $item->name }}</div>
                                <div style="font-size: 11px; color: var(--text-secondary);">{{ $item->description }}</div>
                            </td>
                            <td><span class="badge badge-info">{{ $item->category->name ?? 'None' }}</span></td>
                            <td>
                                @if($item->discount_price)
                                    <span style="text-decoration: line-through; color: var(--text-secondary); font-size: 12px;">£{{ number_format($item->price, 2) }}</span>
                                    <span style="font-weight: 600; color: var(--accent-primary);">£{{ number_format($item->discount_price, 2) }}</span>
                                @else
                                    <span style="font-weight: 600;">£{{ number_format($item->price, 2) }}</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $badge = 'badge-success';
                                    if($item->veg_type === 'NON_VEG') $badge = 'badge-danger';
                                    elseif($item->veg_type === 'EGG') $badge = 'badge-warning';
                                @endphp
                                <span class="badge {{ $badge }}">{{ str_replace('_', ' ', $item->veg_type) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $item->availability ? 'badge-success' : 'badge-danger' }}">
                                    {{ $item->availability ? 'In Stock' : 'Out of Stock' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $item->status === 'ACTIVE' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="populateEditItemForm({{ json_encode($item) }})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                                    
                                    <form action="{{ route('restaurant.menu-items.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this menu item?')">
                                        @csrf
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                                No menu items found. Create one on the right.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add / Edit Menu Item Form -->
    <div class="table-container" style="padding: 25px;">
        <h2 class="table-title" id="item-panel-title" style="margin-bottom: 20px;">Add New Menu Item</h2>
        
        <form id="item-form" action="{{ route('restaurant.menu-items.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <input type="hidden" name="_method" id="item-form-method" value="POST">

            <div class="form-group">
                <label class="form-label" for="item-category">Category</label>
                <select class="form-control" id="item-category" name="category_id" required>
                    <option value="" disabled selected>Select Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="item-name">Dish Name</label>
                <input class="form-control" type="text" id="item-name" name="name" required placeholder="e.g. Special Gujarati Thali">
            </div>

            <div class="form-group">
                <label class="form-label" for="item-desc">Description</label>
                <textarea class="form-control" id="item-desc" name="description" rows="2" placeholder="e.g. Includes rotis, paneer shaak..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="item-image">Dish Image</label>
                <input class="form-control" type="file" id="item-image" name="image" accept="image/*">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="item-price">Regular Price (£)</label>
                    <input class="form-control" type="number" step="0.01" id="item-price" name="price" required min="0" placeholder="10.00">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="item-discount">Discount Price (£)</label>
                    <input class="form-control" type="number" step="0.01" id="item-discount" name="discount_price" min="0" placeholder="e.g. 8.50">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="item-veg">Dietary Type</label>
                    <select class="form-control" id="item-veg" name="veg_type" required>
                        <option value="VEG">Vegetarian</option>
                        <option value="NON_VEG">Non-Vegetarian</option>
                        <option value="EGG">Contains Egg</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="item-availability">Availability</label>
                    <select class="form-control" id="item-availability" name="availability" required>
                        <option value="1">In Stock</option>
                        <option value="0">Out of Stock</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="item-sort">Sort Order</label>
                <input class="form-control" type="number" id="item-sort" name="sort_order" required value="1" min="1">
            </div>

            <div class="form-group" id="item-status-group" style="display: none;">
                <label class="form-label" for="item-status">Status</label>
                <select class="form-control" id="item-status" name="status">
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="INACTIVE">INACTIVE</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="submit" class="btn btn-primary" id="item-submit-btn" style="flex: 1;">Create Item</button>
                <button type="button" id="item-cancel-btn" onclick="resetItemForm()" class="btn btn-secondary" style="display: none;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function populateEditItemForm(item) {
        document.getElementById('item-panel-title').innerText = 'Edit Menu Item: ' + item.name;
        document.getElementById('item-category').value = item.category_id;
        document.getElementById('item-name').value = item.name;
        document.getElementById('item-desc').value = item.description || '';
        document.getElementById('item-price').value = item.price;
        document.getElementById('item-discount').value = item.discount_price || '';
        document.getElementById('item-veg').value = item.veg_type;
        document.getElementById('item-availability').value = item.availability ? "1" : "0";
        document.getElementById('item-sort').value = item.sort_order;
        document.getElementById('item-status').value = item.status;
        
        document.getElementById('item-status-group').style.display = 'block';
        document.getElementById('item-cancel-btn').style.display = 'block';
        document.getElementById('item-submit-btn').innerText = 'Save Changes';
        
        var form = document.getElementById('item-form');
        form.action = '/restaurant/menu-items/' + item.id;
        document.getElementById('item-form-method').value = 'POST';
    }

    function resetItemForm() {
        document.getElementById('item-panel-title').innerText = 'Add New Menu Item';
        document.getElementById('item-form').reset();
        document.getElementById('item-status-group').style.display = 'none';
        document.getElementById('item-cancel-btn').style.display = 'none';
        document.getElementById('item-submit-btn').innerText = 'Create Item';
        
        var form = document.getElementById('item-form');
        form.action = '{{ route('restaurant.menu-items.store') }}';
        document.getElementById('item-form-method').value = 'POST';
    }
</script>
@endsection
