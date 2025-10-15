<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientSubscription;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClientSubscriptionController extends Controller
{
    /**
     * Display a listing of the client's subscriptions.
     */
    public function index(Request $request)
    {
        $client = $request->user();

        $subscriptions = ClientSubscription::where('client_id', $client->id)
            ->with('plan') // Eager load the plan details
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    /**
     * Store a newly created subscription in storage.
     */
    public function store(Request $request)
    {
        $client = $request->user();

        // Ensure the user is a client before they can subscribe
        if ($client->role !== 'client') {
            return response()->json(['message' => 'Only clients can subscribe to plans.'], 403);
        }

        $validatedData = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($validatedData['plan_id']);

        // Optional: Check if the client already has an active subscription for this plan
        $existingActiveSubscription = ClientSubscription::where('client_id', $client->id)
            ->where('plan_id', $plan->id)
            ->where('status', 'active')
            ->exists();

        if ($existingActiveSubscription) {
            return response()->json(['message' => 'You already have an active subscription for this plan.'], 409);
        }

        // Create the subscription
        $subscription = ClientSubscription::create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'vendor_id' => $plan->vendor_id,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays($plan->duration_days),
            'remaining_services' => $plan->service_limit,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to the plan!',
            'data' => $subscription
        ], 201);
    }
}