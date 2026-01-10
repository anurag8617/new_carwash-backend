<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalVendors = Vendor::count();
        $totalUsers = User::where('role', 'client')->count();
        $activeSubscriptions = DB::table('client_subscriptions')->where('status', 'active')->count();

        // Graph Data
        $revenueData = Order::select(
            DB::raw('SUM(price) as total'),
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_year"),
            DB::raw("DATE_FORMAT(created_at, '%b') as month")
        )
        ->where('payment_status', 'paid')
        ->where('created_at', '>=', Carbon::now()->subMonths(6))
        ->groupBy('month_year', 'month')
        ->orderBy('month_year', 'asc')
        ->get();

        $recentVendors = Vendor::with('admin')->latest()->take(5)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [ 'vendors' => $totalVendors, 'users' => $totalUsers, 'subscriptions' => $activeSubscriptions ],
                'graph' => $revenueData,
                'recent_vendors' => $recentVendors
            ]
        ]);
    }
}