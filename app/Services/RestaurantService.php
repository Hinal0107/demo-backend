<?php

namespace App\Services;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Log;
use Exception;

class RestaurantService
{
    protected ImageUploadService $imageService;

    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Update restaurant details.
     *
     * @param Restaurant $restaurant
     * @param array $data
     * @return Restaurant
     * @throws Exception
     */
    public function updateProfile(Restaurant $restaurant, array $data): Restaurant
    {
        // Handle logo replacement if provided
        if (isset($data['logo'])) {
            if ($restaurant->logo) {
                $this->imageService->delete($restaurant->logo);
            }
            $logoUrl = $this->imageService->upload($data['logo'], 'restaurants');
            $restaurant->logo = $logoUrl;
        }

        // Fill parameters
        $fillables = [
            'name', 'phone', 'email', 'description', 'address', 'city', 
            'state', 'country', 'pincode', 'latitude', 'longitude', 
            'opening_time', 'closing_time'
        ];

        foreach ($fillables as $field) {
            if (isset($data[$field])) {
                $restaurant->{$field} = $data[$field];
            }
        }

        $restaurant->save();
        return $restaurant;
    }
}
