<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Store a new rating and review for an order.
     */
    public function store(Request $request, Order $order)
    {
        $client = $request->user();

        // Security Check 1: Ensure the logged-in user is the one who created the order.
        if ($client->id !== $order->client_id) {
            return response()->json(['message' => 'You are not authorized to review this order.'], 403);
        }

        // Security Check 2: Ensure the order has been marked as 'completed'.
        if ($order->status !== 'completed') {
            return response()->json(['message' => 'You can only review completed orders.'], 422);
        }

        // Security Check 3: Ensure this order has not already been reviewed.
        $existingRating = Rating::where('order_id', $order->id)->exists();
        if ($existingRating) {
            return response()->json(['message' => 'This order has already been reviewed.'], 409); // 409 Conflict
        }

        $validatedData = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string',
        ]);

        $rating = Rating::create([
            'order_id' => $order->id,
            'client_id' => $client->id,
            'vendor_id' => $order->vendor_id,
            'rating' => $validatedData['rating'],
            'review_text' => $validatedData['review_text'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your review!',
            'data' => $rating
        ], 201);
    }
}