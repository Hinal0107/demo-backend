<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminActivityLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCustomerController extends Controller
{
    protected AdminActivityLoggerService $activityLogger;

    public function __construct(AdminActivityLoggerService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = User::where('role', 'CUSTOMER');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Show details of a customer.
     */
    public function show(int $id)
    {
        $customer = User::with(['addresses', 'orders', 'subscriptions'])->findOrFail($id);
        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Update customer status (ACTIVE, INACTIVE, BLOCKED).
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:ACTIVE,INACTIVE,BLOCKED']);

        $customer = User::findOrFail($id);
        $oldStatus = $customer->status;
        
        $customer->status = $request->input('status');
        $customer->save();

        $this->activityLogger->log(
            Auth::id(),
            'UPDATE_CUSTOMER_STATUS',
            'users',
            $customer->id,
            ['status' => $oldStatus],
            ['status' => $customer->status]
        );

        return back()->with('success', "Customer account status has been updated to {$customer->status}.");
    }
}
