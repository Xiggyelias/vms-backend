<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationDraft extends Model
{
    use HasFactory;

    protected $table = 'registration_drafts';

    protected $fillable = [
        'applicant_id',
        'draft_data',
    ];

    protected $casts = [
        'draft_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id', 'applicant_id');
    }
}
