<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AddressController extends Controller
{
    /**
     * Create a new address.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            // Validate incoming request
            $validator = Validator::make($request->all(), [
                'addressable_id' => 'required|integer',
                'addressable_type' => 'required|string|max:255',
                'line_1' => 'required|string|max:255',
                'line_2' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'zipcode' => 'required|string|max:20',
                'country' => 'required|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'is_primary' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Create the address
            $address = Address::create($request->all());

            return response()->json(['message' => 'Address created successfully', 'address' => $address], 201);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'Address creation failed. ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing address.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Validate incoming request
            $validator = Validator::make($request->all(), [
                'addressable_id' => 'required|integer',
                'addressable_type' => 'required|string|max:255',
                'line_1' => 'required|string|max:255',
                'line_2' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'zipcode' => 'required|string|max:20',
                'country' => 'required|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'is_primary' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Find the address
            $address = Address::findOrFail($id);

            // Update the address
            $address->update($request->all());

            return response()->json(['message' => 'Address updated successfully', 'address' => $address], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'Address update failed. ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete an address.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        try {
            // Find the address
            $address = Address::findOrFail($id);

            // Delete the address
            $address->delete();

            return response()->json(['message' => 'Address deleted successfully'], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'Address deletion failed. ' . $e->getMessage()], 500);
        }
    }

}
