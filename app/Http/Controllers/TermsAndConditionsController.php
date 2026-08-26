<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TermsAndConditionsController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get Terms & Conditions API response.
     * GET /api/v1/terms-and-conditions
     */
    public function index(): JsonResponse
    {
        $termsData = [
            'title' => 'Terms & Conditions - Tiffin Subscription Service',
            'last_updated' => '2026-08-26',
            'sections' => [
                [
                    'id' => 'free_trial',
                    'title' => '1. Free Trial & Initial Period Policy',
                    'content' => 'Newly registered users receive an initial free period (7 days) during which they can view daily and weekly meal menus and relevant details. Once the free period expires, users must purchase an active subscription plan to continue viewing protected meal details and ordering subscription meals.'
                ],
                [
                    'id' => 'validity_expiry',
                    'title' => '2. Subscription Duration & Pending Meals Expiry Rules',
                    'content' => 'Subscription plans are valid for a specified number of included meals and a maximum validity duration window:
- Weekly Subscription Plan: Includes 7 meals, with a maximum validity window of 14 days from the plan start date.
- Monthly Subscription Plan: Includes 30 meals, with a maximum validity window of 60 days from the plan start date.
If any included meals remain pending or unused after the maximum validity window (14 days for weekly, 60 days for monthly), the subscription plan will automatically expire, and all unused meals will be forfeited and rendered unusable.'
                ],
                [
                    'id' => 'expiry_reminders',
                    'title' => '3. Expiry Reminders & Notifications',
                    'content' => 'Customers will receive automated push notifications and in-app reminder banners when their subscription plan is approaching expiration (e.g., "Your plan will expire in 2 days.").'
                ],
                [
                    'id' => 'addons_policy',
                    'title' => '4. Add-ons & Separate Payment Requirements',
                    'content' => 'Subscription plans cover strictly the meals included in the selected plan. Any additional add-ons (side dishes, drinks, extra items) selected beyond the subscription meals are NOT included in the subscription price. Customers must make a separate payment for all add-ons and associated taxes or delivery charges at the time of ordering.'
                ],
                [
                    'id' => 'cancellation_refunds',
                    'title' => '5. Cancellations & Modifications',
                    'content' => 'Subscriptions may be paused or cancelled prior to meal preparation. Once a daily meal preparation has commenced for a scheduled delivery date, that day\'s meal count is deducted.'
                ]
            ]
        ];

        return $this->successResponse($termsData, 'Terms & Conditions fetched successfully.');
    }

    /**
     * Render Web Terms & Conditions page.
     * GET /terms
     */
    public function showWeb(): View
    {
        return view('terms');
    }
}
