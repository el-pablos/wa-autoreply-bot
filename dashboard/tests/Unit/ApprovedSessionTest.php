<?php

namespace Tests\Unit;

use App\Models\ApprovedSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovedSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_fillable_fields_are_configured(): void
    {
        $model = new ApprovedSession();

        $this->assertEquals([
            'phone_number',
            'approved_at',
            'last_activity_at',
            'expires_at',
            'is_active',
            'approved_by',
            'revoked_at',
        ], $model->getFillable());
    }

    public function test_model_casts_are_applied_correctly(): void
    {
        $session = ApprovedSession::create([
            'phone_number' => '628111000001',
            'approved_at' => now()->subMinutes(5),
            'last_activity_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
            'is_active' => 1,
            'approved_by' => 'admin',
            'revoked_at' => null,
        ])->fresh();

        $this->assertTrue($session->approved_at instanceof \Illuminate\Support\Carbon);
        $this->assertTrue($session->last_activity_at instanceof \Illuminate\Support\Carbon);
        $this->assertTrue($session->expires_at instanceof \Illuminate\Support\Carbon);
        $this->assertIsBool($session->is_active);
        $this->assertNull($session->revoked_at);
    }
}
