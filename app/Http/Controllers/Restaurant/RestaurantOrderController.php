<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class RestaurantOrderController extends Controller
{
    use ApiResponseTrait;

    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * GET /restaurant/orders
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $status = $request->query('status');
        $deliveryStatus = $request->query('delivery_status');
        $search = $request->query('search');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $limit = $request->query('limit', 20);

        $query = Order::with(['items', 'customer', 'address'])
            ->where('restaurant_id', $restaurantId);

        if ($status) {
            $query->where('order_status', strtoupper($status));
        }

        if ($deliveryStatus) {
            $query->where('delivery_status', strtoupper($deliveryStatus));
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function ($sub) use ($search) {
                      $sub->where('name', 'like', '%' . $search . '%')
                          ->orWhere('phone', 'like', '%' . $search . '%');
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate($limit);

        return $this->paginatedResponse(
            OrderResource::collection($orders),
            'Restaurant orders fetched successfully.'
        );
    }

    /**
     * GET /restaurant/orders/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $restaurantId = $request->attributes->get('restaurant_id');
            $order = Order::with(['items', 'customer', 'address', 'payments'])->findOrFail($id);

            if ((int)$order->restaurant_id !== (int)$restaurantId) {
                return $this->errorResponse('Forbidden. You do not have permissions to access this restaurant\'s data.', 403);
            }

            return $this->successResponse(
                new OrderResource($order),
                'Order details fetched successfully.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 404);
        }
    }

    /**
     * POST /restaurant/orders/{id}/confirm
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        try {
            $order = Order::findOrFail($id);
            if ((int)$order->restaurant_id !== (int)$restaurantId) {
                throw new Exception('Forbidden. You do not have permissions to access this restaurant\'s data.', 403);
            }

            $updated = $this->orderService->transitionOrderStatus(
                $order,
                'CONFIRMED',
                $request->user()->id,
                'RESTAURANT',
                'Confirmed by restaurant.'
            );
            return $this->successResponse(new OrderResource($updated), 'Order confirmed successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * POST /restaurant/orders/{id}/preparing
     */
    public function preparing(Request $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        try {
            $order = Order::findOrFail($id);
            if ((int)$order->restaurant_id !== (int)$restaurantId) {
                throw new Exception('Forbidden. You do not have permissions to access this restaurant\'s data.', 403);
            }

            $updated = $this->orderService->transitionOrderStatus(
                $order,
                'PREPARING',
                $request->user()->id,
                'RESTAURANT',
                'Started preparing.'
            );
            return $this->successResponse(new OrderResource($updated), 'Order status updated to preparing.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * POST /restaurant/orders/{id}/ready
     */
    public function ready(Request $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        try {
            $order = Order::findOrFail($id);
            if ((int)$order->restaurant_id !== (int)$restaurantId) {
                throw new Exception('Forbidden. You do not have permissions to access this restaurant\'s data.', 403);
            }

            $updated = $this->orderService->transitionOrderStatus(
                $order,
                'READY',
                $request->user()->id,
                'RESTAURANT',
                'Meal is ready.'
            );
            return $this->successResponse(new OrderResource($updated), 'Order marked as ready.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * POST /restaurant/orders/{id}/out-for-delivery
     */
    public function outForDelivery(Request $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        try {
            $order = Order::findOrFail($id);
            if ((int)$order->restaurant_id !== (int)$restaurantId) {
                throw new Exception('Forbidden. You do not have permissions to access this restaurant\'s data.', 403);
            }

            $updated = $this->orderService->transitionDeliveryStatus(
                $order,
                'OUT_FOR_DELIVERY',
                $request->user()->id,
                'RESTAURANT',
                ['remarks' => 'Dispatched for delivery.']
            );
            return $this->successResponse(
                new OrderResource($updated),
                'Order dispatched out for delivery. OTP generated: ' . $updated->delivery_otp
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * POST /restaurant/orders/{id}/delivered
     */
    public function delivered(Request $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        $request->validate([
            'delivery_otp' => 'nullable|string',
        ]);

        try {
            $order = Order::findOrFail($id);
            if ((int)$order->restaurant_id !== (int)$restaurantId) {
                throw new Exception('Forbidden. You do not have permissions to access this restaurant\'s data.', 403);
            }

            $updated = $this->orderService->transitionDeliveryStatus(
                $order,
                'DELIVERED',
                $request->user()->id,
                'RESTAURANT',
                [
                    'delivery_otp' => $request->input('delivery_otp'),
                    'remarks' => 'Delivered to customer.'
                ]
            );
            return $this->successResponse(new OrderResource($updated), 'Order marked as delivered successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * POST /restaurant/orders/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $restaurantId = $request->attributes->get('restaurant_id');

        try {
            $order = Order::findOrFail($id);
            if ((int)$order->restaurant_id !== (int)$restaurantId) {
                throw new Exception('Forbidden. You do not have permissions to access this restaurant\'s data.', 403);
            }

            $updated = $this->orderService->transitionOrderStatus(
                $order,
                'CANCELLED',
                $request->user()->id,
                'RESTAURANT',
                'Cancelled by restaurant. Reason: ' . $request->input('reason')
            );
            return $this->successResponse(new OrderResource($updated), 'Order cancelled by restaurant.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }
}
