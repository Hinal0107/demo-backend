<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\DailyMeal;
use App\Services\ImageUploadService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Exception;

class RestaurantDailyMealController extends Controller
{
    use ApiResponseTrait;

    protected ImageUploadService $imageService;

    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * GET /restaurant/daily-meals
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $meals = DailyMeal::where('restaurant_id', $restaurantId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return $this->successResponse($meals, 'Daily meals fetched successfully.');
    }

    /**
     * POST /restaurant/daily-meals
     */
    public function store(Request $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        $validated = $request->validate([
            'date' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'veg_type' => 'required|string|in:VEG,NON_VEG,JAIN,veg,non_veg,jain',
            'meal_type' => 'required|string',
            'addons' => 'nullable',
            'addon_ids' => 'nullable',
            'availability' => 'nullable',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE',
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        try {
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $imageUrl = $this->imageService->upload($request->file('image'), 'daily-meals');
            }

            // Parse date dd/mm/yyyy or yyyy-mm-dd
            $dateStr = $validated['date'];
            try {
                if (str_contains($dateStr, '/')) {
                    $parts = explode('/', $dateStr);
                    if (count($parts) === 3) {
                        $dateObj = Carbon::createFromDate((int)$parts[2], (int)$parts[1], (int)$parts[0]);
                    } else {
                        $dateObj = Carbon::parse($dateStr);
                    }
                } else {
                    $dateObj = Carbon::parse($dateStr);
                }
            } catch (Exception $e) {
                $dateObj = Carbon::today();
            }

            // Parse addons
            $addonArray = [];
            if (!empty($validated['addon_ids'])) {
                if (is_array($validated['addon_ids'])) {
                    $addonArray = array_map('intval', $validated['addon_ids']);
                } else {
                    $addonArray = array_map('intval', explode(',', (string)$validated['addon_ids']));
                }
            } elseif (!empty($validated['addons'])) {
                if (is_array($validated['addons'])) {
                    $addonArray = array_map('intval', $validated['addons']);
                } else {
                    $addonArray = array_map('intval', explode(',', (string)$validated['addons']));
                }
            }

            // Standardize meal_type to uppercase (TODAY, TOMORROW, WEEKLY)
            $mealType = strtoupper($validated['meal_type']);
            if (str_contains($mealType, 'TODAY')) {
                $mealType = 'TODAY';
            } elseif (str_contains($mealType, 'TOMORROW')) {
                $mealType = 'TOMORROW';
            } elseif (str_contains($mealType, 'WEEKLY')) {
                $mealType = 'WEEKLY';
            }

            $availability = true;
            if (isset($validated['availability'])) {
                $avail = $validated['availability'];
                $availability = ($avail === '1' || $avail === 1 || $avail === 'true' || $avail === true);
            }

            $meal = DailyMeal::create([
                'restaurant_id' => $restaurantId,
                'date' => $dateObj->toDateString(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'image' => $imageUrl,
                'price' => $validated['price'],
                'discount_price' => $validated['discount_price'] ?? null,
                'veg_type' => strtoupper($validated['veg_type']),
                'meal_type' => $mealType,
                'addons' => $addonArray,
                'availability' => $availability,
                'status' => strtoupper($validated['status'] ?? 'ACTIVE'),
            ]);

            return $this->successResponse($meal, 'Daily meal scheduled successfully.', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * PUT/POST /restaurant/daily-meals/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $meal = DailyMeal::where('restaurant_id', $restaurantId)->findOrFail($id);

        $validated = $request->validate([
            'date' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'veg_type' => 'nullable|string|in:VEG,NON_VEG,JAIN,veg,non_veg,jain',
            'meal_type' => 'nullable|string',
            'addons' => 'nullable',
            'addon_ids' => 'nullable',
            'availability' => 'nullable',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE',
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        try {
            if ($request->hasFile('image')) {
                if ($meal->image) {
                    $this->imageService->delete($meal->image);
                }
                $meal->image = $this->imageService->upload($request->file('image'), 'daily-meals');
            }

            if (isset($validated['name'])) $meal->name = $validated['name'];
            if (isset($validated['description'])) $meal->description = $validated['description'];
            if (isset($validated['price'])) $meal->price = $validated['price'];
            if (array_key_exists('discount_price', $validated)) $meal->discount_price = $validated['discount_price'];
            if (isset($validated['veg_type'])) $meal->veg_type = strtoupper($validated['veg_type']);
            if (isset($validated['status'])) $meal->status = strtoupper($validated['status']);

            if (isset($validated['meal_type'])) {
                $mType = strtoupper($validated['meal_type']);
                if (str_contains($mType, 'TODAY')) $mType = 'TODAY';
                elseif (str_contains($mType, 'TOMORROW')) $mType = 'TOMORROW';
                elseif (str_contains($mType, 'WEEKLY')) $mType = 'WEEKLY';
                $meal->meal_type = $mType;
            }

            if (isset($validated['availability'])) {
                $avail = $validated['availability'];
                $meal->availability = ($avail === '1' || $avail === 1 || $avail === 'true' || $avail === true);
            }

            if (isset($validated['date'])) {
                $dateStr = $validated['date'];
                if (str_contains($dateStr, '/')) {
                    $parts = explode('/', $dateStr);
                    if (count($parts) === 3) {
                        $meal->date = Carbon::createFromDate((int)$parts[2], (int)$parts[1], (int)$parts[0])->toDateString();
                    }
                } else {
                    $meal->date = Carbon::parse($dateStr)->toDateString();
                }
            }

            $meal->save();

            return $this->successResponse($meal, 'Daily meal updated successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /restaurant/daily-meals/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $meal = DailyMeal::where('restaurant_id', $restaurantId)->findOrFail($id);

        try {
            if ($meal->image) {
                $this->imageService->delete($meal->image);
            }
            $meal->delete();
            return $this->successResponse(null, 'Daily meal deleted successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
