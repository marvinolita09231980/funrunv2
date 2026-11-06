<?php

namespace Tests\Feature;

use App\Filament\Resources\Participants\Tables\ParticipantsTable;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipantVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_can_see_participants_created_by_other_users(): void
    {
        $admin = $this->createUser('admin-user');
        $admin->assignRole(Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]));

        $this->createParticipant('First', 'Runner', 'focal-one');
        $this->createParticipant('Second', 'Runner', 'focal-two');

        $participants = ParticipantsTable::applyVisibilityForUser(
            Participant::query(),
            $admin,
        )->get();

        $this->assertCount(2, $participants);
    }

    public function test_non_admin_role_remains_limited_to_its_own_participants(): void
    {
        $encoder = $this->createUser('encoder-one');
        $encoder->assignRole(Role::query()->firstOrCreate([
            'name' => 'encoder',
            'guard_name' => 'web',
        ]));

        $this->createParticipant('Own', 'Runner', 'encoder-one');
        $this->createParticipant('Other', 'Runner', 'encoder-two');

        $participants = ParticipantsTable::applyVisibilityForUser(
            Participant::query(),
            $encoder,
        )->get();

        $this->assertCount(1, $participants);
        $this->assertSame('Own', $participants->first()->firstName);
    }

    public function test_participant_creator_name_resolves_from_the_username(): void
    {
        User::query()->create([
            'name' => 'Maria Santos',
            'username' => 'msantos',
            'email' => 'msantos@example.test',
            'password' => 'password',
        ]);

        $participant = $this->createParticipant('Test', 'Runner', 'msantos');

        $this->assertSame('Maria Santos', $participant->fresh()->creator_name);
    }

    public function test_open_category_self_registration_identifies_the_registrant_as_creator(): void
    {
        $participant = $this->createParticipant(
            'Self',
            'Registered',
            'opencategory',
        );

        $this->assertTrue($participant->fresh()->isSelfRegistered());
        $this->assertSame(
            'Registrant (Self-Registered)',
            $participant->fresh()->creator_name,
        );
    }

    public function test_three_kilometer_distance_uses_the_rave_run_label(): void
    {
        $this->assertSame('Rave Run (3K)', Participant::distanceLabel('3km'));
        $this->assertSame('Rave Run (3K)', Participant::distanceLabel(' 3K '));
        $this->assertSame('5KM', Participant::distanceLabel('5km'));
    }

    private function createUser(string $username): User
    {
        return User::query()->create([
            'name' => $username,
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => 'password',
        ]);
    }

    private function createParticipant(
        string $firstName,
        string $lastName,
        string $createdBy,
    ): Participant {
        return Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => uniqid('participant-', true),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'categoryDescription' => 'OPEN CATEGORY',
            'subDescription' => 'Individual',
            'shirtSize' => 'medium',
            'birthDate' => '1990-01-01',
            'gender' => 'male',
            'waiver' => true,
            'distanceCategory' => '5km',
            'referenceNumber' => '0',
            'address' => 'Test address',
            'contactNumber' => '09123456789',
            'created_by' => $createdBy,
        ]);
    }
}
