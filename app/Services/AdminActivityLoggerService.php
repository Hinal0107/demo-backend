<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Support\Facades\Request;

class AdminActivityLoggerService
{
    /**
     * Log administrative action.
     */
    public function log(int $adminId, string $action, string $module, ?int $recordId = null, ?array $oldData = null, ?array $newData = null): void
    {
        AdminActivityLog::create([
            'admin_id' => $adminId,
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
