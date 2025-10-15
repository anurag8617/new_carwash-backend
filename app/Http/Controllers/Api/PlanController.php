<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $vendor = Vendor::where('admin_id', $user->id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $vendor->plans
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $vendor = Vendor::where('admin_id', $user->id)->firstOrFail();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'service_limit' => 'required|integer|min:1',
        ]);

        $validatedData['vendor_id'] = $vendor->id;

        $plan = Plan::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully.',
            'data' => $plan
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Plan $plan)
    {
        $vendor = Vendor::where('admin_id', $request->user()->id)->firstOrFail();

        if ($plan->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Not authorized to view this plan.'], 403);
        }

        return response()->json(['success' => true, 'data' => $plan]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        $vendor = Vendor::where('admin_id', $request->user()->id)->firstOrFail();

        if ($plan->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Not authorized to update this plan.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'duration_days' => 'sometimes|required|integer|min:1',
            'service_limit' => 'sometimes|required|integer|min:1',
            'status' => ['sometimes', 'required', Rule::in(['active', 'disabled'])],
        ]);

        $plan->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully.',
            'data' => $plan
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Plan $plan)
    {
        $vendor = Vendor::where('admin_id', $request->user()->id)->firstOrFail();

        if ($plan->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Not authorized to delete this plan.'], 403);
        }

        $plan->delete();

        return response()->json(['success' => true, 'message' => 'Plan deleted successfully.']);
    }

    /**
     * Display a public listing of all active plans.
     */
    public function getPublicPlans()
    {
        $plans = Plan::where('status', 'active')->with('vendor')->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }
}