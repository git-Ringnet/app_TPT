<?php

namespace App\Console\Commands;

use App\Models\InventoryLookup;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\InventoryLookupNotification;
use App\Notifications\ReceiNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateInventoryStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:update-storage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cập nhật thời gian tồn kho của Inventory Lookup mỗi ngày';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Lấy tất cả các bản ghi trong InventoryLookup
        $records = InventoryLookup::all();

        foreach ($records as $record) {
            // Tính thời gian tồn kho
            $receivedDate = Carbon::parse($record->import_date); // Ngày nhập kho
            $storageDuration = $receivedDate->diffInDays(Carbon::now()); // Tính số ngày tồn kho

            // Cập nhật thời gian tồn kho
            $record->update(['storage_duration' => $storageDuration]);

            // Kiểm tra lần bảo trì đầu tiên hoặc các lần sau
            if ($storageDuration >= 90 && !$record->warranty_date) {
                // Lần bảo trì đầu tiên
                $record->status = 1;
                $record->save();
                // Gửi thông báo lần bảo trì đầu tiên
                $message = "tới hạn bảo trì";
                $this->notifyStatusChange($record, $message);
            } else if ($record->warranty_date) {
                // Các lần bảo trì tiếp theo
                $nextMaintenanceDate = Carbon::parse($record->warranty_date)->addDays(90);

                if (Carbon::now()->greaterThanOrEqualTo($nextMaintenanceDate)) {
                    // Nếu đã tới thời gian bảo trì tiếp theo
                    $record->status = 1;
                    $record->save();
                    // Gửi thông báo lần bảo trì tiếp theo
                    $message = "tới hạn bảo trì";
                    $this->notifyStatusChange($record, $message);
                }
            } else {
                $record->status = 0;
                $record->save();
            }
        }

        $this->info('Đã cập nhật thời gian tồn kho cho tất cả các sản phẩm.');
    }
    private function notifyStatusChange($record, $message)
    {
        // Lấy tất cả người dùng không có quyền 'dichvu'
        $users = User::whereDoesntHave('permissions', function ($query) {
            $query->where('name', 'dichvu');
        })->get();

        foreach ($users as $user) {
            // Kiểm tra nếu người dùng không có quyền 'dichvu' và chưa nhận thông báo này
            if (!$user->hasPermissionTo('dichvu')) {
                // Gửi thông báo nếu chưa tồn tại thông báo tương tự cho user
                $existingNotification = $user->notifications()
                    ->where('type', InventoryLookupNotification::class)
                    ->where('data->inventoryLookup_id', $record->id)
                    ->where('data->message', $message)
                    ->where('data->warranty_date', $record->warranty_date)
                    ->exists();

                if (!$existingNotification) {
                    // Tạo thông báo mới
                    $user->notify(new InventoryLookupNotification($record, $message));
                }
            }
        }
    }
}
