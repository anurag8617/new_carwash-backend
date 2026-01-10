<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class StaffAndVendorAdminController extends Controller
{
    // Vendor Management Methods
    public function createVendor(Request $request)
    {
        // 1. Validate All Inputs
        $validator = Validator::make($request->all(), [
            // User / Admin Data
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            // ✅ FIX: Added 'unique:users,phone' to prevent duplicate errors
            'phone' => 'required|string|max:20|unique:users,phone', 
            
            // Vendor Business Data
            'business_name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'required|string',
            'website' => 'nullable|url',
            'description' => 'nullable|string',
            'fee_percentage' => 'required|numeric',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
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
            $file->move(public_path('uploads/vendors'), $filename);
            $imagePath = 'uploads/vendors/' . $filename;
        }

        // 3. Create User (Vendor Admin)
        $user = User::create([
            'first_name' => $validatedData['name'],
            'last_name' => 'Admin', // Defaulting last name
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'], 
            'password' => Hash::make($validatedData['password']),
            'role' => 'vendor',
        ]);

        // 4. Create Vendor Profile
        $vendor = Vendor::create([
            'admin_id' => $user->id,
            'name' => $validatedData['business_name'],
            'image' => $imagePath,
            'address' => $validatedData['address'],
            'phone' => $validatedData['phone'], // Storing phone in Vendor as well
            'website' => $validatedData['website'] ?? null,
            'description' => $validatedData['description'] ?? '',
            'fee_percentage' => $validatedData['fee_percentage'],
            'location_lat' => $validatedData['location_lat'] ?? 0.0,
            'location_lng' => $validatedData['location_lng'] ?? 0.0,
        ]);

        return response()->json(['data' => $vendor, 'message' => 'Vendor created successfully.'], 201);
    }

    public function updateVendor(Request $request, $id)
    {
        $vendor = Vendor::with('admin')->find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        // 1. Validate
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($vendor->admin_id),
            ],
            // ✅ FIX: Ensure unique phone, but ignore the current user's phone
            'phone' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($vendor->admin_id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'business_name' => 'sometimes|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'sometimes|string',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'fee_percentage' => 'sometimes|numeric|min:0|max:100',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // 2. Handle Image Upload
        if ($request->hasFile('image')) {
            if ($vendor->image && file_exists(public_path($vendor->image))) {
                @unlink(public_path($vendor->image));
            }

            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/vendors'), $filename);
            $validatedData['image'] = 'uploads/vendors/' . $filename;
        }

        // 3. Update Vendor Details
        $vendorData = [];
        if ($request->has('business_name')) $vendorData['name'] = $request->business_name;
        if ($request->has('address')) $vendorData['address'] = $request->address;
        if ($request->has('phone')) $vendorData['phone'] = $request->phone;
        if ($request->has('website')) $vendorData['website'] = $request->website;
        if ($request->has('description')) $vendorData['description'] = $request->description;
        if ($request->has('fee_percentage')) $vendorData['fee_percentage'] = $request->fee_percentage;
        if ($request->has('location_lat')) $vendorData['location_lat'] = $request->location_lat;
        if ($request->has('location_lng')) $vendorData['location_lng'] = $request->location_lng;
        if (isset($validatedData['image'])) $vendorData['image'] = $validatedData['image'];

        $vendor->update($vendorData);

        // 4. Update Associated User (Admin) Details
        $user = $vendor->admin;
        if ($user) {
            $userData = [];
            if ($request->has('name')) $userData['first_name'] = $request->name;
            if ($request->has('email')) $userData['email'] = $request->email;
            if ($request->has('phone')) $userData['phone'] = $request->phone; // Update User phone too
            
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            if (!empty($userData)) {
                $user->update($userData);
            }
        }

        return response()->json(['data' => $vendor->load('admin'), 'message' => 'Vendor updated successfully.']);
    }

    public function deleteVendor($id)
    {
        $vendor = Vendor::find($id);
        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }
        
        if ($vendor->admin) {
            $vendor->admin->delete();
        }
        
        $vendor->delete();
        
        return response()->json(['message' => 'Vendor deleted successfully']);
    }

     public function getAllVendors()
    {
        $vendors = Vendor::with('admin')->get();
        return response()->json(['data' => $vendors]);
    }

    public function getVendor($id)
    {
        $vendor = Vendor::with('admin')->find($id);
        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }
        return response()->json(['data' => $vendor]);
    }

    // Staff Management Methods
    public function getAllStaff()
    {
        $staff = Staff::with('user', 'vendor')->get();
        return response()->json(['data' => $staff]);
    }

    public function getStaff($id)
    {
        $staff = Staff::with('user', 'vendor')->find($id);
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }
        return response()->json(['data' => $staff]);
    }

    public function createStaff(Request $request)
    {
        // 1. Validate - Including new fields from your migration
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'vendor_id' => 'required|exists:vendors,id',
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

        // 3. Create User Login
        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'staff',
        ]);

        // 4. Create Staff Profile
        $staff = Staff::create([
            'user_id' => $user->id,
            'vendor_id' => $validatedData['vendor_id'],
            'phone' => $validatedData['phone'], // Storing in staff table too as per schema
            'address' => $validatedData['address'] ?? null,
            'joining_date' => $validatedData['joining_date'] ?? now(),
            'salary' => $validatedData['salary'] ?? 0,
            'profile_image' => $imagePath, // Matches your migration column
            'status' => 'active',
        ]);

        return response()->json(['data' => $staff, 'message' => 'Staff created successfully.'], 201);
    }
   public function updateStaff(Request $request, $id)
    {
        // 1. Find the Staff and their associated User
        $staff = Staff::with('user')->find($id);

        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        // 2. Validate with IGNORE rules
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                // ✅ CRITICAL FIX: Ignore the CURRENT User's ID
                Rule::unique('users', 'email')->ignore($staff->user_id),
            ],
            'phone' => [
                'sometimes',
                'string',
                'max:20',
                // ✅ CRITICAL FIX: Ignore the CURRENT User's ID
                Rule::unique('users', 'phone')->ignore($staff->user_id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'vendor_id' => 'sometimes|exists:vendors,id',
            'address' => 'nullable|string|max:500',
            'joining_date' => 'nullable|date',
            'salary' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'sometimes|in:active,inactive,on_leave',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // 3. Handle Image Upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($staff->profile_image && file_exists(public_path($staff->profile_image))) {
                @unlink(public_path($staff->profile_image));
            }

            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/staff'), $filename);
            $validatedData['profile_image'] = 'uploads/staff/' . $filename;
        }

        // 4. Update User Details (Name, Email, Phone, Password)
        $user = $staff->user;
        $userData = [];
        if ($request->has('first_name')) $userData['first_name'] = $request->first_name;
        if ($request->has('last_name')) $userData['last_name'] = $request->last_name;
        if ($request->has('email')) $userData['email'] = $request->email;
        if ($request->has('phone')) $userData['phone'] = $request->phone;
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }
        $user->update($userData);

        // 5. Update Staff Details
        $staffData = [];
        if ($request->has('vendor_id')) $staffData['vendor_id'] = $request->vendor_id;
        if ($request->has('address')) $staffData['address'] = $request->address;
        if ($request->has('joining_date')) $staffData['joining_date'] = $request->joining_date;
        if ($request->has('salary')) $staffData['salary'] = $request->salary;
        if ($request->has('status')) $staffData['status'] = $request->status;
        if ($request->has('phone')) $staffData['phone'] = $request->phone; // Sync phone
        if (isset($validatedData['profile_image'])) $staffData['profile_image'] = $validatedData['profile_image'];

        $staff->update($staffData);

        return response()->json(['data' => $staff->load('user'), 'message' => 'Staff updated successfully.']);
    }   
}