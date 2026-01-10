<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Vendor;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services for the authenticated vendor.
     */
   public function index(Request $request)
    {
        $user = $request->user();
        $vendor = Vendor::where('admin_id', $user->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'No vendor profile found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $vendor->services
        ]);
    }

    /**
     * Store a new service for the authenticated vendor.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $vendor = Vendor::where('admin_id', $user->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile required.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validatedData['vendor_id'] = $vendor->id;
        $validatedData['is_active'] = $request->input('is_active', true); 

        $service = Service::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully.',
            'data' => $service
        ], 201);
    }

    /**
     * Display the specified service.
     */
    public function show(Request $request, Service $service)
    {
        $vendor = Vendor::where('admin_id', $request->user()->id)->first();

        // Authorization: Ensure the requested service belongs to the user's vendor profile
        if (!$vendor || $service->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Not authorized to view this service.'], 403);
        }

        return response()->json(['success' => true, 'data' => $service]);

    }

    /**
     * Update the specified service.
     */
   public function update(Request $request, Service $service)
    {
        $vendor = Vendor::where('admin_id', $request->user()->id)->first();

        if (!$vendor || $service->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'duration' => 'sometimes|required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $service->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully.',
            'data' => $service
        ]);
    }
    /**
     * Remove the specified service.
     */
    public function destroy(Request $request, Service $service)
    {
        $vendor = Vendor::where('admin_id', $request->user()->id)->first();

        // Authorization check
        if (!$vendor || $service->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Not authorized to delete this service.'], 403);
        }
        
        $service->delete();

        return response()->json(['success' => true, 'message' => 'Service deleted successfully.']);
    }

    /**
     * ✅ NEW: Get all services for clients (Public/Client Route)
     */
    public function getPublicServices(Request $request)
    {
        $services = Service::with('vendor')
            ->where('is_active', true) 
            ->get();

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }
}