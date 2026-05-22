<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    use HasFactory;

    protected $table = 'search_logs';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'search_type',
        'search_term',
        'search_date',
    ];

    protected $casts = [
        'search_date' => 'datetime',
    ];

    public function scopeByType($query, $type)
    {
        return $query->where('search_type', $type);
    }
}
