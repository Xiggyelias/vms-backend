<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthorizedDriver extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'authorized_driver';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'fullname',
        'licenseNumber',
        'contact',
        'applicant_id',
    ];

    protected $casts = [
        // licenseNumber was migrated from INT to VARCHAR(50)
        'licenseNumber' => 'string',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id', 'applicant_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }
}
