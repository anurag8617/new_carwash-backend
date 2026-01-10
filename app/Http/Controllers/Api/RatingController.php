<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Vendor; // ✅ Import Vendor
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Store a new rating and review for an order.
     */
    public function store(Request $request, Order $order)
    {
        $client = $request->user();

        // 1. Authorization Checks
        if ($client->id !== $order->client_id) {
            return response()->json(['message' => 'You are not authorized to review this order.'], 403);
        }

        if ($order->status !== 'completed') {
            return response()->json(['message' => 'You can only review completed orders.'], 422);
        }

        $existingRating = Rating::where('order_id', $order->id)->exists();
        if ($existingRating) {
            return response()->json(['message' => 'This order has already been reviewed.'], 409);
        }

        // 2. Validation
        $validatedData = $request->validate([
            'rating' => 'required|integer|min:1|max:5',       // Service/Vendor Rating
            'staff_rating' => 'nullable|integer|min:1|max:5', // Staff Rating (Optional)
            'review_text' => 'nullable|string',
        ]);

        // 3. Create Rating
        $rating = Rating::create([
            'order_id' => $order->id,
            'client_id' => $client->id,
            'vendor_id' => $order->vendor_id,
            'staff_id' => $order->staff_id,
            'rating' => $validatedData['rating'],
            'staff_rating' => $validatedData['staff_rating'] ?? null,
            'review_text' => $validatedData['review_text'] ?? null,
        ]);

        // 4. ✅ FIX: Update Vendor's Average Rating & Count
        $vendor = Vendor::find($order->vendor_id);
        if ($vendor) {
            // Calculate new average from database to be accurate
            $avgRating = Rating::where('vendor_id', $vendor->id)->avg('rating');
            $count = Rating::where('vendor_id', $vendor->id)->count();

            $vendor->update([
                'average_rating' => $avgRating,
                'review_count' => $count
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your review!',
            'data' => $rating
        ], 201);
    }
}