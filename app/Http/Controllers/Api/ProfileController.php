<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;
use App\Models\Staff; // ✅ Import Staff Model

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        
        if ($user->role === 'client') {
            $user->load('client');
        } elseif ($user->role === 'staff') {
            $user->load('staff'); // ✅ Load staff details
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'required|string|max:20|unique:users,phone,' . $user->id,
            'password'   => 'nullable|string|min:6|confirmed',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            
            // Client Specific
            'city'       => 'nullable|string|max:100',
            'pincode'    => 'nullable|string|max:20',

            // Shared / Staff Specific
            'address'           => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:20', // ✅ Staff specific
        ]);

        // 1. Handle Profile Image
        if ($request->hasFile('profile_image')) {
            if ($user->profile_image && file_exists(public_path($user->profile_image))) {
                @unlink(public_path($user->profile_image));
            }
            
            $file = $request->file('profile_image');
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);
            $user->profile_image = 'uploads/users/' . $filename;
        }

        // 2. Update Basic User Info
        $user->first_name = $validatedData['first_name'];
        $user->last_name  = $validatedData['last_name'];
        $user->name       = $validatedData['first_name'] . ' ' . $validatedData['last_name'];
        $user->phone      = $validatedData['phone'];

        if (!empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        $user->save();

        // 3. Update Role Specific Details
        if ($user->role === 'client') {
            Client::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'address' => $request->address,
                    'city'    => $request->city,
                    'pincode' => $request->pincode,
                ]
            );
            $user->load('client');
        } 
        elseif ($user->role === 'staff') { // ✅ Update Staff Details
            Staff::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $request->phone, // Sync phone to staff table too
                    'address' => $request->address,
                    'emergency_contact' => $request->emergency_contact,
                    // Note: Designation/Salary/Joining Date are usually Admin managed, so we don't update them here.
                ]
            );
            $user->load('staff');
        }

        return response()->json([
            'success' => true, 
            'message' => 'Profile updated successfully.', 
            'data' => $user
        ]);
    }
}