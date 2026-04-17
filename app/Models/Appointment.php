<?php /** @noinspection PhpIncompatibleReturnTypeInspection */

namespace App\Models;

use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'reason',
        'notes',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function primaryProvider(): ?User
    {
        return $this->users()
            ->wherePivot('role', AppointmentRole::Primary->value)
            ->first();
    }

    public function attachProvider(User $user, AppointmentRole $role = AppointmentRole::Primary): void
    {
        if ($user->isPatient()) {
            throw new DomainException('Cannot assign a patient as an appointment provider.');
        }

        $this->users()->syncWithoutDetaching([
            $user->id => ['role' => $role->value],
        ]);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AppointmentStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
