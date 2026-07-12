<?php

namespace App\Support;

use App\Contracts\LinksActivityToPatient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Resolves the patient a given activity-log subject belongs to, by loading the
 * subject model and delegating to its auditPatientId(). Shared by the
 * Activity::creating listener and the backfill command.
 */
class ResolvesActivityPatient
{
    /**
     * @param  class-string|null  $subjectType
     */
    public function resolve(?string $subjectType, int|string|null $subjectId): ?int
    {
        if ($subjectType === null || $subjectId === null || ! class_exists($subjectType)) {
            return null;
        }

        if (! is_subclass_of($subjectType, LinksActivityToPatient::class)) {
            return null;
        }

        $subject = $this->findSubject($subjectType, $subjectId);

        return $subject instanceof LinksActivityToPatient ? $subject->auditPatientId() : null;
    }

    /**
     * @param  class-string  $subjectType
     */
    private function findSubject(string $subjectType, int|string $subjectId): ?Model
    {
        $query = $subjectType::query();

        // Deleted events log after the subject is soft-deleted, so include trashed rows.
        if (in_array(SoftDeletes::class, class_uses_recursive($subjectType), true)) {
            $query->withTrashed();
        }

        return $query->find($subjectId);
    }
}
