<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\AdminActivityLog;
use App\Services\NotificationService;
use App\Services\AdminActivityLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    protected NotificationService $notificationService;
    protected AdminActivityLoggerService $activityLogger;

    public function __construct(NotificationService $notificationService, AdminActivityLoggerService $activityLogger)
    {
        $this->notificationService = $notificationService;
        $this->activityLogger = $activityLogger;
    }

    /**
     * Show admin login view.
     */
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    /**
     * Handle admin login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->isSuperAdmin()) {
                $request->session()->regenerate();
                $this->activityLogger->log($user->id, 'LOGIN', 'auth', $user->id);
                return redirect()->route('admin.dashboard');
            }
            Auth::logout();
            return back()->withErrors(['email' => 'Access denied. You do not have administrator privileges.']);
        }

        return back()->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    /**
     * Log out admin.
     */
    public function logout(Request $request)
    {
        $adminId = Auth::id();
        if ($adminId) {
            $this->activityLogger->log($adminId, 'LOGOUT', 'auth', $adminId);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    /**
     * Show admin statistics overview dashboard.
     */
    public function index()
    {
        $today = Carbon::today();

        $stats = [
            'total_restaurants' => Restaurant::count(),
            'active_restaurants' => Restaurant::where('status', 'ACTIVE')->count(),
            'total_customers' => User::where('role', 'CUSTOMER')->count(),
            'today_orders' => Order::whereDate('created_at', $today)->count(),
            'pending_payments' => Payment::where('status', 'PENDING')->count(),
            'successful_payments' => Payment::where('status', 'PAID')->count(),
            'today_revenue' => Payment::where('status', 'PAID')->whereDate('paid_at', $today)->sum('amount'),
            'total_revenue' => Payment::where('status', 'PAID')->sum('amount'),
            'active_subscriptions' => Subscription::where('status', 'ACTIVE')->count(),
            'delivered_orders' => Order::where('delivery_status', 'DELIVERED')->count(),
            'cancelled_orders' => Order::where('order_status', 'CANCELLED')->count(),
        ];

        $recentActivities = AdminActivityLog::with('admin')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentActivities'));
    }

    /**
     * Show broadcast system notification page.
     */
    public function showNotifications()
    {
        return view('admin.notifications');
    }

    /**
     * Send system update notifications.
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'target' => 'required|string|in:all,customers,restaurants,specific_user,specific_restaurant',
            'target_id' => 'required_if:target,specific_user,specific_restaurant|nullable|integer',
        ]);

        $title = $request->input('title');
        $message = $request->input('message');
        $target = $request->input('target');
        $targetId = $request->input('target_id');

        $users = collect();

        if ($target === 'all') {
            $users = User::whereIn('role', ['CUSTOMER', 'RESTAURANT'])->get();
        } elseif ($target === 'customers') {
            $users = User::where('role', 'CUSTOMER')->get();
        } elseif ($target === 'restaurants') {
            $users = User::where('role', 'RESTAURANT')->get();
        } elseif ($target === 'specific_user') {
            $users = collect([User::findOrFail($targetId)]);
        } elseif ($target === 'specific_restaurant') {
            // Find users linked to specific restaurant
            $users = User::whereHas('restaurantUsers', function ($q) use ($targetId) {
                $q->where('restaurant_id', $targetId);
            })->get();
        }

        $sentCount = 0;
        foreach ($users as $user) {
            $this->notificationService->sendNotification($user, 'system_update', $title, $message, [
                'sender' => 'SUPER_ADMIN'
            ]);
            $sentCount++;
        }

        $this->activityLogger->log(
            Auth::id(),
            'BROADCAST_NOTIFICATION',
            'notifications',
            null,
            null,
            ['target' => $target, 'title' => $title, 'sent_count' => $sentCount]
        );

        return back()->with('success', "Notification broadcasted successfully to {$sentCount} users.");
    }
}
