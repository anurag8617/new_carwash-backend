<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor; // <-- Import the Vendor model
use Illuminate\Http\Request; // <-- Import the Request class

class VendorController extends Controller
{
    /**
     * Display a listing of the vendors.
     * If the user is an admin, show all vendors.
     * If the user is a vendor, this endpoint is not used (they manage their own profile).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to view vendor list.'
            ], 403);
        }

        $vendors = Vendor::with('admin')->get();

        return response()->json([
            'success' => true,
            'message' => 'Vendors retrieved successfully',
            'data' => $vendors
        ]);
    }


    /**
     * Store a newly created vendor in the database.
     */
   public function store(Request $request)
    {
        // Get the currently authenticated user
        $user = $request->user();

        // Optional: Check if the user's role is 'vendor'
        if ($user->role !== 'vendor') {
            return response()->json(['message' => 'Only users with the vendor role can create a vendor profile.'], 403); // 403 Forbidden
        }

        // Validate the incoming data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'location_lat' => 'required|numeric',
            'location_lng' => 'required|numeric',
            'fee_percentage' => 'required|numeric'
        ]);

        // Use the authenticated user's ID for the admin_id
        $validatedData['admin_id'] = $user->id;

        // Create the vendor
        $vendor = Vendor::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Vendor created successfully',
            'data' => $vendor
        ], 201);
    }

    /**
     * Display the specified vendor.
     */
    public function show(Vendor $vendor)
    {
        // Laravel's Route Model Binding automatically finds the vendor by its ID
        return response()->json([
            'success' => true,
            'data' => $vendor
        ]);
    }

    /**
     * Update the specified vendor in the database.
     */
    public function update(Request $request, Vendor $vendor)
    {
        // Authorization: Check if the logged-in user owns this vendor profile
        if ($request->user()->id !== $vendor->admin_id) {
            return response()->json(['message' => 'You are not authorized to update this vendor.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|required|string',
            'location_lat' => 'sometimes|required|numeric',
            'location_lng' => 'sometimes|required|numeric',
            'fee_percentage' => 'sometimes|required|numeric'
        ]);

        $vendor->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Vendor updated successfully',
            'data' => $vendor
        ]);
    }

    /**
     * Remove the specified vendor from the database.
     * Can be done by the vendor owner or an admin.
     */
    public function destroy(Request $request, Vendor $vendor)
    {
        $user = $request->user();

        // Authorization Check: Allow if the user is an admin OR they own the vendor profile
        if ($user->role !== 'admin' && $user->id !== $vendor->admin_id) {
            return response()->json(['message' => 'You are not authorized to delete this vendor.'], 403);
        }

        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor deleted successfully'
        ]);
    }
}