<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubscriptionService;
use Carbon\Carbon;

class GenerateSubscriptionOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-subscription-orders {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily scheduled orders for active subscriptions';

    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateParam = $this->argument('date');
        $targetDate = $dateParam ? Carbon::parse($dateParam) : Carbon::today();

        $this->info("Starting scheduled subscription order generation for date: " . $targetDate->toDateString());
        
        $count = $this->subscriptionService->generateScheduledOrdersForDate($targetDate);

        $this->info("Successfully generated {$count} orders for today.");
        return Command::SUCCESS;
    }
}
