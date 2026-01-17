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
     * ✅ FIXED: Show Service Details
     * Uses 'select' on vendor relation to PREVENT RECURSION LOOP (500 Error)
     */
   // Http/Controllers/Api/ServiceController.php

public function show(Request $request, $id)
{
    $service = Service::with(['vendor' => function($query) {
        $query->select('id', 'name', 'image', 'address', 'admin_id', 'fee_percentage'); 
    }])->find($id);
    
    if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
    }

    $user = $request->user();

    // 1. If User is Admin -> Allow
    if ($user && $user->role === 'admin') {
        return response()->json(['success' => true, 'data' => $service]);
    }

    // 2. If User is Vendor -> Check Ownership
    if ($user) {
        $vendor = Vendor::where('admin_id', $user->id)->first();
        if ($vendor && $service->vendor_id === $vendor->id) {
            return response()->json(['success' => true, 'data' => $service]);
        }
    }

    // 3. If Public/Guest or Unauth User -> Allow if you want public viewing
    // If you strictly want this protected, return 403 here.
    // For now, we return the service data assuming public viewing is allowed for guests.
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
     * Get all services for clients (Public/Client Route)
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