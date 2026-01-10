<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\Order;
use App\Models\Staff;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Rating;

class VendorDashboardController extends Controller
{
   public function index()
    {
        $user = Auth::user();
        $vendor = Vendor::where('admin_id', $user->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        // 1. Fetch Key Metrics
        $totalOrders = Order::where('vendor_id', $vendor->id)->count();
        
        $totalRevenue = Order::where('vendor_id', $vendor->id)
            ->where('payment_status', 'paid')
            ->sum('price');

        $totalStaff = Staff::where('vendor_id', $vendor->id)->count();
        
        $activeServices = Service::where('vendor_id', $vendor->id)->count();

        // ✅ Calculate Average Rating
        $avgRating = Rating::where('vendor_id', $vendor->id)->avg('rating');
        $formattedRating = number_format($avgRating, 1); // e.g., "4.5"

        // 2. Fetch Monthly Revenue
        $revenueData = Order::select(
            DB::raw('SUM(price) as revenue'),
            DB::raw("DATE_FORMAT(created_at, '%b') as name")
        )
        ->where('vendor_id', $vendor->id)
        ->where('payment_status', 'paid')
        ->where('created_at', '>=', Carbon::now()->subMonths(6))
        ->groupBy('name')
        ->orderBy('created_at', 'ASC')
        ->get();

        // 3. Fetch Recent Orders
        $recentOrders = Order::with(['client', 'service']) 
            ->where('vendor_id', $vendor->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'user' => $order->client,
                    'service' => $order->service,
                    'total_price' => $order->price,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ];
            });

        return response()->json([
            'stats' => [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'total_staff' => $totalStaff,
                'active_services' => $activeServices,
                'average_rating' => $formattedRating, // ✅ Send Rating
            ],
            'chart_data' => $revenueData,
            'recent_orders' => $recentOrders
        ]);
    }

    // ✅ New Function for History Page
    public function history()
    {
        $user = Auth::user();
        $vendor = Vendor::where('admin_id', $user->id)->firstOrFail();

        // Fetch Ratings with related Order, Client, and Staff
        $history = Rating::where('vendor_id', $vendor->id)
            ->with(['client', 'staff.user', 'order.service']) // Eager load relationships
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
}