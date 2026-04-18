<?php

namespace Tests\Feature;

use App\Models\BusinessHourSchedule;
use App\Models\BotSetting;
use App\Models\OofSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessHoursSettingTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role = 'owner')
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);

        return $this->actingAs($user);
    }

    public function test_business_hours_page_accessible(): void
    {
        $response = $this->actingAsRole()->get('/business-hours');

        $response->assertOk();
        $response->assertSee('Jadwal Operasional Mingguan');
    }

    public function test_owner_can_update_business_hours_and_oof(): void
    {
        BotSetting::setValue('business_hours_enabled', 'false');
        BotSetting::setValue('oof_enabled', 'false');

        $response = $this->actingAsRole('owner')->post('/business-hours', [
            'business_hours_enabled' => 'true',
            'oof_enabled' => 'true',
            'timezone' => 'Asia/Jakarta',
            'outside_business_hours_message' => 'Kami sedang tutup, nanti kami balas lagi ya.',
            'schedule' => [
                1 => ['enabled' => '1', 'start_time' => '09:00', 'end_time' => '17:00'],
                2 => ['enabled' => '1', 'start_time' => '09:00', 'end_time' => '17:00'],
                3 => ['enabled' => '1', 'start_time' => '09:00', 'end_time' => '17:00'],
                4 => ['enabled' => '1', 'start_time' => '09:00', 'end_time' => '17:00'],
                5 => ['enabled' => '1', 'start_time' => '09:00', 'end_time' => '16:00'],
                6 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '12:00'],
                7 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '12:00'],
            ],
            'oof_start_date' => now()->toDateString(),
            'oof_end_date' => now()->addDays(2)->toDateString(),
            'oof_message' => 'Tim sedang cuti bersama sampai akhir pekan.',
        ]);

        $response->assertRedirect('/business-hours');

        $this->assertDatabaseHas('bot_settings', [
            'key' => 'business_hours_enabled',
            'value' => 'true',
        ]);

        $this->assertDatabaseHas('bot_settings', [
            'key' => 'oof_enabled',
            'value' => 'true',
        ]);

        $this->assertDatabaseHas('business_hour_schedules', [
            'weekday' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('oof_schedules', [
            'message' => 'Tim sedang cuti bersama sampai akhir pekan.',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'settings.business_hours_updated',
        ]);
    }

    public function test_invalid_schedule_end_time_rejected(): void
    {
        $response = $this->actingAsRole('owner')->post('/business-hours', [
            'business_hours_enabled' => 'true',
            'oof_enabled' => 'false',
            'timezone' => 'Asia/Jakarta',
            'outside_business_hours_message' => 'Tutup dulu.',
            'schedule' => [
                1 => ['enabled' => '1', 'start_time' => '17:00', 'end_time' => '09:00'],
                2 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                3 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                4 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                5 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                6 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                7 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertSessionHasErrors('schedule.1.end_time');
        $this->assertDatabaseCount('business_hour_schedules', 0);
    }

    public function test_oof_enabled_without_schedule_payload_is_rejected_when_no_active_oof(): void
    {
        $response = $this->actingAsRole('owner')->post('/business-hours', [
            'business_hours_enabled' => 'true',
            'oof_enabled' => 'true',
            'timezone' => 'Asia/Jakarta',
            'outside_business_hours_message' => 'Tutup dulu.',
            'schedule' => [
                1 => ['enabled' => '1', 'start_time' => '09:00', 'end_time' => '17:00'],
                2 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                3 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                4 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                5 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                6 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                7 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertSessionHasErrors('oof_start_date');
        $this->assertDatabaseCount('oof_schedules', 0);
    }

    public function test_viewer_cannot_update_business_hours(): void
    {
        $response = $this->actingAsRole('viewer')->post('/business-hours', [
            'business_hours_enabled' => 'true',
            'oof_enabled' => 'false',
            'timezone' => 'Asia/Jakarta',
            'outside_business_hours_message' => 'Tutup dulu.',
            'schedule' => [
                1 => ['enabled' => '1', 'start_time' => '09:00', 'end_time' => '17:00'],
                2 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                3 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                4 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                5 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                6 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                7 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('business_hour_schedules', 0);
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'settings.business_hours_updated',
        ]);
    }

    public function test_oof_can_be_disabled_and_existing_active_entries_deactivated(): void
    {
        OofSchedule::query()->create([
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'message' => 'OoF sebelumnya',
            'is_active' => true,
        ]);

        $response = $this->actingAsRole('owner')->post('/business-hours', [
            'business_hours_enabled' => 'false',
            'oof_enabled' => 'false',
            'timezone' => 'Asia/Jakarta',
            'outside_business_hours_message' => 'Tutup dulu.',
            'schedule' => [
                1 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                2 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                3 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                4 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                5 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                6 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
                7 => ['enabled' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertRedirect('/business-hours');

        $activeCount = OofSchedule::query()->where('is_active', true)->count();
        $this->assertSame(0, $activeCount);
    }
}
