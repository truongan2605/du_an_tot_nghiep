<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log an event.
     * @param string $event
     * @param mixed|null $model
     * @param array $data
     * @return AuditLog|null
     */
    public static function log(string $event, $model = null, array $data = [])
    {
        try {
            $user = Auth::user();
            $userId = $user->id ?? ($data['user_id'] ?? null);
            $userName = $user->name ?? ($data['user_name'] ?? null);

            $auditableType = null;
            $auditableId = null;
            $old = null;
            $new = null;

            if ($model) {
                $auditableType = get_class($model);
                $auditableId = $model->getKey();

                if (array_key_exists('old', $data)) {
                    $old = $data['old'];
                } else {
                    if (method_exists($model, 'getOriginal')) {
                        $old = $model->getOriginal();
                    }
                }

                if (array_key_exists('new', $data)) {
                    $new = $data['new'];
                } else {
                    if (method_exists($model, 'getAttributes')) {
                        $new = $model->getAttributes();
                    }
                }
            }

            $request = Request::instance();
            $url = $request ? $request->fullUrl() : ($data['url'] ?? null);
            $ip = $request ? $request->ip() : ($data['ip'] ?? null);
            $userAgent = $request ? $request->header('User-Agent') : ($data['user_agent'] ?? null);

            // Avoid storing sensitive fields
            $sensitive = $data['sensitive_fields'] ?? ['password', 'password_hash', 'remember_token'];

            $old = $old ? Arr::except($old, $sensitive) : null;
            $new = $new ? Arr::except($new, $sensitive) : null;

            $payload = [
                'user_id' => $userId,
                'user_name' => $userName,
                'auditable_type' => $auditableType,
                'auditable_id' => $auditableId,
                'event' => $event,
                'old_values' => $old,
                'new_values' => $new,
                'url' => $url,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'meta' => $data['meta'] ?? null,
            ];

            return AuditLog::create($payload);
        } catch (\Throwable $e) {
            \Log::error('AuditLogger error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    /**
     * Helper for model lifecycle events (created/updated/deleted).
     */
    public static function logModelEvent(string $event, $model)
    {
        try {
            $old = null;
            $new = null;

            if ($event === 'created') {
                $old = null;
                $new = $model->getAttributes();
            } elseif ($event === 'deleted') {
                $old = $model->getOriginal();
                $new = null;
            } else { // updated
                $changes = $model->getChanges();
                $old = array_intersect_key($model->getOriginal(), $changes);
                $new = $changes;
            }

            return self::log($event, $model, ['old' => $old, 'new' => $new]);
        } catch (\Throwable $e) {
            \Log::error('AuditLogger::logModelEvent error: ' . $e->getMessage());
            return null;
        }
    }
}