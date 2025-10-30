<?php

namespace App\Models;

use App\Traits\IsPerson;
use App\Traits\Searchable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use function request;
use function strlen;

class Patient extends Base implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;
    use HasFactory, Notifiable;
    use IsPerson, Searchable;

    const SEARCH_FIELDS = [
        'first_name',
        'last_name',
        'email',
        'dob',
        'id'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'prefix',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function getAgeAttribute() : array
    {
        if (!$this->dob) {
            return ['years'  => 0,
                    'months' => 0
            ];
        }

        $now = now();
        $years = $this->dob->diffInYears($now);
        $months = $this->dob->copy()
            ->addYears($years)
            ->diffInMonths($now);

        return [
            'years'  => intval($years),
            'months' => intval($months)
        ];
    }

    public function appointments() : HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
