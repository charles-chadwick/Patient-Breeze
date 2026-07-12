<?php

namespace App\Contracts;

/**
 * Implemented by auditable models that belong to a patient, so their activity-log
 * entries can be stamped with a denormalized patient_id for the patient History tab.
 */
interface LinksActivityToPatient
{
    /**
     * The id of the patient this record's activity should be attributed to,
     * or null when it is not patient-related.
     */
    public function auditPatientId(): ?int;
}
