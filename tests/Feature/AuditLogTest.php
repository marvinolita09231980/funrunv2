<?php

namespace Tests\Feature;

use App\Filament\Pages\AuditLogs;
use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_records_actor_and_before_after_values(): void
    {
        $admin = $this->userWithRole('Administrator', 'admin-user', 'admin');
        $participant = $this->participant('Original', 'Runner');

        $this->actingAs($admin);

        $participant->update([
            'firstName' => 'Updated',
            'shirtSize' => 'xl',
        ]);

        $log = AuditLog::query()->sole();

        $this->assertSame('updated', $log->event);
        $this->assertSame($admin->getKey(), $log->actor_user_id);
        $this->assertSame('Administrator', $log->actor_name);
        $this->assertSame('admin-user', $log->actor_username);
        $this->assertSame(Participant::class, $log->auditable_type);
        $this->assertSame((string) $participant->getKey(), $log->auditable_id);
        $this->assertSame('Updated Runner', $log->subject_label);
        $this->assertSame([
            'firstName' => 'Original',
            'shirtSize' => 'medium',
        ], $log->old_values);
        $this->assertSame([
            'firstName' => 'Updated',
            'shirtSize' => 'xl',
        ], $log->new_values);
        $this->assertArrayNotHasKey('updated_at', $log->new_values);
    }

    public function test_delete_records_actor_and_deleted_record_values(): void
    {
        $admin = $this->userWithRole('Administrator', 'admin-user', 'admin');
        $participant = $this->participant('Deleted', 'Runner');
        $participantId = $participant->getKey();

        $this->actingAs($admin);
        $participant->delete();

        $log = AuditLog::query()->sole();

        $this->assertSame('deleted', $log->event);
        $this->assertSame('Deleted Runner', $log->subject_label);
        $this->assertSame((string) $participantId, $log->auditable_id);
        $this->assertSame('Deleted', $log->old_values['firstName']);
        $this->assertSame('Runner', $log->old_values['lastName']);
        $this->assertSame([], $log->new_values);
        $this->assertDatabaseMissing('participants', ['id' => $participantId]);
    }

    public function test_sensitive_values_are_redacted(): void
    {
        $admin = $this->userWithRole('Administrator', 'admin-user', 'admin');

        $this->actingAs($admin);
        $admin->update(['password' => 'a-new-password']);

        $log = AuditLog::query()->sole();

        $this->assertSame('[REDACTED]', $log->old_values['password']);
        $this->assertSame('[REDACTED]', $log->new_values['password']);
        $this->assertStringNotContainsString(
            'a-new-password',
            json_encode([$log->old_values, $log->new_values]),
        );
    }

    public function test_impersonated_edits_identify_the_original_actor_and_impersonated_user(): void
    {
        $admin = $this->userWithRole('Administrator', 'admin-user', 'admin');
        $focal = $this->userWithRole('Focal Person', 'focal-user', 'focal user');
        $participant = $this->participant('Impersonated', 'Runner');

        $this->actingAs($focal);
        session()->put(
            config('laravel-impersonate.session_key', 'impersonated_by'),
            $admin->getKey(),
        );

        $participant->update(['shirtSize' => 'large']);

        $log = AuditLog::query()->sole();

        $this->assertSame($admin->getKey(), $log->actor_user_id);
        $this->assertSame('Administrator', $log->actor_name);
        $this->assertSame($focal->getKey(), $log->impersonated_user_id);
        $this->assertSame('Focal Person', $log->impersonated_name);
        $this->assertSame('Administrator (acting as Focal Person)', $log->actor_display_name);
    }

    public function test_only_superadmin_can_open_audit_logs(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = $this->userWithRole('Administrator', 'admin-user', 'admin');
        $superadmin = $this->userWithRole('Super Administrator', 'superadmin-user', 'superadmin');
        $officer = $this->userWithRole('Officer', 'ao-user', 'administrative officer');

        $this->actingAs($admin);
        $this->assertFalse(AuditLogs::canAccess());
        Livewire::test(AuditLogs::class)->assertForbidden();

        $this->actingAs($superadmin);
        $this->assertTrue(AuditLogs::canAccess());
        Livewire::test(AuditLogs::class)
            ->assertOk()
            ->assertSee('Edit and Delete History');

        $this->actingAs($officer);
        $this->assertFalse(AuditLogs::canAccess());
        Livewire::test(AuditLogs::class)->assertForbidden();
    }

    private function userWithRole(string $name, string $username, string $role): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'username' => $username,
        ]);
        $user->assignRole(Role::query()->firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]));

        return $user;
    }

    private function participant(string $firstName, string $lastName): Participant
    {
        return Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => uniqid('audit-', true),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'middleInitial' => null,
            'categoryDescription' => 'OPEN CATEGORY',
            'subDescription' => 'Individual',
            'shirtSize' => 'medium',
            'birthDate' => '1990-01-01',
            'gender' => 'male',
            'waiver' => true,
            'distanceCategory' => '5km',
            'referenceNumber' => '0',
            'address' => 'Audit test address',
            'contactNumber' => '09123456789',
            'pwd' => false,
            'rpwuds' => false,
            'couple' => false,
            'created_by' => 'admin-user',
        ]);
    }
}
