<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * Display a listing of the vendor's staff.
     */
    public function index(Request $request)
    {
        $vendorUser = $request->user();
        $vendor = Vendor::where('admin_id', $vendorUser->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found.'], 404);
        }

        // Get staff and include the user details for each staff member
        $staff = Staff::where('vendor_id', $vendor->id)->with('user')->get();

        return response()->json(['success' => true, 'data' => $staff]);
    }

    /**
     * Store a newly created staff member in storage.
     */
    public function store(Request $request)
    {
        $vendorUser = $request->user();
        $vendor = Vendor::where('admin_id', $vendorUser->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found.'], 404);
        }

        $validatedData = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $staffUser = User::where('email', $validatedData['email'])->first();

        // Check if the user has the 'staff' role
        if ($staffUser->role !== 'staff') {
            return response()->json(['message' => 'This user is not a staff member.'], 422);
        }

        // Check if the user is already staff for this vendor
        $existingStaff = Staff::where('user_id', $staffUser->id)
                                ->where('vendor_id', $vendor->id)
                                ->exists();

        if ($existingStaff) {
            return response()->json(['message' => 'This user is already a staff member.'], 409); // 409 Conflict
        }

        $staff = Staff::create([
            'user_id' => $staffUser->id,
            'vendor_id' => $vendor->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Staff member added successfully.',
            'data' => $staff
        ], 201);
    }

    /**
     * Remove the specified staff member from storage.
     */
    public function destroy(Request $request, Staff $staff)
    {
        $vendorUser = $request->user();
        $vendor = Vendor::where('admin_id', $vendorUser->id)->first();

        // Authorization: Check if the staff member belongs to the logged-in vendor
        if (!$vendor || $staff->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Not authorized to remove this staff member.'], 403);
        }

        $staff->delete();

        return response()->json(['success' => true, 'message' => 'Staff member removed successfully.']);
    }
}