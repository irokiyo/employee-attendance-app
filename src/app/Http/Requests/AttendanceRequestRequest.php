<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reason' => ['required'],
            'attendance_id' => ['required', 'exists:attendances,id'],
            'break_id' => ['nullable', 'exists:breaks,id'],
            'start_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/', 'date_format:H:i'],
            'end_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/', 'date_format:H:i'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.break_id' => ['nullable', 'exists:breaks,id'],
            'breaks.*.break_start_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/', 'date_format:H:i'],
            'breaks.*.break_end_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/', 'date_format:H:i'],
        ];
    }

    public function messages()
    {
        return [
            'reason.required' => '備考を記入してください',
            'start_time.regex' => '時間は00:00形式で入力してください',
            'end_time.regex' => '時間は00:00形式で入力してください',
            'breaks.*.break_start_time.regex' => '時間は00:00形式で入力してください',
            'breaks.*.break_end_time.regex' => '時間は00:00形式で入力してください',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $startT = $this->safeParseTime($this->input('start_time'));
            $endT = $this->safeParseTime($this->input('end_time'));

            if ($startT && $endT && $startT->gte($endT)) {
                $validator->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');

                return;
            }

            if (! $startT || ! $endT) {
                return;
            }

            $breaks = $this->input('breaks', []);
            foreach ($breaks as $i => $b) {
                $bs = $b['break_start_time'] ?? null;
                $be = $b['break_end_time'] ?? null;

                if (empty($bs) && empty($be)) {
                    continue;
                }

                if (empty($bs) || empty($be)) {
                    $validator->errors()->add("breaks.$i.break_start_time", '休憩時間を正しく入力してください');
                    $validator->errors()->add("breaks.$i.break_end_time", '休憩時間を正しく入力してください');

                    continue;
                }

                $bsT = $this->safeParseTime($bs);
                $beT = $this->safeParseTime($be);

                if (! $bsT || ! $beT) {
                    continue;
                }

                if ($bsT->gte($beT)) {
                    $validator->errors()->add("breaks.$i.break_start_time", '休憩時間が不適切な値です');

                    continue;
                }

                if ($bsT->lt($startT) || $beT->gt($endT)) {
                    $validator->errors()->add("breaks.$i.break_start_time", '休憩時間が勤務時間外です');
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $toHalf = function ($v) {
            if ($v === null) {
                return null;
            }

            $v = mb_convert_kana($v, 'nsa', 'UTF-8');

            $v = str_replace('：', ':', $v);

            return trim($v);
        };

        $breaks = $this->input('breaks', []);
        foreach ($breaks as $i => $b) {
            if (array_key_exists('break_start_time', $b)) {
                $breaks[$i]['break_start_time'] = $toHalf($b['break_start_time']);
            }
            if (array_key_exists('break_end_time', $b)) {
                $breaks[$i]['break_end_time'] = $toHalf($b['break_end_time']);
            }
        }

        $this->merge([
            'start_time' => $toHalf($this->input('start_time')),
            'end_time' => $toHalf($this->input('end_time')),
            'breaks' => $breaks,
        ]);
    }

    private function safeParseTime(?string $t): ?Carbon
    {
        if (empty($t)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i', $t);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
