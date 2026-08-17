<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        $restaurantId = $request->query('restaurant_id');
        $status = $request->query('status');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $transactionId = $request->query('transaction_id');

        $query = Payment::with(['order', 'customer', 'restaurant']);

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        if ($status) {
            $query->where('status', strtoupper($status));
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        if ($transactionId) {
            $query->where('worldpay_transaction_id', 'like', '%' . $transactionId . '%');
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);
        $restaurants = Restaurant::all();

        return view('admin.payments.index', compact('payments', 'restaurants'));
    }

    /**
     * Display payment detail view.
     */
    public function show(int $id)
    {
        $payment = Payment::with(['order', 'customer', 'restaurant'])->findOrFail($id);
        return view('admin.payments.show', compact('payment'));
    }
}
