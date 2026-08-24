<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Tiffin Service Admin Portal')</title>
    <!-- Custom Modern Vanilla CSS Styling -->
    <style>
        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --accent-primary: #f97316;
            --accent-gradient: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            --accent-glow: 0 0 15px rgba(249, 115, 22, 0.15);
            --success: #16a34a;
            --warning: #ca8a04;
            --danger: #dc2626;
            --info: #0891b2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* Sidebar navigation */
        .sidebar {
            width: 260px;
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 20px 0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
        }

        .sidebar-logo {
            padding: 10px 25px 25px;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 1px;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
        }

        .menu-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 0 15px;
            flex-grow: 1;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.2s ease;
        }

        .menu-item a:hover {
            color: var(--text-primary);
            background-color: rgba(249, 115, 22, 0.08);
        }

        .menu-item.active a {
            background: var(--accent-gradient);
            color: var(--text-primary);
            box-shadow: var(--accent-glow);
        }

        /* Main Content Frame */
        .main-layout {
            margin-left: 260px;
            flex-grow: 1;
            padding: 40px;
            width: calc(100% - 260px);
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
        }

        /* Glassmorphic Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            backdrop-filter: blur(10px);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border-color: var(--accent-primary);
        }

        .stat-title {
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(to right, #0f172a, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Beautiful Tables */
        .table-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            margin-bottom: 40px;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 18px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: rgba(241, 245, 249, 0.6);
            padding: 15px 25px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
            letter-spacing: 0.5px;
        }

        td {
            padding: 18px 25px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: rgba(0, 0, 0, 0.015);
        }

        /* UI Badges */
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-warning {
            background-color: rgba(245, 158, 11, 0.15);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-danger {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge-info {
            background-color: rgba(6, 182, 212, 0.15);
            color: var(--info);
            border: 1px solid rgba(6, 182, 212, 0.3);
        }

        /* Form elements & buttons */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--accent-gradient);
            color: white;
            box-shadow: var(--accent-glow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            color: var(--text-primary);
            border-color: var(--text-primary);
        }

        .btn-danger {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        .btn-danger:hover {
            background-color: var(--danger);
            color: white;
        }

        .form-control {
            background: #ffffff;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--accent-primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        /* Filter block style */
        .filter-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-item {
            flex: 1;
            min-width: 180px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">Tiffin Admin</div>
        <ul class="menu-list">
            <li class="menu-item {{ Request::routeIs('admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            </li>
            <li class="menu-item {{ Request::routeIs('admin.restaurants.*') ? 'active' : '' }}">
                <a href="{{ route('admin.restaurants.index') }}">Restaurants</a>
            </li>
            <li class="menu-item {{ Request::routeIs('admin.customers.*') ? 'active' : '' }}">
                <a href="{{ route('admin.customers.index') }}">Customers</a>
            </li>
            <li class="menu-item {{ Request::routeIs('admin.orders.*') ? 'active' : '' }}">
                <a href="{{ route('admin.orders.index') }}">Orders</a>
            </li>
            <li class="menu-item {{ Request::routeIs('admin.payments.*') ? 'active' : '' }}">
                <a href="{{ route('admin.payments.index') }}">Payments</a>
            </li>
            <li class="menu-item {{ Request::routeIs('admin.subscriptions.*') ? 'active' : '' }}">
                <a href="{{ route('admin.subscriptions.index') }}">Subscriptions</a>
            </li>
            <li class="menu-item {{ Request::routeIs('admin.notifications.show') ? 'active' : '' }}">
                <a href="{{ route('admin.notifications.show') }}">Broadcast Push</a>
            </li>
            <li class="menu-item {{ Request::routeIs('admin.notifications.monitor') ? 'active' : '' }}">
                <a href="{{ route('admin.notifications.monitor') }}">Notification Logs</a>
            </li>
        </ul>
        <div style="padding: 0 15px;">
            <a href="{{ route('admin.logout') }}" class="btn btn-secondary" style="width: 100%; text-align: center; display: block;">Log Out</a>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main-layout">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" style="background-color: rgba(239, 68, 68, 0.15); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); list-style: none;">
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </div>

</body>
</html>
