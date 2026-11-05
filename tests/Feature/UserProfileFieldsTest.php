<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_and_generated_username_are_stored(): void
    {
        $user = User::query()->create([
            'name' => 'Juan Dela Cruz',
            'office' => 'Provincial Administrator Office',
            'cellphone_number' => '09123456789',
            'email' => 'juan@example.test',
            'password' => 'password',
        ]);

        $this->assertSame('Juan Dela Cruz', $user->name);
        $this->assertSame('Provincial Administrator Office', $user->office);
        $this->assertSame('09123456789', $user->cellphone_number);
        $this->assertSame('juandelacruz', $user->username);
        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'name' => 'Juan Dela Cruz',
            'office' => 'Provincial Administrator Office',
            'cellphone_number' => '09123456789',
            'username' => 'juandelacruz',
        ]);

        $user->update(['cellphone_number' => null]);

        $this->assertNull($user->fresh()->cellphone_number);
    }

    public function test_user_can_be_created_without_an_email_address(): void
    {
        $user = User::query()->create([
            'name' => 'No Email User',
            'office' => 'Administrative Office',
            'password' => 'password',
        ]);

        $this->assertNull($user->email);
        $this->assertSame('noemailuser', $user->username);
    }
}
