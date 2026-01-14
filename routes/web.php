<?php

use Illuminate\Support\Facades\Route;
use App\Models\UserSubscription;
use App\Models\UserSubscriptionBalance;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/fix-balances', function () {
    // 1. Get all active subscriptions
    $subs = UserSubscription::with('plan.services')->get();
    $count = 0;

    foreach ($subs as $sub) {
        // 2. Check if balances are missing
        if ($sub->balances()->count() === 0) {
            foreach ($sub->plan->services as $service) {
                // 3. Create the missing balance record
                UserSubscriptionBalance::create([
                    'user_subscription_id' => $sub->id,
                    'service_id' => $service->id,
                    'total_qty' => $service->pivot->quantity ?? 1, // Default to 1 if missing
                    'used_qty' => 0
                ]);
            }
            $count++;
        }
    }

    return "Fixed balances for $count subscriptions. You can now check the Vendor Dashboard.";
});