<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
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

    private function calcBreakMinutes(): int
    {
        $breaks = $this->relationLoaded('breaks') ? $this->breaks : $this->breaks()->get();

        return $breaks->sum(function ($b) {
            if (! $b->break_start_time || ! $b->break_end_time) {
                return 0;
            }

            $s = Carbon::parse($b->break_start_time);
            $e = Carbon::parse($b->break_end_time);

            return $s->diffInMinutes($e);
        });
    }

    public function getTotalBreakTimeAttribute(): ?string
    {
        $minutes = $this->calcBreakMinutes();
        if ($minutes <= 0) {
            return null;
        }

        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function getTotalTimeAttribute(): ?string
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        $st = Carbon::parse($this->start_time);
        $en = Carbon::parse($this->end_time);

        $workMinutes = $st->diffInMinutes($en);
        $netMinutes = max(0, $workMinutes - $this->calcBreakMinutes());

        return sprintf('%d:%02d', intdiv($netMinutes, 60), $netMinutes % 60);
    }
}
