<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Carbon\Carbon;

class SendSubscriptionExpiryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-subscription-expiry-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notifications for subscriptions expiring in 7, 3, or 1 days';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\SubscriptionService $subscriptionService): int
    {
        $this->info("Running auto-expiration check for outdated subscriptions...");
        $expired = $subscriptionService->expireOutdatedSubscriptions();
        $this->info("Auto-expired {$expired} outdated/exhausted subscriptions.");

        $this->info("Checking subscriptions expiring soon...");

        $targets = [7, 3, 2, 1];
        $notifiedCount = 0;

        foreach ($targets as $days) {
            $targetDate = Carbon::today()->addDays($days)->toDateString();

            $expiringSubscriptions = Subscription::where('status', 'ACTIVE')
                ->where('payment_status', 'PAID')
                ->where(function ($q) use ($targetDate) {
                    $q->whereDate('max_validity_date', $targetDate)
                      ->orWhere(function ($q2) use ($targetDate) {
                          $q2->whereNull('max_validity_date')->whereDate('end_date', $targetDate);
                      });
                })
                ->get();

            foreach ($expiringSubscriptions as $sub) {
                event(new \App\Events\SubscriptionExpiringEvent($sub, $days));
                $notifiedCount++;
            }
        }

        $this->info("Dispatched {$notifiedCount} expiry reminder events.");
        return Command::SUCCESS;
    }
}
