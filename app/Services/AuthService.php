<?php

namespace App\Services;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\RestaurantUser;
use App\Models\UserDevice;
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
                    'bank_account_holder' => $data['bank_account_holder'] ?? null,
                    'bank_account_number' => $data['bank_account_number'] ?? null,
                    'bank_ifsc' => $data['bank_ifsc'] ?? null,
                    'bank_branch' => $data['bank_branch'] ?? null,
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
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new Exception('Invalid credentials provided.', 401);
        }

        if ($user->status === 'BLOCKED') {
            throw new Exception('Your account is blocked by administrative decision.', 403);
        }

        // Generate Sanctum access token
        $tokenName = 'AuthToken_' . $user->id;
        $token = $user->createToken($tokenName)->plainTextToken;

        // Clean up any existing records with the same FCM token to avoid unique constraint violations
        if (!empty($data['fcm_token'])) {
            UserDevice::where('fcm_token', $data['fcm_token'])->delete();
        }

        // Save device details to UserDevice table
        $deviceId = $data['device_id'] ?? ('device_' . $user->id);
        UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $deviceId,
            ],
            [
                'fcm_token' => $data['fcm_token'] ?? $user->fcm_token ?? 'default_fcm_token',
                'device_type' => $data['device_type'] ?? $user->device_type ?? 'android',
                'is_active' => true,
                'last_login_at' => now(),
            ]
        );

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
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        if ($fcmToken) {
            UserDevice::where('user_id', $user->id)
                ->where('fcm_token', $fcmToken)
                ->update(['is_active' => false]);
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
        $imgFile = $data['profile_image'] ?? $data['avatar'] ?? null;
        if ($imgFile) {
            if ($user->profile_image) {
                $this->imageService->delete($user->profile_image);
            }

            $url = $this->imageService->upload($imgFile, 'profile-images');
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
