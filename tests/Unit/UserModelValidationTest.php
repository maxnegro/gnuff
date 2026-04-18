<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;

class UserModelValidationTest extends TestCase
{
    public function test_email_must_be_unique(): void
    {
        // Create a valid user
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        // Attempt to create another user with the same email
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => 'test@example.com']);
    }

    public function test_password_must_be_present(): void
    {
        // Attempt to create a user without a password
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        User::factory()->create(['password' => '']);
    }

    public function test_name_must_be_present(): void
    {
        // Attempt to create a user without a name
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        User::factory()->create(['name' => '']);
    }
}