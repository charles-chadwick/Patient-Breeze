<?php
/** @noinspection PhpUnused */

namespace App\Models;

use App\Enums\AppointmentStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Appointment extends Base
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'type',
        'start',
        'end',
        'status',
        'title',
        'description',
    ];

    public function users() : BelongsToMany
    {
        return $this->belongsToMany(User::class, 'appointment_users', 'appointment_id', 'user_id');
    }

    protected function casts() : array
    {
        return [
            'start'  => 'datetime',
            'end'    => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    public function patient() : BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Check if there are any conflicting appointments for the given time period and users
     *
     * @param  DateTime|string  $start  Start datetime of the appointment
     * @param  DateTime|string  $end  End datetime of the appointment
     * @param  array<int>  $user_ids  Array of user IDs to check conflicts for
     * @param  int|null  $exclude_id  Optional appointment ID to exclude from the check
     * @return bool Returns true if conflicts exist, false otherwise
     */
    public function hasConflicts(DateTime|string $start, DateTime|string $end, array $user_ids, ?int $exclude_id = null) : bool
    {
        $query = self::query()
            ->whereHas('users', function ($query) use ($user_ids) {
                $query->whereIn('users.id', $user_ids);
            })
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start', [
                    $start,
                    $end
                ])
                    ->orWhereBetween('end', [
                        $start,
                        $end
                    ])
                    ->orWhere(function ($query) use ($start, $end) {
                        $query->where('start', '<=', $start)
                            ->where('end', '>=', $end);
                    });
            });

        if ($exclude_id) {
            $query->where('id', '!=', $exclude_id);
        }

        return $query->exists();
    }

}
