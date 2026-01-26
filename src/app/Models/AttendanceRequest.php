<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable =
    [
        'user_id',
        'attendance_id',
        'break_id',
        'status',
        'payload',
        'reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
    'payload' => 'array',
    'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }
    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class, 'break_id');
    }
    public function reviewer()
    {
    return $this->belongsTo(User::class, 'reviewed_by');
    }
}
