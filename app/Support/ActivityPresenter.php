<?php

namespace App\Support;

use Illuminate\Support\Facades\Date;
use Spatie\Activitylog\Contracts\Activity;

/**
 * Shapes a raw activity-log record into a display array shared by the patient
 * History tab and the global Audit Log page.
 */
class ActivityPresenter
{
    /**
     * @return array{
     *     id: int,
     *     event: string|null,
     *     subject_type: string|null,
     *     subject_key: string|null,
     *     subject_id: int|string|null,
     *     causer_name: string|null,
     *     created_at: string|null,
     *     changes: list<array{field: string, old: mixed, new: mixed}>
     * }
     */
    public static function present(Activity $activity): array
    {
        $causer = $activity->causer;

        $attribute_changes = $activity->attribute_changes;
        $new = (array) data_get($attribute_changes, 'attributes', []);
        $old = (array) data_get($attribute_changes, 'old', []);

        $fields = array_keys($new + $old);

        return [
            'id' => $activity->id,
            'event' => $activity->event,
            'subject_type' => $activity->subject_type,
            'subject_key' => $activity->subject_type ? class_basename($activity->subject_type) : null,
            'subject_id' => $activity->subject_id,
            'causer_name' => $causer !== null
                ? trim(($causer->first_name ?? '').' '.($causer->last_name ?? '')) ?: null
                : null,
            'created_at' => $activity->created_at instanceof \DateTimeInterface
                ? Date::instance($activity->created_at)->toIso8601String()
                : $activity->created_at,
            'changes' => array_values(array_map(fn (string $field): array => [
                'field' => $field,
                'old' => $old[$field] ?? null,
                'new' => $new[$field] ?? null,
            ], $fields)),
        ];
    }
}
