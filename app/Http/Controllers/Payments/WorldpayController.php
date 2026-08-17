<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\WorldpayService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class WorldpayController extends Controller
{
    use ApiResponseTrait;

    protected WorldpayService $worldpayService;

    public function __construct(WorldpayService $worldpayService)
    {
        $this->worldpayService = $worldpayService;
    }

    /**
     * POST /api/v1/payments/worldpay/webhook
     * Secure Webhook Endpoint.
     */
    public function webhook(Request $request): JsonResponse
    {
        Log::info('Worldpay Webhook received.', ['payload' => $request->all()]);

        try {
            $result = $this->worldpayService->processWebhook($request->all());
            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Worldpay Webhook Error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * POST /api/v1/payments/worldpay/simulate
     * Dev Sandbox Helper: Simulate Worldpay webhook processing.
     */
    public function simulate(Request $request): JsonResponse
    {
        $request->validate([
            'order_number' => 'required|string|exists:orders,order_number',
            'status' => 'nullable|string|in:PAID,FAILED,paid,failed',
        ]);

        $orderNumber = $request->input('order_number');
        $status = strtoupper($request->input('status', 'PAID'));

        // Resolve order
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // Build a mock webhook payload
        $payload = [
            'event_id' => 'evt_' . Str::random(12),
            'transaction_id' => 'tx_' . Str::random(12),
            'reference' => 'WP-REF-' . $orderNumber,
            'status' => $status,
            'amount' => (float)$order->total_amount,
            'response_code' => '00',
            'signature' => 'mock-signature',
        ];

        try {
            $result = $this->worldpayService->processWebhook($payload);
            return $this->successResponse($result, 'Simulated Worldpay payment successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * POST /api/v1/orders/{id}/refund
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $order = Order::findOrFail($id);

        // Security check: Only SUPER_ADMIN or RESTAURANT linked to this order can refund
        if ($user->isRestaurant()) {
            $restaurantId = $request->attributes->get('restaurant_id');
            if ((int)$order->restaurant_id !== (int)$restaurantId) {
                return $this->errorResponse('Forbidden. This order does not belong to your restaurant.', 403);
            }
        } elseif (!$user->isSuperAdmin()) {
            return $this->errorResponse('Forbidden. You do not have permissions to issue refunds.', 403);
        }

        try {
            $payment = $this->worldpayService->refundOrder(
                $order,
                (float)$request->input('refund_amount'),
                $request->input('reason')
            );

            return $this->successResponse(
                $payment,
                'Refund processed successfully.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }
}
