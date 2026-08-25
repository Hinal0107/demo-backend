@extends('layouts.restaurant')

@section('title', 'Daily Meal Scheduler - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">Daily Meal Scheduler</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Schedule and manage Today's and Tomorrow's meal boxes</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
    
    <!-- Scheduled Meals Listing -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Scheduled Daily Meals</h2>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Scheduled Date</th>
                        <th>Meal Box</th>
                        <th>Price</th>
                        <th>Type</th>
                        <th>Availability</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyMeals as $meal)
                        <tr>
                            <td>
                                @if($meal->image)
                                    <img src="{{ $meal->image }}" alt="Meal Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                @else
                                    <div style="width: 50px; height: 50px; border-radius: 6px; background-color: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; font-size: 10px; color: var(--text-secondary);">No Image</div>
                                @endif
                            </td>
                            <td style="font-weight: 700; color: var(--accent-primary);">
                                {{ $meal->date->format('d M, Y') }}
                                @if($meal->date->isToday())
                                    <span class="badge badge-success" style="font-size: 9px; padding: 2px 6px; margin-left: 5px;">TODAY</span>
                                @elseif($meal->date->isTomorrow())
                                    <span class="badge badge-info" style="font-size: 9px; padding: 2px 6px; margin-left: 5px;">TOMORROW</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight: 600; display: flex; align-items: center; gap: 5px;">
                                    {{ $meal->name }}
                                    @if($meal->meal_type === 'WEEKLY')
                                        <span class="badge badge-info" style="font-size: 9px; padding: 2px 6px;">WEEKLY</span>
                                    @elseif($meal->meal_type === 'TOMORROW')
                                        <span class="badge badge-warning" style="font-size: 9px; padding: 2px 6px;">TOMORROW</span>
                                    @else
                                        <span class="badge badge-success" style="font-size: 9px; padding: 2px 6px;">TODAY</span>
                                    @endif
                                </div>
                                <div style="font-size: 11px; color: var(--text-secondary);">{{ $meal->description }}</div>
                                @if($meal->addons && is_array($meal->addons) && count($meal->addons) > 0)
                                    <div style="font-size: 10px; color: var(--accent-primary); margin-top: 4px; font-weight: 600;">
                                        Add-ons: {{ implode(', ', \App\Models\Addon::whereIn('id', $meal->addons)->pluck('name')->toArray()) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($meal->discount_price)
                                    <span style="text-decoration: line-through; color: var(--text-secondary); font-size: 12px;">£{{ number_format($meal->price, 2) }}</span>
                                    <span style="font-weight: 600; color: var(--accent-primary);">£{{ number_format($meal->discount_price, 2) }}</span>
                                @else
                                    <span style="font-weight: 600;">£{{ number_format($meal->price, 2) }}</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $badge = 'badge-success';
                                    if($meal->veg_type === 'NON_VEG') $badge = 'badge-danger';
                                    elseif($meal->veg_type === 'JAIN') $badge = 'badge-info';
                                @endphp
                                <span class="badge {{ $badge }}">{{ str_replace('_', ' ', $meal->veg_type) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $meal->availability ? 'badge-success' : 'badge-danger' }}">
                                    {{ $meal->availability ? 'Available' : 'Unavailable' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $meal->status === 'ACTIVE' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $meal->status }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="populateEditForm({{ json_encode($meal) }})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                                    
                                    <form action="{{ route('restaurant.daily-meals.destroy', $meal->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this scheduled meal?')">
                                        @csrf
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                                No meals scheduled yet. Schedule one on the right!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create / Edit Panel -->
    <div class="table-container" style="padding: 25px;">
        <h2 class="table-title" id="panel-title" style="margin-bottom: 20px;">Schedule Daily Meal</h2>
        
        <!-- Autofill Section -->
        <div style="background-color: rgba(249, 115, 22, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed rgba(249, 115, 22, 0.2);">
            <label class="form-label" style="margin-bottom: 5px;">Autofill from Menu Items</label>
            <select class="form-control" onchange="autofillFromMenuItem(this.value)">
                <option value="">-- Choose Menu Item to Autofill --</option>
                @foreach($menuItems as $item)
                    <option value="{{ json_encode($item) }}">{{ $item->name }} (£{{ number_format($item->price, 2) }})</option>
                @endforeach
            </select>
        </div>

        <form id="meal-form" action="{{ route('restaurant.daily-meals.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <input type="hidden" name="_method" id="form-method" value="POST">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="meal-date">Date to Schedule</label>
                    <input class="form-control" type="date" id="meal-date" name="date" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="meal-type">Meal Type</label>
                    <select class="form-control" id="meal-type" name="meal_type" required>
                        <option value="TODAY">Today's Meal</option>
                        <option value="TOMORROW">Tomorrow's Meal</option>
                        <option value="WEEKLY">Weekly Meal</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Available Add-ons</label>
                <div style="max-height: 120px; overflow-y: auto; border: 1px solid rgba(0,0,0,0.15); padding: 10px; border-radius: 6px; background-color: white;">
                    @forelse($addons as $addon)
                        <div style="display: flex; align-items: center; margin-bottom: 8px; gap: 8px;">
                            <input type="checkbox" name="addons[]" value="{{ $addon->id }}" id="addon-{{ $addon->id }}">
                            <label for="addon-{{ $addon->id }}" style="font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; width: 100%;">
                                <span>{{ $addon->name }}</span>
                                <span style="color: var(--accent-primary); font-weight: 600;">+£{{ number_format($addon->price, 2) }}</span>
                            </label>
                        </div>
                    @empty
                        <div style="font-size: 12px; color: var(--text-secondary);">No active add-ons available. Create them in the Add-ons tab.</div>
                    @endforelse
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="meal-name">Meal Box Name</label>
                <input class="form-control" type="text" id="meal-name" name="name" required placeholder="e.g. Deluxe Gujarati Thali">
            </div>

            <div class="form-group">
                <label class="form-label" for="meal-desc">Description</label>
                <textarea class="form-control" id="meal-desc" name="description" rows="2" placeholder="e.g. 2 curries, sweet, farsan, rotlis..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="meal-image">Meal Image</label>
                <input class="form-control" type="file" id="meal-image" name="image" accept="image/*">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="meal-price">Price (£)</label>
                    <input class="form-control" type="number" step="0.01" id="meal-price" name="price" required min="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="meal-discount">Discount Price (£)</label>
                    <input class="form-control" type="number" step="0.01" id="meal-discount" name="discount_price" min="0">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="meal-veg">Dietary Type</label>
                    <select class="form-control" id="meal-veg" name="veg_type" required>
                        <option value="VEG">Vegetarian (VEG)</option>
                        <option value="NON_VEG">Non-Vegetarian</option>
                        <option value="JAIN">Jain Special</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="meal-availability">Availability</label>
                    <select class="form-control" id="meal-availability" name="availability" required>
                        <option value="1">Available</option>
                        <option value="0">Unavailable</option>
                    </select>
                </div>
            </div>

            <div class="form-group" id="status-group" style="display: none;">
                <label class="form-label" for="meal-status">Status</label>
                <select class="form-control" id="meal-status" name="status">
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="INACTIVE">INACTIVE</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="submit" class="btn btn-primary" id="submit-btn" style="flex: 1;">Schedule Meal</button>
                <button type="button" id="cancel-btn" onclick="resetForm()" class="btn btn-secondary" style="display: none;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function autofillFromMenuItem(itemJson) {
        if (!itemJson) return;
        const item = JSON.parse(itemJson);
        document.getElementById('meal-name').value = item.name;
        document.getElementById('meal-desc').value = item.description || '';
        document.getElementById('meal-price').value = item.price;
        document.getElementById('meal-discount').value = item.discount_price || '';
        // Map vegetarian types
        if(item.veg_type === 'NON_VEG') {
            document.getElementById('meal-veg').value = 'NON_VEG';
        } else if(item.veg_type === 'JAIN') {
            document.getElementById('meal-veg').value = 'JAIN';
        } else {
            document.getElementById('meal-veg').value = 'VEG';
        }
    }

    function populateEditForm(meal) {
        document.getElementById('panel-title').innerText = 'Edit Scheduled Meal';
        document.getElementById('meal-date').value = meal.date.substring(0, 10);
        document.getElementById('meal-name').value = meal.name;
        document.getElementById('meal-desc').value = meal.description || '';
        document.getElementById('meal-price').value = meal.price;
        document.getElementById('meal-discount').value = meal.discount_price || '';
        document.getElementById('meal-veg').value = meal.veg_type;
        document.getElementById('meal-availability').value = meal.availability ? "1" : "0";
        document.getElementById('meal-status').value = meal.status;
        document.getElementById('meal-type').value = meal.meal_type || 'TODAY';
        
        // Uncheck all first
        const checkboxes = document.querySelectorAll('input[name="addons[]"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        // Check selected
        if (meal.addons && Array.isArray(meal.addons)) {
            meal.addons.forEach(id => {
                const cb = document.getElementById('addon-' + id);
                if (cb) cb.checked = true;
            });
        }
        
        document.getElementById('status-group').style.display = 'block';
        document.getElementById('cancel-btn').style.display = 'block';
        document.getElementById('submit-btn').innerText = 'Save Changes';
        
        var form = document.getElementById('meal-form');
        form.action = '/restaurant/daily-meals/' + meal.id;
        document.getElementById('form-method').value = 'POST';
    }

    function resetForm() {
        document.getElementById('panel-title').innerText = 'Schedule Daily Meal';
        document.getElementById('meal-form').reset();
        document.getElementById('meal-type').value = 'TODAY';
        const checkboxes = document.querySelectorAll('input[name="addons[]"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        document.getElementById('status-group').style.display = 'none';
        document.getElementById('cancel-btn').style.display = 'none';
        document.getElementById('submit-btn').innerText = 'Schedule Meal';
        
        var form = document.getElementById('meal-form');
        form.action = '{{ route('restaurant.daily-meals.store') }}';
        document.getElementById('form-method').value = 'POST';
    }
</script>
@endsection
