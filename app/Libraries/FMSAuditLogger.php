<?php

namespace App\Libraries;

use App\Modules\ActivityLogs\Models\FMSActivityLogModel;
use App\Modules\ActivityLogs\Services\FMSActivityLogRedactionService;
use App\Modules\ActivityLogs\Services\FMSActivityLogService;
use Throwable;

/**
 * Thin facade untuk mencatat activity log dari mana saja.
 * Gagal menulis tidak boleh melempar exception ke pemanggil.
 */
final class FMSAuditLogger
{
    public static function record(
        string $event,
        string $module,
        ?int $actorId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $description = null,
        array $before = [],
        array $after = [],
        ?string $httpMethod = null,
        ?int $statusCode = null,
    ): void {
        try {
            (new FMSActivityLogService(
                new FMSActivityLogModel(),
                new FMSActivityLogRedactionService(),
            ))->recordActivity([
                'event'        => $event,
                'module'       => $module,
                'actor_user_id' => $actorId,
                'entity_type'  => $entityType,
                'entity_id'    => $entityId,
                'description'  => $description,
                'before'       => $before,
                'after'        => $after,
                'http_method'  => $httpMethod,
                'status_code'  => $statusCode,
            ]);
        } catch (Throwable $e) {
            log_message('error', '[FMSAuditLogger] Failed to record {event}: {msg}', [
                'event' => $event,
                'msg'   => $e->getMessage(),
            ]);
        }
    }
}
