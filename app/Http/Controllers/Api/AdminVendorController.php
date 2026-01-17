<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminVendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::with('admin')->get();
        return response()->json(['success' => true, 'data' => $vendors]);
    }

    public function show($id)
    {
        $vendor = Vendor::with('admin')->find($id);
        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $vendor]);
    }

    public function showUser($id)
    {
        // Only load the client relation, avoid deeply nested user relations
        $user = User::with('client')->find($id);

        if (!$user) {
             return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        return response()->json([
            'success' => true, 
            'data' => $user
        ]);
    }
    
    // 3. Create a NEW Vendor
    public function store(Request $request)     
    {
        $request->validate([
            'name' => 'required|string|max:255', 
            'business_name' => 'required|string|max:255', 
            'address' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
            'fee_percentage' => 'nullable|numeric',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'operating_days' => 'nullable|array', 
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        DB::beginTransaction();
        try {
            // A. Create User (Owner)
            $user = User::create([
                'first_name' => $request->name, 
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'vendor',
            ]);

            // Ensure Upload Directory Exists
            $uploadPath = public_path('uploads/vendors');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Handle Profile Image (DP) Upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_dp_' . $file->getClientOriginalName();
                $file->move($uploadPath, $filename);
                $imagePath = 'uploads/vendors/' . $filename;
            }

            // Handle Banner Upload
            $bannerPath = null;
            if ($request->hasFile('banner')) {
                $file = $request->file('banner');
                $filename = time() . '_banner_' . $file->getClientOriginalName();
                $file->move($uploadPath, $filename);
                $bannerPath = 'uploads/vendors/' . $filename;
            }

            // B. Create Vendor Profile
            $vendor = Vendor::create([
                'admin_id' => $user->id,
                'name' => $request->business_name,
                'address' => $request->address,
                'phone' => $request->phone,
                'website' => $request->website,
                'description' => $request->description,
                'image' => $imagePath,
                'banner' => $bannerPath, // ✅ Fixed Banner Saving
                'location_lat' => $request->location_lat ?? 0.0,
                'location_lng' => $request->location_lng ?? 0.0,
                'fee_percentage' => $request->fee_percentage ?? 15.00,
                'opening_time' => $request->opening_time,
                'closing_time' => $request->closing_time,
                'operating_days' => $request->operating_days,
            ]);

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Vendor created successfully.', 
                'data' => $vendor
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Vendor Creation Failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create vendor: ' . $e->getMessage()], 500);
        }
    }

    // 4. Update Vendor
    public function update(Request $request, $id)
    {
        // 1. Check if Vendor Exists
        $vendor = Vendor::find($id);
        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
        }

        // 2. Validate
        $validator = \Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'operating_days' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // Ensure Upload Directory Exists
            $uploadPath = public_path('uploads/vendors');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // 3. Handle Profile Image
            if ($request->hasFile('image')) {
                if ($vendor->image && file_exists(public_path($vendor->image))) {
                    @unlink(public_path($vendor->image));
                }
                $file = $request->file('image');
                $filename = time() . '_dp_' . $file->getClientOriginalName();
                $file->move($uploadPath, $filename);
                $vendor->image = 'uploads/vendors/' . $filename;
            }

            // 4. Handle Banner Image
            if ($request->hasFile('banner')) {
                // Delete old banner
                if ($vendor->banner && file_exists(public_path($vendor->banner))) {
                    @unlink(public_path($vendor->banner));
                }
                $file = $request->file('banner');
                $filename = time() . '_banner_' . $file->getClientOriginalName();
                $file->move($uploadPath, $filename);
                $vendor->banner = 'uploads/vendors/' . $filename;
            }

            // 5. Update Fields
            $vendor->name = $request->business_name;
            $vendor->address = $request->address;
            $vendor->phone = $request->phone;
            $vendor->website = $request->website;
            $vendor->description = $request->description;
            $vendor->location_lat = $request->location_lat;
            $vendor->location_lng = $request->location_lng;
            $vendor->fee_percentage = $request->fee_percentage;
            $vendor->opening_time = $request->opening_time;
            $vendor->closing_time = $request->closing_time;
            
            // ✅ Only update operating_days if present in request (handles cases where frontend might send null)
            if ($request->has('operating_days')) {
                $vendor->operating_days = $request->operating_days;
            }
            
            $vendor->save();

            // 6. Update User (Admin) Info
            if($request->name) {
                $user = User::find($vendor->admin_id);
                if($user) {
                    $user->first_name = $request->name;
                    // Only update password if provided and not empty
                    if($request->filled('password')) {
                        $user->password = Hash::make($request->password);
                    }
                    $user->save();
                }
            }

            return response()->json([
                'success' => true, 
                'message' => 'Vendor updated successfully', 
                'data' => $vendor
            ]);

        } catch (\Throwable $e) {
            \Log::error("Vendor Update Failed: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $vendor = Vendor::find($id);
        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
        }

        $user = User::find($vendor->admin_id);
        if ($user) {
            $user->delete();
        }
        
        $vendor->delete();

        return response()->json(['success' => true, 'message' => 'Vendor deleted successfully']);
    }
}