<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Models\Address;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /addresses
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::where('customer_id', $request->user()->id)->get();

        return $this->successResponse(
            $addresses,
            'Addresses fetched successfully.'
        );
    }

    /**
     * POST /addresses
     */
    public function store(AddressRequest $request): JsonResponse
    {
        $customerId = $request->user()->id;
        $validated = $request->validated();

        $address = DB::transaction(function () use ($customerId, $validated) {
            // Handle default address flag logic
            $isDefault = $validated['is_default'] ?? false;

            if ($isDefault) {
                Address::where('customer_id', $customerId)->update(['is_default' => false]);
            } else {
                // If this is the only address, make it default
                $count = Address::where('customer_id', $customerId)->count();
                if ($count === 0) {
                    $isDefault = true;
                }
            }

            return Address::create(array_merge($validated, [
                'customer_id' => $customerId,
                'is_default' => $isDefault,
            ]));
        });

        return $this->successResponse(
            $address,
            'Address created successfully.',
            201
        );
    }

    /**
     * PUT /addresses/{id}
     */
    public function update(AddressRequest $request, int $id): JsonResponse
    {
        $customerId = $request->user()->id;
        $address = Address::where('customer_id', $customerId)->findOrFail($id);
        $validated = $request->validated();

        $updated = DB::transaction(function () use ($customerId, $address, $validated) {
            $isDefault = $validated['is_default'] ?? false;

            if ($isDefault) {
                Address::where('customer_id', $customerId)->update(['is_default' => false]);
            }

            $address->update(array_merge($validated, [
                'is_default' => $isDefault,
            ]));

            return $address;
        });

        return $this->successResponse(
            $updated,
            'Address updated successfully.'
        );
    }

    /**
     * DELETE /addresses/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $address = Address::where('customer_id', $request->user()->id)->findOrFail($id);
        $address->delete();

        // If we deleted the default address, promote another address to default
        if ($address->is_default) {
            $nextAddress = Address::where('customer_id', $request->user()->id)->first();
            if ($nextAddress) {
                $nextAddress->is_default = true;
                $nextAddress->save();
            }
        }

        return $this->successResponse(null, 'Address deleted successfully.');
    }
}
