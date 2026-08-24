@extends('layouts.restaurant')

@section('title', 'Subscription Plans Directory - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">Subscription & Meal Plans</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Configure long-term tiffin subscription offerings for customers</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
    
    <!-- Subscription Plans Listing -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Meal Plans Offered</h2>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Plan Name</th>
                        <th>Meal Type</th>
                        <th>Frequency</th>
                        <th>Meals Total</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td>
                                <div style="font-weight: 600;">{{ $plan->name }}</div>
                                <div style="font-size: 11px; color: var(--text-secondary);">{{ $plan->description ?: 'No description' }}</div>
                            </td>
                            <td><span class="badge badge-info">{{ ucfirst($plan->meal_type) }}</span></td>
                            <td><span class="badge badge-success">{{ ucfirst($plan->delivery_frequency) }}</span></td>
                            <td><strong>{{ $plan->total_meals }}</strong> total ({{ $plan->meals_per_day }}/day)</td>
                            <td style="font-weight: 700; color: var(--accent-primary);">£{{ number_format($plan->price, 2) }}</td>
                            <td>{{ $plan->duration_value }} {{ strtolower($plan->duration_type) }}{{ $plan->duration_value > 1 ? 's' : '' }}</td>
                            <td>
                                <span class="badge {{ $plan->status === 'ACTIVE' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $plan->status }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="populateEditForm({{ json_encode($plan) }})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                                    
                                    <form action="{{ route('restaurant.subscription-plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this subscription plan?')">
                                        @csrf
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                                No subscription plans found. Create one on the right.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create / Edit Panel -->
    <div class="table-container" style="padding: 25px;">
        <h2 class="table-title" id="panel-title" style="margin-bottom: 20px;">Create Subscription Plan</h2>
        
        <form id="plan-form" action="{{ route('restaurant.subscription-plans.store') }}" method="POST">
            @csrf
            
            <input type="hidden" name="_method" id="form-method" value="POST">

            <div class="form-group">
                <label class="form-label" for="plan-name">Plan Name</label>
                <input class="form-control" type="text" id="plan-name" name="name" required placeholder="e.g. Monthly Gujarati Lunch Box">
            </div>

            <div class="form-group">
                <label class="form-label" for="plan-desc">Description</label>
                <textarea class="form-control" id="plan-desc" name="description" rows="2" placeholder="e.g. Traditional lunch delivered Mon-Fri"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="plan-price">Price (£)</label>
                    <input class="form-control" type="number" step="0.01" id="plan-price" name="price" required min="0" placeholder="150.00">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="plan-meal-type">Meal Type</label>
                    <input class="form-control" type="text" id="plan-meal-type" name="meal_type" required placeholder="e.g. lunch, dinner">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="plan-duration-val">Duration Value</label>
                    <input class="form-control" type="number" id="plan-duration-val" name="duration_value" required min="1" value="1">
                </div>

                <div class="form-group">
                    <label class="form-label" for="plan-duration-type">Duration Type</label>
                    <select class="form-control" id="plan-duration-type" name="duration_type" required>
                        <option value="MONTH">Month(s)</option>
                        <option value="WEEK">Week(s)</option>
                        <option value="DAY">Day(s)</option>
                        <option value="CUSTOM">Custom / Meal-based</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="plan-meals-per-day">Meals Per Day</label>
                    <input class="form-control" type="number" id="plan-meals-per-day" name="meals_per_day" required min="1" value="1">
                </div>

                <div class="form-group">
                    <label class="form-label" for="plan-total-meals">Total Meals Included</label>
                    <input class="form-control" type="number" id="plan-total-meals" name="total_meals" required min="1" placeholder="e.g. 20 or 30">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="plan-freq">Delivery Frequency</label>
                    <select class="form-control" id="plan-freq" name="delivery_frequency" required>
                        <option value="daily">Daily (Mon-Sun)</option>
                        <option value="weekdays">Weekdays (Mon-Fri)</option>
                        <option value="weekends">Weekends (Sat-Sun)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="plan-start-date">Starts On (Optional)</label>
                    <input class="form-control" type="date" id="plan-start-date" name="start_date">
                </div>
            </div>

            <div class="form-group" id="status-group" style="display: none;">
                <label class="form-label" for="plan-status">Status</label>
                <select class="form-control" id="plan-status" name="status">
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="INACTIVE">INACTIVE</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="submit" class="btn btn-primary" id="submit-btn" style="flex: 1;">Create Plan</button>
                <button type="button" id="cancel-btn" onclick="resetForm()" class="btn btn-secondary" style="display: none;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function populateEditForm(plan) {
        document.getElementById('panel-title').innerText = 'Edit Plan: ' + plan.name;
        document.getElementById('plan-name').value = plan.name;
        document.getElementById('plan-desc').value = plan.description || '';
        document.getElementById('plan-price').value = plan.price;
        document.getElementById('plan-meal-type').value = plan.meal_type;
        document.getElementById('plan-duration-val').value = plan.duration_value;
        document.getElementById('plan-duration-type').value = plan.duration_type;
        document.getElementById('plan-meals-per-day').value = plan.meals_per_day;
        document.getElementById('plan-total-meals').value = plan.total_meals;
        document.getElementById('plan-freq').value = plan.delivery_frequency;
        
        if (plan.start_date) {
            document.getElementById('plan-start-date').value = plan.start_date.substring(0, 10);
        } else {
            document.getElementById('plan-start-date').value = '';
        }
        
        document.getElementById('plan-status').value = plan.status;
        
        document.getElementById('status-group').style.display = 'block';
        document.getElementById('cancel-btn').style.display = 'block';
        document.getElementById('submit-btn').innerText = 'Save Changes';
        
        var form = document.getElementById('plan-form');
        form.action = '/restaurant/subscription-plans/' + plan.id;
        document.getElementById('form-method').value = 'POST';
    }

    function resetForm() {
        document.getElementById('panel-title').innerText = 'Create Subscription Plan';
        document.getElementById('plan-form').reset();
        document.getElementById('status-group').style.display = 'none';
        document.getElementById('cancel-btn').style.display = 'none';
        document.getElementById('submit-btn').innerText = 'Create Plan';
        
        var form = document.getElementById('plan-form');
        form.action = '{{ route('restaurant.subscription-plans.store') }}';
        document.getElementById('form-method').value = 'POST';
    }
</script>
@endsection
