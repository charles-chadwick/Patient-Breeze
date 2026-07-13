<?php

namespace App\Console\Commands;

use App\Support\ResolvesActivityPatient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

#[Signature('audit:backfill-patient-id')]
#[Description('Backfill activity_log.patient_id for existing rows using the per-model resolver.')]
class BackfillActivityPatientId extends Command
{
    public function handle(ResolvesActivityPatient $resolver): int
    {
        $activityModel = Config::get('activitylog.activity_model');

        $updated = 0;

        $activityModel::query()
            ->whereNull('patient_id')
            ->whereNotNull('subject_type')
            ->whereNotNull('subject_id')
            ->chunkById(500, function ($activities) use ($resolver, &$updated): void {
                foreach ($activities as $activity) {
                    $patient_id = $resolver->resolve($activity->subject_type, $activity->subject_id);

                    if ($patient_id !== null) {
                        $activity->updateQuietly(['patient_id' => $patient_id]);
                        $updated++;
                    }
                }
            });

        $this->info("Backfilled patient_id on {$updated} activity records.");

        return self::SUCCESS;
    }
}
