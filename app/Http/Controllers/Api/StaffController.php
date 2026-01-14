<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\UserSubscriptionBalance; 
use App\Models\Order;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $vendorUser = $request->user();
        $vendor = Vendor::where('admin_id', $vendorUser->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found.'], 404);
        }

       $staff = Staff::where('vendor_id', $vendor->id)
            ->with(['user', 'ratings']) 
            ->get();

        return response()->json(['success' => true, 'data' => $staff]);
    }

    // Http/Controllers/Api/StaffController.php

    public function show(Request $request, $id)
    {
        $vendorUser = $request->user();
        $vendor = Vendor::where('admin_id', $vendorUser->id)->first();
        
        // ❌ DELETE THIS LINE BELOW (It causes the crash because 'ratings.user' doesn't exist)
        // $staff = Staff::with(['user', 'ratings.user'])->find($id); 
        
        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found.'], 404);
        }

        // ✅ KEEP THIS ONE (It uses 'ratings.client' which is correct)
        $staff = Staff::with(['user', 'ratings.client', 'ratings.service'])
            ->where('vendor_id', $vendor->id)
            ->where('id', $id)
            ->first();

        if (!$staff) {
            return response()->json(['message' => 'Staff member not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $staff
        ]);
    }

    public function store(Request $request)
    {
        $vendorUser = $request->user();
        $vendor = Vendor::where('admin_id', $vendorUser->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'joining_date' => 'nullable|date',
            'salary' => 'nullable|numeric',
            'designation' => 'nullable|string|max:100',       // <--- NEW
            'emergency_contact' => 'nullable|string|max:20', // <--- NEW
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'id_proof_image' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048', // <--- NEW
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Handle Profile Image
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_p_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/staff'), $filename);
                $imagePath = 'uploads/staff/' . $filename;
            }

            // 2. Handle ID Proof Image
            $idProofPath = null;
            if ($request->hasFile('id_proof_image')) {
                $file = $request->file('id_proof_image');
                $filename = time() . '_id_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/staff/docs'), $filename);
                $idProofPath = 'uploads/staff/docs/' . $filename;
            }

            // 3. Create User
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'staff',
                'phone' => $request->phone,
                'profile_image' => $imagePath, // Save to User table too
            ]);

            // 4. Create Staff
            $staff = Staff::create([
                'user_id' => $user->id,
                'vendor_id' => $vendor->id,
                'phone' => $request->phone,
                'address' => $request->address,
                'joining_date' => $request->joining_date ?? now(),
                'salary' => $request->salary ?? 0,
                'status' => 'active',
                'profile_image' => $imagePath,
                'designation' => $request->designation,             // <--- NEW
                'emergency_contact' => $request->emergency_contact, // <--- NEW
                'id_proof_image' => $idProofPath,                   // <--- NEW
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Staff created.', 'data' => $staff], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating staff: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $vendorUser = $request->user();
        $vendor = Vendor::where('admin_id', $vendorUser->id)->first();
        $staff = Staff::where('vendor_id', $vendor->id)->where('id', $id)->first();

        if (!$staff) return response()->json(['message' => 'Staff member not found.'], 404);

        $user = User::find($staff->user_id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:100',
            'emergency_contact' => 'nullable|string|max:20',
            'image' => 'nullable|image|max:2048',
            'id_proof_image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        try {
            DB::beginTransaction();

            // Update Profile Image
            if ($request->hasFile('image')) {
                if ($staff->profile_image && file_exists(public_path($staff->profile_image))) {
                    @unlink(public_path($staff->profile_image));
                }
                $file = $request->file('image');
                $filename = time() . '_p_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/staff'), $filename);
                $staff->profile_image = 'uploads/staff/' . $filename;
                $user->profile_image = 'uploads/staff/' . $filename; // Sync with User table
            }

            // Update ID Proof
            if ($request->hasFile('id_proof_image')) {
                if ($staff->id_proof_image && file_exists(public_path($staff->id_proof_image))) {
                    @unlink(public_path($staff->id_proof_image));
                }
                $file = $request->file('id_proof_image');
                $filename = time() . '_id_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/staff/docs'), $filename);
                $staff->id_proof_image = 'uploads/staff/docs/' . $filename;
            }

            // Update User
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->name = $request->first_name . ' ' . $request->last_name;
            $user->email = $request->email;
            if ($request->filled('password')) $user->password = Hash::make($request->password);
            $user->save();

            // Update Staff
            $staff->phone = $request->phone;
            $staff->address = $request->address;
            $staff->joining_date = $request->joining_date;
            $staff->salary = $request->salary;
            $staff->designation = $request->designation;             // <--- NEW
            $staff->emergency_contact = $request->emergency_contact; // <--- NEW
            $staff->status = $request->status ?? $staff->status;
            $staff->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Staff updated.', 'data' => $staff]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $vendorUser = $request->user();
        $vendor = Vendor::where('admin_id', $vendorUser->id)->first();
        $staff = Staff::where('vendor_id', $vendor->id)->where('id', $id)->first();

        if (!$staff) return response()->json(['message' => 'Not found.'], 404);

        // Delete images
        if ($staff->profile_image && file_exists(public_path($staff->profile_image))) @unlink(public_path($staff->profile_image));
        if ($staff->id_proof_image && file_exists(public_path($staff->id_proof_image))) @unlink(public_path($staff->id_proof_image));

        $user = User::find($staff->user_id);
        $staff->delete();
        if ($user) $user->delete();

        return response()->json(['success' => true, 'message' => 'Staff deleted.']);
    }

    public function startService($id)
    {
        $order = Order::findOrFail($id);
        
        if ($order->status !== 'assigned') { // Or 'pending' depending on your flow
            return response()->json(['message' => 'Order must be assigned to start.'], 400);
        }

        // Generate 4-digit OTP
        $otp = rand(1000, 9999);
        $order->update([
            'status' => 'in_progress',
            'otp' => $otp
        ]);

        // 🔔 TODO: Send SMS/Notification to Client here
        // Notification::send($order->user, new ServiceStarted($otp));

        return response()->json([
            'message' => 'Service Started. OTP sent to client.',
            'otp_debug' => $otp // Remove in production
        ]);
    }

    // 2. Complete Service -> Verify OTP
    public function completeService(Request $request, $id)
    {
        $request->validate(['otp' => 'required|digits:4']);

        $order = Order::findOrFail($id);

        if ($order->otp != $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        // ✅ If Subscription Order: Deduct Balance
        if ($order->user_subscription_id) {
            $balance = UserSubscriptionBalance::where('user_subscription_id', $order->user_subscription_id)
                        ->where('service_id', $order->service_id)
                        ->first();
            
            if ($balance) {
                $balance->increment('used_qty');
            }
        }

        $order->update([
            'status' => 'completed',
            'otp' => null // Clear OTP for security
        ]);

        return response()->json(['message' => 'Service Completed Successfully!']);
    }
}