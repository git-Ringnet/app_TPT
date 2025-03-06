<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Receiving;
use App\Models\User;
use Carbon\Carbon;

class UpdateReceivingStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'receiving:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status of receiving records daily based on date_created and conditions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now();

        // Các trạng thái để so sánh
        $messages = [
            '0' => 'tiếp nhận',
            '1' => 'chưa xử lý',
            '2' => 'quá hạn',
        ];

        // Tiếp nhận (< 3 ngày, state = 0)
        Receiving::whereIn('status', [1, 2])
            ->where('date_created', '>=', $today->copy()->subDays(3))
            ->get()
            ->each(function ($receiving) use ($messages) {
                $newState = 0;
                $receiving->update(['state' => 0]);
            });

        // Chưa xử lý (>= 3 ngày, state = 1)
        Receiving::whereIn('status', [1])
            ->where('date_created', '<', $today->copy()->subDays(3))
            ->where('date_created', '>=', $today->copy()->subDays(21))
            ->get()
            ->each(function ($receiving) use ($messages) {

                $newState = 1;
                $message = $messages[1];
                $receiving->update(['state' => 1]);
                // Gửi thông báo
                $this->notifyStateChange($receiving, $newState, $message);
            });

        // Quá hạn (>= 21 ngày, state = 2)
        Receiving::whereNotIn('status', [3, 4])
            ->where('date_created', '<', $today->copy()->subDays(21))
            ->get()
            ->each(function ($receiving) use ($messages) {
                $newState = 2;
                $message = $messages[2];
                $receiving->update(['state' => 2]);

                // Gửi thông báo
                $this->notifyStateChange($receiving, $newState, $message);
            });

        // Hoàn thành hoặc khách không đồng ý
        Receiving::whereIn('status', [3, 4])
            ->update(['state' => 0]);

        $this->info('Receiving statuses updated successfully.');
        return Command::SUCCESS;
    }

    private function notifyStateChange($receiving, $newState, $message)
    {
        $users = User::whereDoesntHave('permissions', function ($query) {
            $query->where('name', 'quankho');
        })->get();
        foreach ($users as $user) {
            // Kiểm tra xem thông báo đã tồn tại chưa
            $notificationExists = $user->notifications()
                ->where('type', \App\Notifications\ReceiNotification::class)
                ->where('data->receiving_id', $receiving->id)
                ->where('data->state', $newState)
                ->exists();
            // Chỉ gửi thông báo nếu chưa tồn tại
            if (!$notificationExists) {
                $user->notify(new \App\Notifications\ReceiNotification($receiving, $newState, $message));
            }
        }
    }
}
