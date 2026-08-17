@extends('layouts.admin')

@section('title', 'Broadcast Push Notifications - Admin')

@section('content')
    <div class="header-section">
        <div>
            <h1 class="page-title">Broadcast Push Notifications</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Dispatch announcements or platform updates via Firebase FCM</p>
        </div>
    </div>

    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 30px; max-width: 650px; backdrop-filter: blur(10px);">
        <form action="{{ route('admin.notifications.send') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label">Notification Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. System Maintenance Update" required value="{{ old('title') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Message Body</label>
                <textarea name="message" class="form-control" rows="4" placeholder="Type your message here..." required>{{ old('message') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Audience Target</label>
                <select name="target" id="targetSelect" class="form-control" onchange="toggleTargetId()">
                    <option value="all">All Registered Users</option>
                    <option value="customers">Customers Only</option>
                    <option value="restaurants">Restaurants Only</option>
                    <option value="specific_user">Specific User ID</option>
                    <option value="specific_restaurant">Specific Restaurant Users</option>
                </select>
            </div>

            <div class="form-group" id="targetIdGroup" style="display: none;">
                <label class="form-label" id="targetIdLabel">Target Record ID</label>
                <input type="number" name="target_id" class="form-control" placeholder="e.g. 5" value="{{ old('target_id') }}">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px;">Broadcast Notification</button>
        </form>
    </div>

    <script>
        function toggleTargetId() {
            var select = document.getElementById('targetSelect');
            var group = document.getElementById('targetIdGroup');
            var label = document.getElementById('targetIdLabel');

            if (select.value === 'specific_user') {
                group.style.display = 'block';
                label.innerText = 'Target User ID';
            } else if (select.value === 'specific_restaurant') {
                group.style.display = 'block';
                label.innerText = 'Target Restaurant ID';
            } else {
                group.style.display = 'none';
            }
        }
        
        // Run on page load to set correct visibility
        window.onload = toggleTargetId;
    </script>
@endsection
