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
     * Get all staff members.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllStaff()
    {
        if (Gate::denies('admin-action')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $staff = Staff::with('user', 'vendor')->get();
        return response()->json(['data' => $staff]);
    }

    /**
     * Get a single staff member.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getStaff($id)
    {
        if (Gate::denies('admin-action')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $staff = Staff::with('user', 'vendor')->find($id);
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }
        return response()->json(['data' => $staff]);
    }

    /**
     * Create a new staff member.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createStaff(Request $request)
    {
        if (Gate::denies('admin-action')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllVendors()
    {
        if (Gate::denies('admin-action')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $vendors = Vendor::with('admin')->get();
        return response()->json(['data' => $vendors]);
    }

    /**
     * Get a single vendor.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getVendor($id)
    {
        if (Gate::denies('admin-action')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $vendor = Vendor::with('admin')->find($id);
        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }
        return response()->json(['data' => $vendor]);
    }

    /**
     * Create a new vendor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createVendor(Request $request)
    {
        if (Gate::denies('admin-action')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'first_name' => $validatedData['name'],
            'last_name' => 'Admin',
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'vendor',
        ]);

        $vendor = Vendor::create([
            'name' => $validatedData['name'],
            'address' => $validatedData['address'],
            'admin_id' => $user->id,
        ]);

        return response()->json(['data' => $vendor, 'message' => 'Vendor created successfully.'], 201);
    }
}