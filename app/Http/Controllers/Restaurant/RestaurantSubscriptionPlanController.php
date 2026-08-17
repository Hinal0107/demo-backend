<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class RestaurantSubscriptionPlanController extends Controller
{
    use ApiResponseTrait;

    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * GET /restaurant/subscription-plans
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        $plans = SubscriptionPlan::where('restaurant_id', $restaurantId)->get();

        return $this->successResponse(
            SubscriptionPlanResource::collection($plans),
            'Subscription plans fetched successfully.'
        );
    }

    /**
     * POST /restaurant/subscription-plans
     */
    public function store(SubscriptionPlanRequest $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        try {
            $plan = $this->subscriptionService->createPlan($restaurantId, $request->validated());
            return $this->successResponse(
                new SubscriptionPlanResource($plan),
                'Subscription plan created successfully.',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * PUT /restaurant/subscription-plans/{id}
     */
    public function update(SubscriptionPlanRequest $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $plan = SubscriptionPlan::where('restaurant_id', $restaurantId)->findOrFail($id);

        try {
            $updated = $this->subscriptionService->updatePlan($plan, $request->validated());
            return $this->successResponse(
                new SubscriptionPlanResource($updated),
                'Subscription plan updated successfully.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * DELETE /restaurant/subscription-plans/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $plan = SubscriptionPlan::where('restaurant_id', $restaurantId)->findOrFail($id);

        try {
            $this->subscriptionService->deletePlan($plan);
            return $this->successResponse(null, 'Subscription plan deleted successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
