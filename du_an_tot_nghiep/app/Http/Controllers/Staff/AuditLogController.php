<?php

namespace App\Http\Controllers\Staff;

use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs with optimized summary and safe details.
     */
    public function index(Request $request)
    {
        $query = AuditLog::query()->with('user');

        // Filters
        if ($request->filled('user_id')) {
            // allow user name or id
            $userIdOrName = $request->input('user_id');
            if (is_numeric($userIdOrName)) {
                $query->where('user_id', (int)$userIdOrName);
            } else {
                $query->where('user_name', $userIdOrName);
            }
        }
        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }
        if ($request->filled('auditable_type')) {
            // Allow searching by short class name
            $type = $request->input('auditable_type');
            $query->where('auditable_type', 'like', "%$type%");
        }
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [
                $request->input('from') . ' 00:00:00',
                $request->input('to') . ' 23:59:59',
            ]);
        }
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($qry) use ($q) {
                $qry->where('user_name', 'like', "%$q%")
                    ->orWhere('ip_address', 'like', "%$q%")
                    ->orWhere('url', 'like', "%$q%")
                    ->orWhere('meta', 'like', "%$q%");
            });
        }

        $perPage = 20;
        $logsPaginator = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        // Build auxiliary lists (for filters) from the current page collection (lightweight)
        $collection = $logsPaginator->getCollection();

        $users = $collection->pluck('user_name')->filter()->unique()->values()->all();
        $events = $collection->pluck('event')->filter()->unique()->values()->all();

        // Prepare processed logs: summary + HTML details (escaped) to avoid showing raw JSON/URL
        $processed = $collection->map(function (AuditLog $log) {
            $old = $log->old_values ?? [];
            $new = $log->new_values ?? [];
            // Compute changed fields (intersection of keys)
            $changedKeys = array_unique(array_merge(array_keys((array)$old), array_keys((array)$new)));

            // Build human-readable summary: up to 3 fields: field: old → new
            $summaryParts = [];
                // Process changes for display
                $summary = [];
                $detailsHtml = '';
                
                // Get friendly model name for summary
                $friendlyModel = \App\Helpers\AuditFieldTranslator::translateModel($log->auditable_type);

                if ($log->event === 'created') {
                    $summary[] = "Tạo mới {$friendlyModel}";
                    if ($log->new_values) {
                        $detailsHtml .= '<h6 class="text-success mb-2"><i class="fas fa-plus-circle me-2"></i>Giá trị mới</h6><ul class="list-unstyled small">';
                        foreach ($log->new_values as $field => $newVal) {
                            // Skip blacklisted fields
                            if (\App\Helpers\AuditFieldTranslator::isBlacklisted($field)) {
                                continue;
                            }
                            
                            $label = \App\Helpers\AuditFieldTranslator::translateField($field);
                            $formatted = \App\Helpers\AuditFieldTranslator::translateValue($field, $newVal);
                            $detailsHtml .= "<li><strong>{$label}:</strong> {$formatted}</li>";
                        }
                        $detailsHtml .= '</ul>';
                    }
                } elseif ($log->event === 'updated' && $log->old_values && $log->new_values) {
                    $changes = [];
                    $detailsHtml .= '<div class="table-responsive"><table class="table table-sm mb-0">';
                    $detailsHtml .= '<thead><tr><th>Trường</th><th class="text-end">Giá trị cũ</th><th class="text-end">Giá trị mới</th></tr></thead><tbody>';
                    $hasChanges = false;
                    foreach ($log->new_values as $field => $newVal) {
                        // Skip blacklisted fields
                        if (\App\Helpers\AuditFieldTranslator::isBlacklisted($field)) {
                            continue;
                        }
                        
                        $oldVal = $log->old_values[$field] ?? null;
                        if ($oldVal != $newVal) {
                            $hasChanges = true;
                            $label = \App\Helpers\AuditFieldTranslator::translateField($field);
                            $oldFormatted = \App\Helpers\AuditFieldTranslator::translateValue($field, $oldVal);
                            $newFormatted = \App\Helpers\AuditFieldTranslator::translateValue($field, $newVal);
                            
                            $oldShort = e($this->shorten($oldFormatted));
                            $newShort = e($this->shorten($newFormatted));

                            $changes[] = "{$label}: {$oldShort} → {$newShort}";
                            $detailsHtml .= "<tr><td class=\"small text-muted\">".e($label)."</td><td class=\"text-end small text-danger\">{$oldShort}</td><td class=\"text-end small text-success\">{$newShort}</td></tr>";
                        }
                    }
                    if (!$hasChanges) {
                        $detailsHtml .= '<tr><td colspan="3" class="small text-muted">Không có thay đổi cụ thể</td></tr>';
                    }
                    $detailsHtml .= '</tbody></table></div>';
                    $summary = $changes;
                } elseif ($log->event === 'deleted') {
                    $summary[] = "Xóa {$friendlyModel}";
                    if ($log->old_values) {
                        $detailsHtml .= '<h6 class="text-danger mb-2"><i class="fas fa-trash-alt me-2"></i>Giá trị cũ (đã xóa)</h6><ul class="list-unstyled small">';
                        foreach ($log->old_values as $field => $oldVal) {
                            // Skip blacklisted fields
                            if (\App\Helpers\AuditFieldTranslator::isBlacklisted($field)) {
                                continue;
                            }
                            
                            $label = \App\Helpers\AuditFieldTranslator::translateField($field);
                            $formatted = \App\Helpers\AuditFieldTranslator::translateValue($field, $oldVal);
                            $detailsHtml .= "<li><strong>{$label}:</strong> {$formatted}</li>";
                        }
                        $detailsHtml .= '</ul>';
                    }
                }
                
                // Limit summary to 3 items
                $changesSummary = empty($summary) ? '-' : implode(' • ', array_slice($summary, 0, 3));
                if (count($summary) > 3) {
                    $changesSummary .= " (+".(count($summary) - 3)." khác)";
                }

            // Meta (note) show if present - escape
            $metaNote = null;
            if (is_array($log->meta) && isset($log->meta['note'])) {
                $metaNote = e(Str::limit($log->meta['note'], 400));
                $detailsHtml .= "<div class=\"mt-2\"><strong>Ghi chú:</strong><div class=\"small text-muted\">{$metaNote}</div></div>";
            }

            return (object)[
                'id' => $log->id,
                'created_at' => $log->created_at,
                'user_name' => $log->user_name ?? ($log->user?->name ?? '-'),
                'user_id' => $log->user_id,
                'event' => $log->event,
                'auditable' => \App\Helpers\AuditFieldTranslator::translateModel($log->auditable_type) . ' #' . $log->auditable_id,
                'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id,
                'changes_summary' => $changesSummary,
                'detailsHtml' => $detailsHtml,
                'isRecent' => $log->created_at->gt(now()->subDay()),
            ];
        });

        // Replace paginator collection with processed objects for the view
        $logsPaginator->setCollection($processed);

        // totals and summary (computed efficiently)
        $totalCount = $logsPaginator->total();
        $recentCount = AuditLog::where('created_at', '>=', now()->subDay())->count();

        return view('staff.audit.index', [
            'logs' => $logsPaginator,
            'users' => $users,
            'events' => $events,
            'totalCount' => $totalCount,
            'recentCount' => $recentCount,
        ]);
    }

    /**
     * Shorten long string/values to a safe displayable text.
     */
    protected function shorten($val, $len = 60)
    {
        if (is_null($val)) return '—';
        if (is_array($val) || is_object($val)) {
            $s = json_encode($val, JSON_UNESCAPED_UNICODE);
        } else {
            $s = (string)$val;
        }
        $s = trim($s);
        if (mb_strlen($s) > $len) {
            return mb_substr($s, 0, $len - 1) . '…';
        }
        return $s;
    }
}