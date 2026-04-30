<?php

namespace App\Models;

use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Patient extends Model
{
    use HasFactory, LogsActivity, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'user_id',
        'mrn',
        'date_of_birth',
        'gender_at_birth',
        'gender_identity',
        'blood_type',
    ];

    protected function searchableFields(): array
    {
        return [
            'mrn',
            'date_of_birth',
            'blood_type',
            'user.first_name',
            'user.last_name',
            'user.email',
        ];
    }

    protected function sortableFields(): array
    {
        return [
            'last_name' => 'user.last_name',
            'first_name' => 'user.first_name',
            'date_of_birth' => 'date_of_birth',
            'blood_type' => 'blood_type',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public static function generateMrn(): string
    {
        $max = static::withTrashed()->lockForUpdate()->max('mrn');
        $number = $max ? ((int) substr($max, 4)) + 1 : 1;

        return 'MRN-'.str_pad((string) $number, 7, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender_at_birth' => GenderAtBirth::class,
            'gender_identity' => GenderIdentity::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
