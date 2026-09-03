<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWorldpaySessionRequest;
use App\Models\Order;
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
     * POST /api/payments/worldpay/create-session
     * Create Access Worldpay HPP Checkout Session URL.
     */
    public function createSession(CreateWorldpaySessionRequest $request): JsonResponse
    {
        try {
            $result = $this->worldpayService->createCheckoutSession($request->validated());
            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Worldpay createSession error: ' . $e->getMessage());
            $code = is_numeric($e->getCode()) && (int)$e->getCode() >= 100 && (int)$e->getCode() < 600 ? (int)$e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    /**
     * POST /api/payments/worldpay/webhook
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
            $code = is_numeric($e->getCode()) && (int)$e->getCode() >= 100 && (int)$e->getCode() < 600 ? (int)$e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $code);
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
            $code = is_numeric($e->getCode()) && (int)$e->getCode() >= 100 && (int)$e->getCode() < 600 ? (int)$e->getCode() : 422;
            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    /**
     * GET /payment-success
     * Worldpay Payment Success return page.
     */
    public function paymentSuccess(Request $request)
    {
        return response()->make('
            <!DOCTYPE html>
            <html>
            <head>
                <title>Payment Successful</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body { font-family: system-ui, -apple-system, sans-serif; text-align: center; padding: 50px 20px; background-color: #f8fafc; color: #1e293b; }
                    .card { background: white; max-width: 440px; margin: 0 auto; padding: 40px 30px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
                    .icon-box { width: 64px; height: 64px; background: #dcfce7; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px auto; }
                    h1 { font-size: 22px; font-weight: 700; margin-bottom: 10px; color: #0f172a; }
                    p { font-size: 15px; color: #64748b; line-height: 1.5; margin-bottom: 0; }
                </style>
            </head>
            <body>
                <div class="card">
                    <div class="icon-box">&#10003;</div>
                    <h1>Payment Successful</h1>
                    <p>Thank you! Your payment has been processed successfully. You can return to the mobile application now.</p>
                </div>
            </body>
            </html>
        ', 200, ['Content-Type' => 'text/html']);
    }

    /**
     * GET /payment-failed
     * Worldpay Payment Failure return page.
     */
    public function paymentFailed(Request $request)
    {
        return response()->make('
            <!DOCTYPE html>
            <html>
            <head>
                <title>Payment Failed</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body { font-family: system-ui, -apple-system, sans-serif; text-align: center; padding: 50px 20px; background-color: #f8fafc; color: #1e293b; }
                    .card { background: white; max-width: 440px; margin: 0 auto; padding: 40px 30px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
                    .icon-box { width: 64px; height: 64px; background: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px auto; }
                    h1 { font-size: 22px; font-weight: 700; margin-bottom: 10px; color: #0f172a; }
                    p { font-size: 15px; color: #64748b; line-height: 1.5; margin-bottom: 0; }
                </style>
            </head>
            <body>
                <div class="card">
                    <div class="icon-box">&#10007;</div>
                    <h1>Payment Failed</h1>
                    <p>Your payment could not be processed. Please check your payment details and try again.</p>
                </div>
            </body>
            </html>
        ', 200, ['Content-Type' => 'text/html']);
    }
}
