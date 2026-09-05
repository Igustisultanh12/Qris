<?php

use App\Jobs\ExpireTransactionsJob;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console & Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Requirement #22: Scheduled Laravel job: ExpireTransactionsJob jalankan setiap menit.
Schedule::job(new ExpireTransactionsJob())->everyMinute();

// Check for overdue invoices and mark them overdue
Schedule::call(function () {
    Invoice::where('status', 'pending')
        ->where('due_date', '<', now()->toDateString())
        ->update(['status' => 'overdue']);
})->daily();

// Check expired subscriptions past grace period
Schedule::call(function () {
    Subscription::where('status', 'active')
        ->whereNotNull('ends_at')
        ->where('ends_at', '<', now())
        ->chunkById(50, function ($subscriptions) {
            foreach ($subscriptions as $sub) {
                if (!$sub->isInGracePeriod()) {
                    $sub->update(['status' => 'expired']);
                } else {
                    $sub->update(['status' => 'past_due']);
                }
            }
        });
})->daily();

// Prune old API usage logs older than 60 days
Schedule::call(function () {
    \App\Models\ApiUsageLog::where('created_at', '<', now()->subDays(60))->delete();
})->weekly();
