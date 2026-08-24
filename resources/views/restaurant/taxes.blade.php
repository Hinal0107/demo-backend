@extends('layouts.restaurant')

@section('title', 'Taxes Configurations - ' . $restaurant->name)

@section('content')
<div class="header-section">
    <div>
        <h1 class="page-title">Tax Configuration</h1>
        <p style="color: var(--text-secondary); margin-top: 5px;">Configure GST, VAT, or other service charges applicable to meals</p>
    </div>
    <div class="restaurant-badge">
        {{ $restaurant->name }}
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
    
    <!-- Taxes Listing -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Tax Rules</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tax Name</th>
                    <th>Rate (%)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($taxes as $tax)
                    <tr>
                        <td style="font-weight: 600;">{{ $tax->name }}</td>
                        <td style="font-weight: 600; color: var(--accent-primary);">{{ number_format($tax->rate, 2) }}%</td>
                        <td>
                            <span class="badge {{ $tax->status === 'ACTIVE' ? 'badge-success' : 'badge-warning' }}">
                                {{ $tax->status }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <button onclick="populateEditForm({{ json_encode($tax) }})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                                
                                <form action="{{ route('restaurant.taxes.destroy', $tax->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tax rule?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                            No custom tax configurations found. The system is falling back to a flat 10% rate. Define custom rules on the right.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Create / Edit Panel -->
    <div class="table-container" style="padding: 25px;">
        <h2 class="table-title" id="panel-title" style="margin-bottom: 20px;">Add Tax Rule</h2>
        
        <form id="tax-form" action="{{ route('restaurant.taxes.store') }}" method="POST">
            @csrf
            
            <input type="hidden" name="_method" id="form-method" value="POST">

            <div class="form-group">
                <label class="form-label" for="tax-name">Tax Description</label>
                <input class="form-control" type="text" id="tax-name" name="name" required placeholder="e.g. GST / VAT / Service Charge">
            </div>

            <div class="form-group">
                <label class="form-label" for="tax-rate">Rate Percentage (%)</label>
                <input class="form-control" type="number" step="0.01" id="tax-rate" name="rate" required min="0" max="100" placeholder="e.g. 5.00 or 12.50">
            </div>

            <div class="form-group" id="status-group" style="display: none;">
                <label class="form-label" for="tax-status">Status</label>
                <select class="form-control" id="tax-status" name="status">
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="INACTIVE">INACTIVE</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="submit" class="btn btn-primary" id="submit-btn" style="flex: 1;">Create Tax Rule</button>
                <button type="button" id="cancel-btn" onclick="resetForm()" class="btn btn-secondary" style="display: none;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function populateEditForm(tax) {
        document.getElementById('panel-title').innerText = 'Edit Tax Rule: ' + tax.name;
        document.getElementById('tax-name').value = tax.name;
        document.getElementById('tax-rate').value = tax.rate;
        document.getElementById('tax-status').value = tax.status;
        
        document.getElementById('status-group').style.display = 'block';
        document.getElementById('cancel-btn').style.display = 'block';
        document.getElementById('submit-btn').innerText = 'Save Changes';
        
        var form = document.getElementById('tax-form');
        form.action = '/restaurant/taxes/' + tax.id;
        document.getElementById('form-method').value = 'POST';
    }

    function resetForm() {
        document.getElementById('panel-title').innerText = 'Add Tax Rule';
        document.getElementById('tax-form').reset();
        document.getElementById('status-group').style.display = 'none';
        document.getElementById('cancel-btn').style.display = 'none';
        document.getElementById('submit-btn').innerText = 'Create Tax Rule';
        
        var form = document.getElementById('tax-form');
        form.action = '{{ route('restaurant.taxes.store') }}';
        document.getElementById('form-method').value = 'POST';
    }
</script>
@endsection
