<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Restaurant;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class CustomerSubscriptionController extends Controller
{
    use ApiResponseTrait;

    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * GET /restaurants/{restaurantId}/subscription-plans
     */
    public function plans(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);

        $plans = SubscriptionPlan::active()
            ->where('restaurant_id', $restaurantId)
            ->get();

        return $this->successResponse(
            SubscriptionPlanResource::collection($plans),
            'Subscription plans fetched successfully.'
        );
    }

    /**
     * GET /subscription-plans/{id}
     */
    public function showPlan(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::active()->findOrFail($id);

        return $this->successResponse(
            new SubscriptionPlanResource($plan),
            'Subscription plan details fetched successfully.'
        );
    }

    /**
     * POST /subscriptions
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'restaurant_id' => 'required|integer|exists:restaurants,id',
            'subscription_plan_id' => 'required|integer|exists:subscription_plans,id',
            'start_date' => 'required|date',
            'address_id' => 'required|integer|exists:addresses,id',
            'auto_renew' => 'nullable|boolean',
        ]);

        try {
            $subscription = $this->subscriptionService->createSubscription(
                $request->user()->id,
                $request->all()
            );

            return $this->successResponse(
                new SubscriptionResource($subscription),
                'Subscribed successfully.',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * GET /subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        $subscriptions = Subscription::with(['restaurant', 'plan'])
            ->where('customer_id', $request->user()->id)
            ->get();

        return $this->successResponse(
            SubscriptionResource::collection($subscriptions),
            'Customer subscriptions fetched successfully.'
        );
    }

    /**
     * GET /subscriptions/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::with(['restaurant', 'plan'])
            ->where('customer_id', $request->user()->id)
            ->findOrFail($id);

        return $this->successResponse(
            new SubscriptionResource($subscription),
            'Subscription details fetched successfully.'
        );
    }

    /**
     * POST /subscriptions/{id}/pause
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::where('customer_id', $request->user()->id)->findOrFail($id);

        try {
            $updated = $this->subscriptionService->pauseSubscription($subscription);
            return $this->successResponse(
                new SubscriptionResource($updated),
                'Subscription paused successfully.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * POST /subscriptions/{id}/resume
     */
    public function resume(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::where('customer_id', $request->user()->id)->findOrFail($id);

        try {
            $updated = $this->subscriptionService->resumeSubscription($subscription);
            return $this->successResponse(
                new SubscriptionResource($updated),
                'Subscription resumed successfully.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * POST /subscriptions/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::where('customer_id', $request->user()->id)->findOrFail($id);

        try {
            $updated = $this->subscriptionService->cancelSubscription($subscription);
            return $this->successResponse(
                new SubscriptionResource($updated),
                'Subscription cancelled successfully.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
