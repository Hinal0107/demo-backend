@extends('layouts.restaurant')

@section('title', 'Menu Categories - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">Menu Categories</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Group meals, thalis, and individual items</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
    
    <!-- Listing -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Categories</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Sort Order</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>
                            @if($category->image)
                                <img src="{{ $category->image }}" alt="Category Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                            @else
                                <div style="width: 50px; height: 50px; border-radius: 6px; background-color: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; font-size: 10px; color: var(--text-secondary);">No Image</div>
                            @endif
                        </td>
                        <td style="font-weight: 600;">{{ $category->sort_order }}</td>
                        <td style="font-weight: 600;">{{ $category->name }}</td>
                        <td>{{ $category->description ?: 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $category->status === 'ACTIVE' ? 'badge-success' : 'badge-warning' }}">
                                {{ $category->status }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <!-- Simple Toggle Form / JS could populate the edit side, or we just submit inline edits -->
                                <button onclick="populateEditForm({{ json_encode($category) }})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                                
                                <form action="{{ route('restaurant.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                            No categories found. Create one on the right.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Create / Edit Panel -->
    <div class="table-container" style="padding: 25px;">
        <h2 class="table-title" id="panel-title" style="margin-bottom: 20px;">Add New Category</h2>
        
        <form id="category-form" action="{{ route('restaurant.categories.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <input type="hidden" name="_method" id="form-method" value="POST">

            <div class="form-group">
                <label class="form-label" for="cat-name">Category Name</label>
                <input class="form-control" type="text" id="cat-name" name="name" required placeholder="e.g. Punjabi Thalis">
            </div>

            <div class="form-group">
                <label class="form-label" for="cat-desc">Description</label>
                <input class="form-control" type="text" id="cat-desc" name="description" placeholder="Short summary of items">
            </div>

            <div class="form-group">
                <label class="form-label" for="cat-image">Category Image</label>
                <input class="form-control" type="file" id="cat-image" name="image" accept="image/*">
            </div>

            <div class="form-group">
                <label class="form-label" for="cat-sort">Sort Order</label>
                <input class="form-control" type="number" id="cat-sort" name="sort_order" required value="1" min="1">
            </div>

            <div class="form-group" id="status-group" style="display: none;">
                <label class="form-label" for="cat-status">Status</label>
                <select class="form-control" id="cat-status" name="status">
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="INACTIVE">INACTIVE</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="submit" class="btn btn-primary" id="submit-btn" style="flex: 1;">Create Category</button>
                <button type="button" id="cancel-btn" onclick="resetForm()" class="btn btn-secondary" style="display: none;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function populateEditForm(category) {
        document.getElementById('panel-title').innerText = 'Edit Category: ' + category.name;
        document.getElementById('cat-name').value = category.name;
        document.getElementById('cat-desc').value = category.description || '';
        document.getElementById('cat-sort').value = category.sort_order;
        document.getElementById('cat-status').value = category.status;
        
        document.getElementById('status-group').style.display = 'block';
        document.getElementById('cancel-btn').style.display = 'block';
        document.getElementById('submit-btn').innerText = 'Save Changes';
        
        // Dynamically change form action and method to UPDATE
        var form = document.getElementById('category-form');
        form.action = '/restaurant/categories/' + category.id;
        document.getElementById('form-method').value = 'POST'; // POST route matches Controller parameter logic
    }

    function resetForm() {
        document.getElementById('panel-title').innerText = 'Add New Category';
        document.getElementById('category-form').reset();
        document.getElementById('status-group').style.display = 'none';
        document.getElementById('cancel-btn').style.display = 'none';
        document.getElementById('submit-btn').innerText = 'Create Category';
        
        var form = document.getElementById('category-form');
        form.action = '{{ route('restaurant.categories.store') }}';
        document.getElementById('form-method').value = 'POST';
    }
</script>
@endsection
