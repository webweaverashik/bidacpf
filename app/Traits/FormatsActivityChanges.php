<?php
namespace App\Traits;

use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Renders a Spatie activity record's changed attributes as a compact
 * "field: old → new" diff, styled like the audit-logs index "Changes" column.
 *
 * Spatie's logOnlyDirty() stores two buckets inside `properties`:
 *   - attributes : the NEW values after the change
 *   - old        : the PREVIOUS values (only present on `updated`)
 *
 * On `created` there is no `old`, so values render as freshly-set badges.
 * On `deleted`/`restored` there is usually nothing to diff, so we fall back
 * to a muted dash.
 */
trait FormatsActivityChanges
{
    /**
     * Attribute keys that should never be shown in the diff (noise / secrets).
     */
    protected array $hiddenChangeKeys = ['password', 'remember_token', 'updated_at', 'created_at'];

    public function renderActivityChanges(Activity $activity): string
    {
        $props = $activity->properties;

        $new = (array) (method_exists($props, 'get') ? $props->get('attributes', []) : ($props['attributes'] ?? []));
        $old = (array) (method_exists($props, 'get') ? $props->get('old', []) : ($props['old'] ?? []));

        foreach ($this->hiddenChangeKeys as $key) {
            unset($new[$key], $old[$key]);
        }

        if (empty($new) && empty($old)) {
            return '<span class="text-muted">—</span>';
        }

        // Iterate over the union of keys so we never miss a side.
        $keys = array_keys($new + $old);

        $rows = '';
        foreach ($keys as $key) {
            $hasOld = array_key_exists($key, $old);
            $label  = e(Str::headline($key));

            $newVal = $this->formatChangeValue($new[$key] ?? null);

            if ($hasOld) {
                $oldVal = $this->formatChangeValue($old[$key] ?? null);
                $rows .= '<div class="d-flex align-items-center flex-wrap mb-1">'
                    . '<span class="text-gray-600 fw-semibold me-2">' . $label . ':</span>'
                    . '<span class="badge badge-light-danger text-decoration-line-through me-1">' . $oldVal . '</span>'
                    . '<i class="ki-outline ki-arrow-right fs-7 text-gray-400 me-1"></i>'
                    . '<span class="badge badge-light-success">' . $newVal . '</span>'
                    . '</div>';
            } else {
                // Created / context-only value — show as a single "set to" badge.
                $rows .= '<div class="d-flex align-items-center flex-wrap mb-1">'
                    . '<span class="text-gray-600 fw-semibold me-2">' . $label . ':</span>'
                    . '<span class="badge badge-light-primary">' . $newVal . '</span>'
                    . '</div>';
            }
        }

        return '<div class="d-flex flex-column">' . $rows . '</div>';
    }

    /**
     * Normalise a single value for display (booleans, nulls, long strings).
     */
    protected function formatChangeValue(mixed $value): string
    {
        if (is_null($value) || $value === '') {
            return '<span class="fst-italic">empty</span>';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        $value = (string) $value;

        // Keep cells tidy — truncate very long values.
        return e(Str::limit($value, 60));
    }
}
