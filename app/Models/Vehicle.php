<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicles';
    protected $primaryKey = 'vehicle_id';
    public $timestamps = false;

    protected $fillable = [
        'applicant_id',
        'regNumber',
        'make',
        'model',
        'owner',
        'address',
        'PlateNumber',
        'registration_date',
        'registration_expiry',
        'last_renewed_at',
        'disk_number',
        'last_updated',
        'status',
    ];

    protected $casts = [
        'registration_date'   => 'datetime',
        'registration_expiry' => 'date',
        'last_renewed_at'     => 'date',
        'last_updated'        => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'applicant_id', 'applicant_id');
    }

    public function authorizedDrivers(): HasMany
    {
        return $this->hasMany(AuthorizedDriver::class, 'vehicle_id', 'vehicle_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForApplicant($query, int $applicantId)
    {
        return $query->where('applicant_id', $applicantId);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('registration_expiry')
                     ->where('registration_expiry', '<=', now()->addDays($days))
                     ->where('registration_expiry', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('registration_expiry')
                     ->where('registration_expiry', '<', now());
    }

    public function isExpired(): bool
    {
        return $this->registration_expiry && $this->registration_expiry->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->registration_expiry
            && !$this->isExpired()
            && $this->registration_expiry->diffInDays(now()) <= $days;
    }

    public function daysUntilExpiry(): ?int
    {
        if (!$this->registration_expiry) {
            return null;
        }
        return (int) now()->diffInDays($this->registration_expiry, false);
    }

    public function renewalStatus(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }
        if ($this->isExpiringSoon(14)) {
            return 'critical';   // ≤ 14 days
        }
        if ($this->isExpiringSoon(30)) {
            return 'warning';    // ≤ 30 days
        }
        return 'valid';
    }

    /**
     * Get the formatted registration date
     */
    protected function formattedRegistrationDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->registration_date?->format('M d, Y h:i A')
        );
    }

    /**
     * Get the formatted last updated date
     */
    protected function formattedLastUpdated(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_updated?->format('M d, Y h:i A')
        );
    }

    /**
     * Check if vehicle belongs to specific applicant
     */
    public function belongsToApplicant(int $applicantId): bool
    {
        return $this->applicant_id === $applicantId;
    }

    /**
     * Get authorized driver count
     */
    public function getAuthorizedDriverCount(): int
    {
        return $this->authorizedDrivers()->count();
    }
}
