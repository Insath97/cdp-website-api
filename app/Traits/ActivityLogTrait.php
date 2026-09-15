<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait ActivityLogTrait
{
    /**
     * Sensitive fields that should be redacted from activity log payloads.
     */
    protected array $sensitiveLogKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'auth_token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
    ];

    /**
     * Log activity to database and Laravel log file.
     *
     * @param string $action
     * @param string $module
     * @param string $description
     * @param array|null $payload
     * @param int|null $userId
     * @return void
     */
    public function logActivity(string $action, string $module, string $description, ?array $payload = null, ?int $userId = null)
    {
        try {
            $effectiveUserId = $userId ?? Auth::guard('api')->id();
            $sanitizedPayload = $this->sanitizeLogPayload($payload);

            // DB logging
            ActivityLog::create([
                'user_id' => $effectiveUserId,
                'action' => strtoupper($action),
                'module' => $module,
                'description' => $description,
                'payload' => $sanitizedPayload,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(), 
            ]);

            // Laravel file logging
            $payloadStr = $sanitizedPayload ? ' | Payload: ' . json_encode($sanitizedPayload) : '';
            Log::info("[{$module}] " . strtoupper($action) . ": {$description}{$payloadStr} | User ID: " . ($effectiveUserId ?? 'Guest') . " | IP: " . request()->ip());

        } catch (\Throwable $th) {
            // Silently fail DB logging but log the failure to Laravel logs
            Log::error("Failed to log activity to database: " . $th->getMessage());
            Log::info("[{$module}] " . strtoupper($action) . ": {$description} (DB Log Failed)");
        }
    }

    /**
     * Recursively sanitize sensitive keys in the activity payload.
     *
     * @param array|null $payload
     * @return array|null
     */
    protected function sanitizeLogPayload(?array $payload): ?array
    {
        if (is_null($payload)) {
            return null;
        }

        $sanitized = [];
        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), $this->sensitiveLogKeys, true)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeLogPayload($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
