<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'admin_reports';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'description',
        'category',
        'report_date',
        'file_path',
        'admin_id',
    ];

    protected $casts = [
        'report_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function scopeOfCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
