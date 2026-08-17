<?php

namespace App\Services;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\RestaurantUser;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AuthService
{
    protected ImageUploadService $imageService;

    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Register a new user in the system.
     *
     * @param array $data
     * @return User
     * @throws Exception
     */
    public function register(array $data): User
    {
        if (strtolower($data['role']) === 'super_admin') {
            throw new Exception('Super Admin registration is not allowed.', 403);
        }

        return DB::transaction(function () use ($data) {
            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'firebase_uid' => $data['firebase_uid'] ?? null,
                'role' => strtoupper($data['role']),
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'status' => 'ACTIVE',
            ]);

            // If user is a RESTAURANT, initialize a pending restaurant relationship
            if ($user->role === 'RESTAURANT') {
                $restaurant = Restaurant::create([
                    'name' => $data['restaurant_name'] ?? ($user->name . "'s Tiffin Service"),
                    'email' => $user->email,
                    'phone' => $user->phone ?? '0000000000',
                    'address' => $data['restaurant_address'] ?? 'Pending Address',
                    'city' => $data['restaurant_city'] ?? 'Pending City',
                    'state' => $data['restaurant_state'] ?? 'Pending State',
                    'country' => $data['restaurant_country'] ?? 'Pending Country',
                    'pincode' => $data['restaurant_pincode'] ?? '000000',
                    'opening_time' => '09:00:00',
                    'closing_time' => '21:00:00',
                    'status' => 'PENDING_APPROVAL',
                ]);

                RestaurantUser::create([
                    'restaurant_id' => $restaurant->id,
                    'user_id' => $user->id,
                    'role' => 'owner',
                    'status' => 'ACTIVE',
                ]);
            }

            return $user;
        });
    }

    /**
     * Log in a user and handle FCM registration.
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function login(array $data): array
    {
        $user = User::where('firebase_uid', $data['firebase_uid'])->first();

        if (!$user) {
            throw new Exception('Account not found in our database. Please register first.', 404);
        }

        if ($user->status === 'BLOCKED') {
            throw new Exception('Your account is blocked by administrative decision.', 403);
        }

        // Generate Sanctum access token
        $tokenName = 'AuthToken_' . $user->id;
        $token = $user->createToken($tokenName)->plainTextToken;

        // Associate FCM Token if provided
        if (!empty($data['fcm_token'])) {
            FcmToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_id' => $data['device_id'] ?? null,
                ],
                [
                    'token' => $data['fcm_token'],
                    'device_type' => $data['device_type'] ?? 'unknown',
                    'status' => 'ACTIVE',
                    'last_used_at' => now(),
                ]
            );
        }

        // Resolve linked restaurant details
        $restaurant = null;
        if ($user->isRestaurant()) {
            $restaurant = $user->restaurant;
        }

        return [
            'user' => $user,
            'token' => $token,
            'role' => strtolower($user->role),
            'restaurant' => $restaurant,
        ];
    }

    /**
     * Log out a user and disable FCM token.
     *
     * @param User $user
     * @param string|null $fcmToken
     * @return void
     */
    public function logout(User $user, ?string $fcmToken = null): void
    {
        // 1. Invalidate current personal access token
        $user->currentAccessToken()->delete();

        // 2. Mark target FCM token as INACTIVE / delete
        if ($fcmToken) {
            FcmToken::where('user_id', $user->id)
                ->where('token', $fcmToken)
                ->delete();
        }
    }

    /**
     * Update user profile settings.
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws Exception
     */
    public function updateProfile(User $user, array $data): User
    {
        // Handle Profile Image upload
        if (isset($data['profile_image'])) {
            // Delete old profile image if exists
            if ($user->profile_image) {
                $this->imageService->delete($user->profile_image);
            }

            // Upload new image
            $url = $this->imageService->upload($data['profile_image'], 'profile-images');
            $user->profile_image = $url;
        }

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['phone'])) {
            $user->phone = $data['phone'];
        }

        $user->save();
        return $user;
    }
}
