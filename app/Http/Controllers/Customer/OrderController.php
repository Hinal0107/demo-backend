<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class OrderController extends Controller
{
    use ApiResponseTrait;

    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * POST /orders
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder(
                $request->user()->id,
                $request->validated()
            );

            return $this->successResponse(
                new OrderResource($order->load(['items', 'address', 'restaurant'])),
                'Order placed successfully. Please proceed to payment.',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * GET /orders
     */
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->user()->id;
        $limit = $request->query('limit', 20);
        $status = $request->query('status');
        $restaurantId = $request->query('restaurant_id');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = Order::with(['items', 'restaurant', 'address'])
            ->where('customer_id', $customerId);

        if ($status === 'active') {
            $query->whereNotIn('order_status', ['COMPLETED', 'CANCELLED']);
        } elseif ($status) {
            $query->where('order_status', strtoupper($status));
        }

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate($limit);

        return $this->paginatedResponse(
            OrderResource::collection($orders),
            'Orders fetched successfully.'
        );
    }

    /**
     * GET /orders/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::with(['items', 'restaurant', 'address', 'payments'])
            ->where('customer_id', $request->user()->id)
            ->findOrFail($id);

        return $this->successResponse(
            new OrderResource($order),
            'Order details fetched successfully.'
        );
    }

    /**
     * POST /orders/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $order = Order::where('customer_id', $request->user()->id)->findOrFail($id);

        // Define cancellation policies: cannot cancel if preparing, ready, out for delivery, or completed
        if (in_array($order->order_status, ['PREPARING', 'READY', 'COMPLETED'])) {
            return $this->errorResponse('Cannot cancel order. The kitchen has already started preparing your food.', 422);
        }

        try {
            $updated = $this->orderService->transitionOrderStatus(
                $order,
                'CANCELLED',
                $request->user()->id,
                'CUSTOMER',
                'Cancelled by customer. Reason: ' . $request->input('reason')
            );

            return $this->successResponse(
                new OrderResource($updated),
                'Order cancelled successfully.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * GET /orders/{id}/tracking
     */
    public function tracking(Request $request, int $id): JsonResponse
    {
        $order = Order::where('customer_id', $request->user()->id)->findOrFail($id);

        // Construct timeline
        $timeline = [
            [
                'status' => 'PAID',
                'completed' => in_array($order->order_status, ['PAID', 'CONFIRMED', 'PREPARING', 'READY', 'COMPLETED']),
                'timestamp' => $order->created_at?->toDateTimeString(), // Payment is initial check
            ],
            [
                'status' => 'CONFIRMED',
                'completed' => in_array($order->order_status, ['CONFIRMED', 'PREPARING', 'READY', 'COMPLETED']),
                'timestamp' => $order->confirmed_at?->toDateTimeString(),
            ],
            [
                'status' => 'PREPARING',
                'completed' => in_array($order->order_status, ['PREPARING', 'READY', 'COMPLETED']),
                'timestamp' => $order->preparing_at?->toDateTimeString(),
            ],
            [
                'status' => 'READY',
                'completed' => in_array($order->order_status, ['READY', 'COMPLETED']),
                'timestamp' => $order->ready_at?->toDateTimeString(),
            ],
            [
                'status' => 'OUT_FOR_DELIVERY',
                'completed' => in_array($order->delivery_status, ['OUT_FOR_DELIVERY', 'DELIVERED']),
                'timestamp' => $order->out_for_delivery_at?->toDateTimeString(),
            ],
            [
                'status' => 'DELIVERED',
                'completed' => $order->delivery_status === 'DELIVERED',
                'timestamp' => $order->delivered_at?->toDateTimeString(),
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Order tracking timeline fetched successfully.',
            'data' => [
                'order_id' => $order->id,
                'order_status' => $order->order_status,
                'delivery_status' => $order->delivery_status,
                'timeline' => $timeline,
            ]
        ]);
    }

    /**
     * POST /orders/{id}/confirm-received
     */
    public function confirmReceived(Request $request, int $id): JsonResponse
    {
        $order = Order::where('customer_id', $request->user()->id)->findOrFail($id);

        if (!$order->delivery_otp) {
            $order->delivery_otp = (string)rand(1000, 9999);
        }

        $order->otp_revealed = true;
        $order->save();

        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->notifyRestaurantCustomerConfirmedReceipt($order);
        } catch (\Exception $e) {}

        return $this->successResponse(
            new OrderResource($order),
            'Order receipt confirmed. Your delivery OTP is ' . $order->delivery_otp
        );
    }
}
