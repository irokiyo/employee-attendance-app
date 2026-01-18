<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;
use App\Models\Request;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    protected $fillable =
    [
        'attendance_id',
        'break_start_time',
        'break_end_time',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }
    public function request()
    {
        return $this->hasMany(Request::class);
    }
}
