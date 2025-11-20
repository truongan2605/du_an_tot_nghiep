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
            $maxPreview = 3;
            $count = 0;
            foreach ($changedKeys as $k) {
                if ($count >= $maxPreview) break;
                $o = array_key_exists($k, (array)$old) ? $old[$k] : null;
                $n = array_key_exists($k, (array)$new) ? $new[$k] : null;
                // string truncate values
                $oStr = $o === null ? '—' : $this->shorten($o);
                $nStr = $n === null ? '—' : $this->shorten($n);
                $summaryParts[] = "{$k}: {$oStr} → {$nStr}";
                $count++;
            }
            $remaining = max(0, count($changedKeys) - $maxPreview);
            if ($remaining > 0) {
                $summaryParts[] = "+{$remaining} khác";
            }
            $changesSummary = empty($summaryParts) ? '-' : implode(' • ', $summaryParts);

            // Build safe HTML for modal details (table of fields) - escape values
            $detailsHtml = '<div class="table-responsive"><table class="table table-sm mb-0">';
            $detailsHtml .= '<thead><tr><th>Trường</th><th class="text-end">Giá trị cũ</th><th class="text-end">Giá trị mới</th></tr></thead><tbody>';
            foreach ($changedKeys as $k) {
                $o = array_key_exists($k, (array)$old) ? $old[$k] : null;
                $n = array_key_exists($k, (array)$new) ? $new[$k] : null;
                $oSafe = e($this->shorten($o));
                $nSafe = e($this->shorten($n));
                $detailsHtml .= "<tr><td class=\"small text-muted\">".e($k)."</td><td class=\"text-end small\">{$oSafe}</td><td class=\"text-end small\">{$nSafe}</td></tr>";
            }
            if (empty($changedKeys)) {
                $detailsHtml .= '<tr><td colspan="3" class="small text-muted">Không có thay đổi cụ thể</td></tr>';
            }
            $detailsHtml .= '</tbody></table></div>';

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
                'auditable' => class_basename($log->auditable_type) . '#' . $log->auditable_id,
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