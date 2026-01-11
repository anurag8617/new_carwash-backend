<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class StaffAndVendorAdminController extends Controller
{
    /**
     * Update an existing vendor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateVendor(Request $request, $id)
    {
        // 1. Authorization Check
        // Ensure you have a Gate defined for 'admin-action' or remove this if not using Gates yet.
        // if (Gate::denies('admin-action')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $vendor = Vendor::with('admin')->find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        // 2. Validation
        $validatedData = $request->validate([
            // User Fields
            'name' => 'sometimes|string|max:255', // Maps to User first_name
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($vendor->admin_id),
            ],
            'password' => 'nullable|string|min:8|confirmed',

            // Vendor Fields
            'business_name' => 'sometimes|string|max:255', // Maps to Vendor name
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'sometimes|string',
            'phone' => 'sometimes|string|max:20',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'fee_percentage' => 'sometimes|numeric|min:0|max:100',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
        ]);

        // 3. Handle Image Upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($vendor->image && file_exists(public_path($vendor->image))) {
                @unlink(public_path($vendor->image));
            }

            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/vendors'), $filename);
            $validatedData['image'] = 'uploads/vendors/' . $filename;
        }

        // 4. Update Vendor Details
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

        // 5. Update Associated User (Admin) Details (Name, Email, Password)
        $user = $vendor->admin;
        if ($user) {
            $userData = [];
            if ($request->has('name')) $userData['first_name'] = $request->name;
            if ($request->has('email')) $userData['email'] = $request->email;
            
            // Password Update Logic
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            if (!empty($userData)) {
                $user->update($userData);
            }
        }

        return response()->json(['data' => $vendor->load('admin'), 'message' => 'Vendor updated successfully.']);
    }

    /**
     * Create a new vendor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createVendor(Request $request)
    {
        // if (Gate::denies('admin-action')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        // 1. Validate All Inputs
        $validatedData = $request->validate([
            // User / Admin Data
            'name' => 'required|string|max:255', // User's Name
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            
            // Vendor Business Data
            'business_name' => 'required|string|max:255', // Vendor Name
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'website' => 'nullable|url',
            'description' => 'nullable|string',
            'fee_percentage' => 'required|numeric',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
        ]);

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
            'phone' => $validatedData['phone'], // Storing phone in User as well if needed
            'password' => Hash::make($validatedData['password']),
            'role' => 'vendor',
        ]);

        // 4. Create Vendor Profile
        $vendor = Vendor::create([
            'admin_id' => $user->id,
            'name' => $validatedData['business_name'], // Mapping business_name to name
            'image' => $imagePath,
            'address' => $validatedData['address'],
            'phone' => $validatedData['phone'],
            'website' => $validatedData['website'] ?? null,
            'description' => $validatedData['description'] ?? '',
            'fee_percentage' => $validatedData['fee_percentage'],
            'location_lat' => $validatedData['location_lat'] ?? 0.0,
            'location_lng' => $validatedData['location_lng'] ?? 0.0,
        ]);

        return response()->json(['data' => $vendor, 'message' => 'Vendor created successfully.'], 201);
    }

    /**
     * Get all staff members.
     */
    public function getAllStaff()
    {
        $staff = Staff::with('user', 'vendor')->get();
        return response()->json(['data' => $staff]);
    }

    /**
     * Get a single staff member.
     */
    public function getStaff($id)
    {
        $staff = Staff::with('user', 'vendor')->find($id);
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }
        return response()->json(['data' => $staff]);
    }

    /**
     * Create a new staff member.
     */
    public function createStaff(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'vendor_id' => 'required|exists:vendors,id',
        ]);

        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'staff',
        ]);

        $staff = Staff::create([
            'user_id' => $user->id,
            'vendor_id' => $validatedData['vendor_id'],
        ]);

        return response()->json(['data' => $staff, 'message' => 'Staff created successfully.'], 201);
    }

    /**
     * Get all vendors.
     */
    public function getAllVendors()
    {
        $vendors = Vendor::with('admin')->get();
        return response()->json(['data' => $vendors]);
    }

    /**
     * Get a single vendor.
     */
    public function getVendor($id)
    {
        $vendor = Vendor::with('admin')->find($id);
        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }
        return response()->json(['data' => $vendor]);
    }
    
    /**
     * Delete a staff member.
     */
    public function deleteStaff($id)
    {
        $staff = Staff::find($id);
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $user = User::find($staff->user_id);

        if ($staff->profile_image && file_exists(public_path($staff->profile_image))) {
            @unlink(public_path($staff->profile_image));
        }

        // Delete Staff first
        $staff->delete();

        // Delete User second
        if ($user) {
            $user->delete();
        }

        return response()->json(['success' => true, 'message' => 'Staff deleted successfully']);
    }
    
}
