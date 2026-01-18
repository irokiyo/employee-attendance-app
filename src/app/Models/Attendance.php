<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BreakTime;
use App\Models\User;
use App\Models\Request;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'date',
        'start_time',
        'end_time',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function request()
    {
        return $this->hasMany(Request::class);
    }
    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }
}
