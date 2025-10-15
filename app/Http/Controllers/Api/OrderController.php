<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Store a newly created order in storage.
     * This is typically done by a 'client'.
     */
    public function store(Request $request)
    {
        $client = $request->user();

        // Ensure the user is a client
        if ($client->role !== 'client') {
            return response()->json(['message' => 'Only clients can create orders.'], 403);
        }

        $validatedData = $request->validate([
            'service_id' => 'required|exists:services,id',
            'scheduled_time' => 'required|date|after:now',
        ]);

        // Find the service to get its details
        $service = Service::findOrFail($validatedData['service_id']);

        // Create the order
        $order = Order::create([
            'client_id' => $client->id,
            'vendor_id' => $service->vendor_id,
            'service_id' => $service->id,
            'scheduled_time' => $validatedData['scheduled_time'],
            'price' => $service->price, // Get the price from the service
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => $order
        ], 201);
    }

    /**
     * Display a listing of the orders.
     * Shows different results based on user's role (client or vendor).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = [];

        if ($user->role === 'client') {
            // If the user is a client, get all orders they created
            $orders = Order::where('client_id', $user->id)
                ->with(['vendor', 'service']) // Eager load related data
                ->latest()
                ->get();
        } elseif ($user->role === 'vendor') {
            // If the user is a vendor, find their vendor profile first
            $vendor = Vendor::where('admin_id', $user->id)->first();
            if ($vendor) {
                // Get all orders for that vendor profile
                $orders = Order::where('vendor_id', $vendor->id)
                    ->with(['client', 'service']) // Eager load related data
                    ->latest()
                    ->get();
            }
        }


        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
 * Update the status of a specific order.
 * This is typically done by a 'vendor'.
 */
    public function updateStatus(Request $request, Order $order)
    {
        $user = $request->user();
        $vendor = Vendor::where('admin_id', $user->id)->first();

        // Authorization: Check if the order belongs to the logged-in vendor
        if (!$vendor || $order->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Not authorized to update this order.'], 403);
        }

        $validatedData = $request->validate([
            'status' => ['required', Rule::in(['pending', 'assigned', 'in_progress', 'completed', 'cancelled'])],
        ]);

        $order->update(['status' => $validatedData['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order
        ]);
    }

        /**
     * Assign a staff member to a specific order.
     * This is done by a 'vendor'.
     */
    public function assignStaff(Request $request, Order $order)
    {
        $user = $request->user();
        $vendor = Vendor::where('admin_id', $user->id)->first();

        // Authorization: Check if the order belongs to the logged-in vendor
        if (!$vendor || $order->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Not authorized to update this order.'], 403);
        }

        $validatedData = $request->validate([
            'staff_id' => 'required|exists:staffs,id',
        ]);

        // Extra check: Ensure the staff member belongs to the vendor
        $staff = Staff::find($validatedData['staff_id']);
        if ($staff->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'This staff member does not belong to your vendor profile.'], 422);
        }

        $order->update(['staff_id' => $validatedData['staff_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Staff assigned to order successfully.',
            'data' => $order
        ]);
    }
}