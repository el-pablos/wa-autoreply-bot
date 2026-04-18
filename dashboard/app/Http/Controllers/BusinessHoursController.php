<?php

namespace App\Http\Controllers;

use App\Models\BotSetting;
use App\Models\BusinessHourSchedule;
use App\Models\OofSchedule;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BusinessHoursController extends Controller
{
    public function index()
    {
        $settings = BotSetting::query()
            ->whereIn('key', ['business_hours_enabled', 'oof_enabled', 'outside_business_hours_message'])
            ->pluck('value', 'key');

        $rows = BusinessHourSchedule::query()
            ->orderBy('weekday')
            ->get();

        $timezone = $rows->first()?->timezone ?: 'Asia/Jakarta';

        $defaultSchedule = [
            1 => ['enabled' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
            2 => ['enabled' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
            3 => ['enabled' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
            4 => ['enabled' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
            5 => ['enabled' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
            6 => ['enabled' => false, 'start_time' => '09:00', 'end_time' => '13:00'],
            7 => ['enabled' => false, 'start_time' => '09:00', 'end_time' => '13:00'],
        ];

        $schedule = $defaultSchedule;

        foreach ($rows as $row) {
            $schedule[(int) $row->weekday] = [
                'enabled' => (bool) $row->is_active,
                'start_time' => substr((string) $row->start_time, 0, 5),
                'end_time' => substr((string) $row->end_time, 0, 5),
            ];
        }

        $weekdayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $activeOof = OofSchedule::query()
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->first();

        return view('settings.business-hours', [
            'businessHoursEnabled' => ($settings['business_hours_enabled'] ?? 'false') === 'true',
            'oofEnabled' => ($settings['oof_enabled'] ?? 'false') === 'true',
            'outsideHoursMessage' => (string) ($settings['outside_business_hours_message'] ?? 'Saat ini kami di luar jam operasional. Pesan kamu sudah masuk dan akan kami respon saat jam kerja.'),
            'timezone' => $timezone,
            'schedule' => $schedule,
            'weekdayNames' => $weekdayNames,
            'activeOof' => $activeOof,
        ]);
    }

    public function update(Request $request)
    {
        $baseValidator = Validator::make($request->all(), [
            'business_hours_enabled' => 'nullable|in:true,false',
            'oof_enabled' => 'nullable|in:true,false',
            'timezone' => 'required|string|timezone',
            'outside_business_hours_message' => 'nullable|string|max:1000',
            'schedule' => 'required|array',
            'oof_start_date' => 'nullable|date',
            'oof_end_date' => 'nullable|date|after_or_equal:oof_start_date',
            'oof_message' => 'nullable|string|max:1000',
        ]);

        $baseValidator->after(function ($validator) use ($request) {
            $schedule = $request->input('schedule', []);
            for ($weekday = 1; $weekday <= 7; $weekday++) {
                $day = $schedule[$weekday] ?? [];
                $enabled = ((string) ($day['enabled'] ?? '0')) === '1';
                if (!$enabled) {
                    continue;
                }

                $start = (string) ($day['start_time'] ?? '');
                $end = (string) ($day['end_time'] ?? '');

                if (!preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/', $start)) {
                    $validator->errors()->add("schedule.$weekday.start_time", 'Format jam mulai harus HH:MM.');
                }
                if (!preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/', $end)) {
                    $validator->errors()->add("schedule.$weekday.end_time", 'Format jam selesai harus HH:MM.');
                }

                if ($this->looksLikeTime($start) && $this->looksLikeTime($end)) {
                    if ($this->toMinutes($end) <= $this->toMinutes($start)) {
                        $validator->errors()->add("schedule.$weekday.end_time", 'Jam selesai harus lebih besar dari jam mulai.');
                    }
                }
            }

            $oofEnabled = $request->boolean('oof_enabled');
            if ($oofEnabled) {
                $hasExistingActive = OofSchedule::query()->where('is_active', true)->exists();
                $hasNewPayload = $request->filled('oof_start_date')
                    || $request->filled('oof_end_date')
                    || $request->filled('oof_message');

                if (!$hasExistingActive && !$hasNewPayload) {
                    $validator->errors()->add('oof_start_date', 'Jika OoF diaktifkan, isi jadwal OoF minimal sekali.');
                }

                if ($hasNewPayload) {
                    if (!$request->filled('oof_start_date')) {
                        $validator->errors()->add('oof_start_date', 'Tanggal mulai OoF wajib diisi.');
                    }
                    if (!$request->filled('oof_end_date')) {
                        $validator->errors()->add('oof_end_date', 'Tanggal selesai OoF wajib diisi.');
                    }
                    if (!$request->filled('oof_message')) {
                        $validator->errors()->add('oof_message', 'Pesan OoF wajib diisi.');
                    }
                }
            }
        });

        $baseValidator->validate();

        $businessHoursEnabled = $request->boolean('business_hours_enabled');
        $oofEnabled = $request->boolean('oof_enabled');
        $timezone = (string) $request->input('timezone', 'Asia/Jakarta');
        $outsideHoursMessage = (string) $request->input(
            'outside_business_hours_message',
            'Saat ini kami di luar jam operasional. Pesan kamu sudah masuk dan akan kami respon saat jam kerja.'
        );

        $schedule = $request->input('schedule', []);
        $scheduleRows = [];
        for ($weekday = 1; $weekday <= 7; $weekday++) {
            $day = $schedule[$weekday] ?? [];
            $enabled = ((string) ($day['enabled'] ?? '0')) === '1';
            if (!$enabled) {
                continue;
            }

            $scheduleRows[] = [
                'weekday' => $weekday,
                'start_time' => ((string) $day['start_time']) . ':00',
                'end_time' => ((string) $day['end_time']) . ':00',
                'timezone' => $timezone,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $oldValues = [
            'business_hours_enabled' => BotSetting::getValue('business_hours_enabled', 'false'),
            'oof_enabled' => BotSetting::getValue('oof_enabled', 'false'),
            'outside_business_hours_message' => BotSetting::getValue('outside_business_hours_message'),
            'schedules' => BusinessHourSchedule::query()->orderBy('weekday')->get()->toArray(),
            'active_oof' => OofSchedule::query()->where('is_active', true)->orderByDesc('start_date')->get()->toArray(),
        ];

        DB::transaction(function () use ($businessHoursEnabled, $oofEnabled, $outsideHoursMessage, $scheduleRows, $request) {
            BotSetting::setValue('business_hours_enabled', $businessHoursEnabled ? 'true' : 'false');
            BotSetting::setValue('oof_enabled', $oofEnabled ? 'true' : 'false');
            BotSetting::setValue('outside_business_hours_message', $outsideHoursMessage);

            BusinessHourSchedule::query()->delete();
            if (!empty($scheduleRows)) {
                DB::table('business_hour_schedules')->insert($scheduleRows);
            }

            if (!$oofEnabled) {
                OofSchedule::query()->where('is_active', true)->update(['is_active' => false]);
                return;
            }

            $hasNewOofPayload = $request->filled('oof_start_date')
                || $request->filled('oof_end_date')
                || $request->filled('oof_message');

            if ($hasNewOofPayload) {
                OofSchedule::query()->where('is_active', true)->update(['is_active' => false]);

                OofSchedule::query()->create([
                    'start_date' => (string) $request->input('oof_start_date'),
                    'end_date' => (string) $request->input('oof_end_date'),
                    'message' => (string) $request->input('oof_message'),
                    'is_active' => true,
                ]);
            }
        });

        $newValues = [
            'business_hours_enabled' => $businessHoursEnabled ? 'true' : 'false',
            'oof_enabled' => $oofEnabled ? 'true' : 'false',
            'outside_business_hours_message' => $outsideHoursMessage,
            'schedules' => $scheduleRows,
            'active_oof' => OofSchedule::query()->where('is_active', true)->orderByDesc('start_date')->get()->toArray(),
        ];

        AuditTrail::record(
            $request,
            'settings.business_hours_updated',
            ['type' => 'settings.business-hours', 'id' => null],
            $oldValues,
            $newValues
        );

        return redirect()->route('business-hours.index')->with('success', 'Business hours dan jadwal OoF berhasil diperbarui.');
    }

    private function looksLikeTime(string $time): bool
    {
        return preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/', $time) === 1;
    }

    private function toMinutes(string $time): int
    {
        [$hour, $minute] = explode(':', $time);
        return ((int) $hour * 60) + (int) $minute;
    }
}
