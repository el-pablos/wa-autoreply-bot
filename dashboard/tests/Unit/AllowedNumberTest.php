<?php

namespace Tests\Unit;

use App\Models\AllowedNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowedNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_allowed_number(): void
    {
        $number = AllowedNumber::create([
            'phone_number' => '628111222333',
            'label'        => 'Test User',
            'is_active'    => true,
        ]);

        $this->assertDatabaseHas('allowed_numbers', [
            'phone_number' => '628111222333',
        ]);
        $this->assertTrue($number->is_active);
    }

    public function test_scope_active_returns_only_active_numbers(): void
    {
        AllowedNumber::create(['phone_number' => '628000000001', 'is_active' => true]);
        AllowedNumber::create(['phone_number' => '628000000002', 'is_active' => false]);

        $active = AllowedNumber::active()->get();
        $this->assertCount(1, $active);
        $this->assertEquals('628000000001', $active->first()->phone_number);
    }

    public function test_phone_number_must_be_unique(): void
    {
        AllowedNumber::create(['phone_number' => '628000000099', 'is_active' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        AllowedNumber::create(['phone_number' => '628000000099', 'is_active' => false]);
    }
}
