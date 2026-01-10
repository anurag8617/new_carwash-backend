<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BannerController extends Controller
{
    /**
     * Public: Get active banners based on Status + Dates + Priority
     */
    public function getActiveBanners(Request $request)
    {
        $now = \Carbon\Carbon::now();
        $position = $request->query('position'); // Remove the default 'top'

        $query = Banner::where('status', 'active');

        // ONLY filter by position if the frontend specifically asks for one
        // AND the requested position is not 'all'
        if ($position && $position !== 'all') {
            $query->where('position', $position);
        }

        $banners = $query->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->orderBy('priority', 'desc')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $banners]);
    }
    /**
     * Admin: List all banners
     */
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $banners = Banner::orderBy('priority', 'desc')->latest()->get();
        return response()->json(['success' => true, 'data' => $banners]);
    }

    /**
     * Admin: Upload a new banner
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $request->validate([
            'image'      => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'title'      => 'nullable|string|max:255',
            'link'       => 'nullable|url',
            'position'   => 'in:top,middle,bottom',
            'priority'   => 'integer',
            'status'     => 'in:active,inactive',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('uploads/banners', 'public');
        }

        $banner = Banner::create([
            'title'      => $request->title,
            'link'       => $request->link,
            'image'      => $path,
            'position'   => $request->position ?? 'top',
            'priority'   => $request->priority ?? 0,
            'status'     => $request->status ?? 'active',
            'is_premium' => $request->boolean('is_premium'),
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Banner added successfully.',
            'data'    => $banner
        ], 201);
    }

    /**
     * Admin: Delete a banner
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $banner = Banner::findOrFail($id);

        if ($banner->image && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return response()->json(['success' => true, 'message' => 'Banner deleted successfully.']);
    }
    
    /**
     * Admin: Toggle Active Status (Enum)
     */
    public function toggleStatus(Request $request, $id)
    {
         if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }
        
        $banner = Banner::findOrFail($id);
        
        // Toggle between 'active' and 'inactive' strings
        $banner->status = ($banner->status === 'active') ? 'inactive' : 'active';
        $banner->save();
        
        return response()->json(['success' => true, 'data' => $banner]);
    }
}