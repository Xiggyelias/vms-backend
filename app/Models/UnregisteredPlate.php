<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnregisteredPlate extends Model
{
    use HasFactory;

    protected $table = 'unregistered_plates';
    public $timestamps = false;

    protected $fillable = [
        'plate_number',
        'detected_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
    ];
}
