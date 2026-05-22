<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Applicant extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'applicants';
    protected $primaryKey = 'applicant_id';
    public $timestamps = true;

    protected $fillable = [
        'registrantType',
        'studentRegNo',
        'staffsRegNo',
        'fullName',
        'password',
        'phone',
        'email',
        'google_id',
        'avatar',
        'college',
        'idNumber',
        'licenseNumber',
        'licenseClass',
        'licenseDate',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'licenseDate' => 'date',
        'last_login' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => strtolower(trim((string) $value))
        );
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'applicant_id', 'applicant_id');
    }

    public function authorizedDrivers(): HasMany
    {
        return $this->hasMany(AuthorizedDriver::class, 'applicant_id', 'applicant_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'applicant_id');
    }

    public function searchLogs(): HasMany
    {
        return $this->hasMany(SearchLog::class, 'user_id', 'applicant_id');
    }

    public function registrationDraft(): HasOne
    {
        return $this->hasOne(RegistrationDraft::class, 'applicant_id', 'applicant_id');
    }

    public function isStudent(): bool
    {
        return $this->registrantType === 'student';
    }

    public function isStaff(): bool
    {
        return $this->registrantType === 'staff';
    }

    public function isGuest(): bool
    {
        return $this->registrantType === 'guest';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function canRegisterVehicles(): bool
    {
        return in_array($this->registrantType, ['student', 'staff']);
    }

    protected function maxVehicles(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->isStudent()) {
                    return config('app.max_vehicles_per_student', 1);
                }
                if ($this->isStaff()) {
                    return config('app.max_vehicles_per_staff', 5);
                }
                return 0;
            }
        );
    }

    /**
     * Get the active vehicle count for this applicant
     */
    public function getActiveVehicleCount(): int
    {
        return $this->vehicles()->where('status', 'active')->count();
    }

    /**
     * Get the total vehicle count for this applicant
     */
    public function getTotalVehicleCount(): int
    {
        return $this->vehicles()->count();
    }

    /**
     * Check if applicant can register more vehicles
     */
    public function canRegisterMoreVehicles(): bool
    {
        return $this->getTotalVehicleCount() < $this->max_vehicles;
    }
}
