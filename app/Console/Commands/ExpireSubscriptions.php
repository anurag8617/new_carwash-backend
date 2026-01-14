<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserSubscription;
use Carbon\Carbon;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Expire subscriptions that have passed their end date';

    public function handle()
    {
        $today = Carbon::today();

        // Find active subscriptions where end_date is before today
        $count = UserSubscription::where('status', 'active')
            ->whereDate('end_date', '<', $today)
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} subscriptions successfully.");
    }
}