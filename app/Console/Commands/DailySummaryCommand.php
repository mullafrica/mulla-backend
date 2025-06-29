<?php

namespace App\Console\Commands;

use App\Jobs\DiscordBots;
use App\Models\MullaUserTransactions;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DailySummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mulla:daily-summary {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send daily summary to Discord';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::yesterday();
        
        $this->info("Generating daily summary for {$date->toDateString()}");
        
        // Get transaction statistics
        $transactions = MullaUserTransactions::whereDate('created_at', $date)
            ->where('status', true);
            
        $totalTransactions = $transactions->count();
        $totalVolume = $transactions->sum('amount');
        
        // Get success rate
        $allTransactions = MullaUserTransactions::whereDate('created_at', $date);
        $successRate = $allTransactions->count() > 0 
            ? round(($totalTransactions / $allTransactions->count()) * 100, 2)
            : 0;
            
        // Get service breakdown
        $serviceBreakdown = $transactions->select('type')
            ->groupBy('type')
            ->selectRaw('type, count(*) as count, sum(amount) as volume')
            ->orderByDesc('count')
            ->get();
            
        $topService = $serviceBreakdown->first();
        
        // Get provider breakdown for electricity
        $providerBreakdown = $transactions->where('type', 'electricity')
            ->groupBy('provider')
            ->selectRaw('provider, count(*) as count')
            ->get();
            
        // Get new users
        $newUsers = User::whereDate('created_at', $date)->count();
        
        // Get active users (users who made transactions)
        $activeUsers = $transactions->distinct('user_id')->count('user_id');
        
        // Get failed transactions breakdown
        $failedTransactions = MullaUserTransactions::whereDate('created_at', $date)
            ->where('status', false)
            ->groupBy('vtp_status')
            ->selectRaw('vtp_status, count(*) as count')
            ->get();
            
        // Get cashback given
        $totalCashback = $transactions->sum('cashback');
        
        // Top users by transaction volume
        $topUsers = $transactions->groupBy('user_id')
            ->selectRaw('user_id, count(*) as transaction_count, sum(amount) as total_volume')
            ->orderByDesc('total_volume')
            ->limit(5)
            ->get();
            
        // Build service details
        $serviceDetails = $serviceBreakdown->map(function ($service) {
            return $service->type . ': ' . $service->count . ' txns (₦' . number_format($service->volume) . ')';
        })->join(', ');
        
        // Build provider details for electricity
        $providerDetails = $providerBreakdown->map(function ($provider) {
            return ucfirst($provider->provider) . ': ' . $provider->count;
        })->join(', ');
        
        // Build failed transaction details
        $failureDetails = $failedTransactions->map(function ($failure) {
            $status = match($failure->vtp_status) {
                0 => 'Failed',
                1 => 'Success',
                2 => 'Pending', 
                3 => 'Reversed',
                default => 'Unknown'
            };
            return $status . ': ' . $failure->count;
        })->join(', ');

        // Send to Discord
        DiscordBots::dispatch([
            'message' => '📊 DAILY SUMMARY - ' . $date->format('M d, Y'),
            'details' => [
                'date' => $date->toDateString(),
                'overview' => [
                    'total_transactions' => number_format($totalTransactions),
                    'total_volume' => '₦' . number_format($totalVolume),
                    'success_rate' => $successRate . '%',
                    'total_cashback' => '₦' . number_format($totalCashback),
                    'new_users' => number_format($newUsers),
                    'active_users' => number_format($activeUsers)
                ],
                'services' => [
                    'top_service' => $topService ? $topService->type . ' (' . $topService->count . ' txns)' : 'None',
                    'breakdown' => $serviceDetails ?: 'No transactions'
                ],
                'electricity_providers' => [
                    'breakdown' => $providerDetails ?: 'No electricity transactions'
                ],
                'failures' => [
                    'total_failed' => $allTransactions->count() - $totalTransactions,
                    'breakdown' => $failureDetails ?: 'No failures'
                ],
                'top_users' => $topUsers->map(function ($user) {
                    return 'User ' . $user->user_id . ': ₦' . number_format($user->total_volume) . ' (' . $user->transaction_count . ' txns)';
                })->take(3)->join(', ') ?: 'No active users',
                'generated_at' => now()->toDateTimeString()
            ]
        ]);
        
        $this->info("Daily summary sent to Discord successfully!");
        
        return Command::SUCCESS;
    }
}