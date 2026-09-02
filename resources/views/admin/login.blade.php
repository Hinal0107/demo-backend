<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/admin-favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('images/admin-favicon-48x48.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/admin-favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/admin-favicon-180x180.png') }}">
    <link rel="shortcut icon" href="{{ asset('admin-favicon.ico') }}">
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
            --danger: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo-img {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 16px;
            margin-bottom: 12px;
            box-shadow: 0 4px 15px rgba(22, 101, 52, 0.25);
            background-color: #ffffff;
            padding: 4px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-secondary);
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

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            border: none;
            background: var(--accent-gradient);
            color: white;
            box-shadow: var(--accent-glow);
            transition: all 0.2s ease;
            text-align: center;
            text-decoration: none;
        }

        .btn:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="card-header">
            <img src="{{ asset('images/admin-logo.png') }}" alt="Tiffin Admin Logo" class="login-logo-img">
            <div class="logo">Tiffin Admin</div>
            <div class="subtitle">Sign in to manage the platform</div>
        </div>

        @if($errors->any())
            <div class="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input class="form-control" type="email" id="email" name="email" required autofocus placeholder="admin@tiffin.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn">Sign In</button>
        </form>
    </div>

</body>
</html>
