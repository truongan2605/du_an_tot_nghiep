<?php

namespace App\Traits;

use App\Services\AuditLogger;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            AuditLogger::logModelEvent('created', $model);
        });

        static::updated(function ($model) {
            AuditLogger::logModelEvent('updated', $model);
        });

        static::deleted(function ($model) {
            AuditLogger::logModelEvent('deleted', $model);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                AuditLogger::logModelEvent('restored', $model);
            });
        }
    }

    public function audit(string $event, array $meta = [])
    {
        return AuditLogger::log($event, $this, ['meta' => $meta]);
    }
}