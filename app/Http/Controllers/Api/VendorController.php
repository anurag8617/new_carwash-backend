<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\User; // Don't forget to import User
use App\Models\Staff; // Don't forget to import Staff
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{

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


    public function storeStaff(Request $request)
    {
        // 0. Verify the logged-in user has a Vendor profile
        $user = Auth::user();
        $vendor = Vendor::where('admin_id', $user->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found for this user.'], 404);
        }

        // 1. Validate
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'phone' => 'required|string|max:20|unique:users,phone',
            'address' => 'nullable|string|max:500',
            'joining_date' => 'nullable|date',
            'salary' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
             return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // 2. Handle Image Upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/staff'), $filename);
            $imagePath = 'uploads/staff/' . $filename;
        }

        // 3. Create User (Staff Login)
        $newUser = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'staff',
        ]);

        // 4. Create Staff Profile linked to the *Found Vendor*
        $staff = Staff::create([
            'user_id' => $newUser->id,
            'vendor_id' => $vendor->id, // Use the ID from the vendor we found earlier
            'phone' => $validatedData['phone'],
            'address' => $validatedData['address'] ?? null,
            'joining_date' => $validatedData['joining_date'] ?? now(),
            'salary' => $validatedData['salary'] ?? 0,
            'profile_image' => $imagePath,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Staff created successfully.',
            'data' => $staff
        ], 201);
    }

    public function updateStaff(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        
        // Ensure the vendor owns this staff member
        $user = Auth::user();
        $vendor = Vendor::where('admin_id', $user->id)->first();
        
        if (!$vendor || $staff->vendor_id !== $vendor->id) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate updates
        $validated = $request->validate([
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'in:active,inactive,on_leave',
            'salary' => 'nullable|numeric',
        ]);

        // Update the Staff record
        $staff->update($validated);

        // Optional: Update the User phone if changed
        if (isset($validated['phone'])) {
            $staff->user->update(['phone' => $validated['phone']]);
        }

        return response()->json(['message' => 'Staff details updated!', 'data' => $staff]);
    }

    public function search(Request $request)
    {
        // ... (keep existing search logic) ...
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric|min:1', // Default 5km
            'rating' => 'nullable|numeric|min:0|max:5',
            'new_only' => 'nullable|boolean', // For "New" badge filter
            'available_only' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius ?? 5; // Default to 5km

        // 2. Build Query with Haversine Formula
        $vendors = Vendor::select('*')
            ->selectRaw(
                '( 6371 * acos( cos( radians(?) ) * cos( radians( location_lat ) ) * cos( radians( location_lng ) - radians(?) ) + sin( radians(?) ) * sin( radians( location_lat ) ) ) ) AS distance',
                [$lat, $lng, $lat]
            );

        // 3. Apply Filters
        $vendors->having('distance', '<=', $radius);

        if ($request->has('rating') && $request->rating > 0) {
            $vendors->where('average_rating', '>=', $request->rating);
        }

        if ($request->has('available_only') && $request->available_only == 'true') {
            $vendors->where('is_active', true);
        }

        if ($request->has('new_only') && $request->new_only == 'true') {
            $vendors->where('created_at', '>=', now()->subDays(30));
        }

        // 4. Sort and Execute
        $vendors->orderBy('distance', 'asc');

        $data = $vendors->with('services')->get();

        return response()->json([
            'success' => true,
            'message' => 'Nearby vendors retrieved.',
            'count' => $data->count(),
            'data' => $data
        ]);
    }

    public function showPublic($id)
    {
        // ✅ UPDATED: Added 'ratings.client' to eager loading
        $vendor = Vendor::with([
            'services' => function($query) {
                $query->where('is_active', true);
            },
            'plans' => function($query) {
                $query->where('is_active', true); 
            },
            // 'plans' => function($query) {
            //     $query->where('status', 'active');
            // },
            'ratings.client' => function($query) {
                $query->select('id', 'first_name', 'last_name', 'profile_image'); // Fetch only necessary user fields
            }
        ])->find($id);

        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $vendor
        ]);
    }

    public function show($id)
    {
        // ❌ OLD: Might look like this
        // $vendor = Vendor::with('services')->find($id);

        // ✅ NEW: Add 'plans' to the with() function
        $vendor = Vendor::with(['services', 'plans' => function($query) {
            $query->where('is_active', true); // Only show active plans
        }])->find($id);

        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $vendor]);
    }
    
    public function destroy($id)
    {
        $vendor = Vendor::find($id);
        if (!$vendor) return response()->json(['message' => 'Not found'], 404);

        $user = User::find($vendor->admin_id);

        $vendor->delete(); // Delete Vendor FIRST
        if ($user) $user->delete(); // Delete User SECOND

        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }
    
}