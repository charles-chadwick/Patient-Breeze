<?php

namespace App\Providers;

use App\Support\ResolvesActivityPatient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        $this->stampActivityPatientId();
    }

    /**
     * Denormalize a patient_id onto every activity-log entry so a patient's
     * "wide" History can be queried with a single indexed lookup.
     */
    private function stampActivityPatientId(): void
    {
        $activityModel = config('activitylog.activity_model');

        $activityModel::creating(function (ActivityContract $activity): void {
            $activity->patient_id = app(ResolvesActivityPatient::class)
                ->resolve($activity->subject_type, $activity->subject_id);
        });
    }
}
