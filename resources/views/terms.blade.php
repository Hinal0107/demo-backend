<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - Tiffin Subscription Service</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF6B00;
            --primary-dark: #E05D00;
            --bg-dark: #121418;
            --card-bg: #1E222A;
            --border-color: #2E3440;
            --text-main: #ECEFF4;
            --text-muted: #D8DEE9;
            --accent-gold: #FFD700;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            line-height: 1.6;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 860px;
            margin: 0 auto;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }

        .header {
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .badge {
            display: inline-block;
            background: rgba(255, 107, 0, 0.15);
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #FFFFFF;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 1.2rem;
            background: var(--primary);
            border-radius: 2px;
        }

        .section-content {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        ul {
            list-style: none;
            padding-left: 0;
            margin: 1rem 0;
        }

        ul li {
            position: relative;
            padding-left: 1.8rem;
            margin-bottom: 0.75rem;
            color: var(--text-muted);
        }

        ul li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: bold;
        }

        .highlight-box {
            background: rgba(255, 215, 0, 0.08);
            border-left: 4px solid var(--accent-gold);
            padding: 1.2rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .highlight-box h4 {
            color: var(--accent-gold);
            font-size: 1.05rem;
            margin-bottom: 0.5rem;
        }

        .footer {
            margin-top: 3rem;
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Terms & Conditions</h1>
            <p>Tiffin Service Subscription Policies & Meal Validity Rules</p>
            <span class="badge">Last Updated: August 26, 2026</span>
        </div>

        <!-- 1. Free Trial -->
        <div class="section">
            <h2 class="section-title">1. Free Trial & Initial Access Period</h2>
            <div class="section-content">
                <p>Newly registered users receive an initial free trial period of <strong>7 days</strong> from their account creation date. During this free trial, users can explore daily meal menus, weekly menus, and restaurant details free of charge.</p>
                <p>Once the free trial period expires, users must purchase an active subscription plan to view protected subscription meal details and place daily meal orders.</p>
            </div>
        </div>

        <!-- 2. Subscription Duration & Expiry Rules -->
        <div class="section">
            <h2 class="section-title">2. Subscription Duration & Pending Meals Expiry</h2>
            <div class="section-content">
                <p>Subscription plans include a defined meal count and a maximum validity duration window to consume those meals:</p>
                <ul>
                    <li><strong>Weekly Subscription Plan:</strong> Includes 7 meals, with a maximum validity window of <strong>14 days</strong> from the plan start date.</li>
                    <li><strong>Monthly Subscription Plan:</strong> Includes 30 meals, with a maximum validity window of <strong>60 days</strong> from the plan start date.</li>
                </ul>
                <div class="highlight-box">
                    <h4>Important Pending Meal Expiry Rule</h4>
                    <p>If a customer orders meals for only 5 days under a Weekly plan and has 2 remaining meals, those 2 meals MUST be consumed within 14 days of the plan start date. If not consumed within 14 days, the subscription plan will automatically expire, and all remaining meals will be forfeited and no longer usable.</p>
                </div>
            </div>
        </div>

        <!-- 3. Expiry Reminders -->
        <div class="section">
            <h2 class="section-title">3. Expiry Reminders & Notifications</h2>
            <div class="section-content">
                <p>To ensure you never lose remaining meals, automated reminders will be sent to your account when your plan is near expiration. For example, <strong>2 days prior to expiration</strong>, your plan screen will display:</p>
                <div style="background: #2A2F3A; border: 1px solid var(--primary); padding: 1rem; border-radius: 8px; text-align: center; font-weight: 600; color: var(--primary); margin: 1rem 0;">
                    “Your plan will expire in 2 days.”
                </div>
            </div>
        </div>

        <!-- 4. Add-ons Separate Payments -->
        <div class="section">
            <h2 class="section-title">4. Add-on Charges & Separate Payment Requirements</h2>
            <div class="section-content">
                <p>Subscriptions cover ONLY the meals explicitly defined in the selected plan. Additional items or add-ons (side dishes, beverages, extra rotis, special items) are NOT included in the subscription price.</p>
                <ul>
                    <li>Subscription-included meals are billed at £0.00 during order checkout.</li>
                    <li>Any add-ons beyond the subscription meals require a <strong>separate payment</strong> at the time of order placement.</li>
                    <li>Order invoice and checkout screens will clearly separate subscription meals from additional add-on charges.</li>
                </ul>
            </div>
        </div>

        <!-- 5. General Conditions -->
        <div class="section">
            <h2 class="section-title">5. Plan Management & Pausing</h2>
            <div class="section-content">
                <p>Customers may pause or resume their subscription plan through the mobile application or website prior to daily kitchen cutoff times. Pausing a plan extends the daily delivery schedule but does NOT extend the plan beyond the maximum validity date (14 days for weekly, 60 days for monthly).</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2026 Tiffin Service Backend Application. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
