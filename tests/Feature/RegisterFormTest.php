<?php

namespace Tests\Feature;

use App\Filament\Participant\Pages\RegisterForm;
use App\Models\CategoryAllocation;
use App\Models\Participant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RegisterFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('register'));

        CategoryAllocation::query()->create([
            'categoryDescription' => 'OPEN CATEGORY',
            'allocated_participants' => 100,
        ]);
    }

    public function test_registration_form_displays_its_base_fields(): void
    {
        Livewire::test(RegisterForm::class)
            ->assertSee('Personal Information')
            ->assertSee('First name')
            ->assertSee('Last name')
            ->assertSee('Birth date')
            ->assertSee('Gender')
            ->assertSee('Shirt Size')
            ->assertSee('Address')
            ->assertSee('Contact number')
            ->assertSee('Category')
            ->assertSee('Description')
            ->assertSee('Distance category');
    }

    public function test_open_category_uses_its_main_allocation_without_a_subcategory(): void
    {
        CategoryAllocation::query()
            ->where('categoryDescription', 'OPEN CATEGORY')
            ->update(['allocated_participants' => 1]);

        $this->assertDatabaseCount('subcategories', 0);

        Livewire::test(RegisterForm::class)
            ->assertSet('categoryFull', false)
            ->set('data.firstName', 'Open')
            ->set('data.lastName', 'Runner')
            ->set('data.birthDate', '1990-01-01')
            ->set('data.gender', 'male')
            ->set('data.shirtSize', 'large')
            ->set('data.address', 'Test address')
            ->set('data.contactNumber', '09987654321')
            ->set('data.pwd', '0')
            ->set('data.rpwuds', '0')
            ->set('data.registrationType', 'individual')
            ->set('data.distanceCategory', '5km')
            ->set('data.waiver', true)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('participants', [
            'firstName' => 'Open',
            'lastName' => 'Runner',
            'categoryDescription' => 'OPEN CATEGORY',
        ]);

        Livewire::test(RegisterForm::class)
            ->assertSet('categoryFull', true);
    }

    public function test_registration_type_reveals_its_conditional_fields(): void
    {
        Livewire::test(RegisterForm::class)
            ->set('data.registrationType', 'organization')
            ->assertSee('Organization Name')
            ->set('data.registrationType', 'group')
            ->assertSee('Group Name')
            ->assertSee('Additional Participants')
            ->assertSee('Add Group Member');
    }

    public function test_group_members_can_be_added_and_updated(): void
    {
        $component = Livewire::test(RegisterForm::class)
            ->set('data.registrationType', 'group')
            ->callFormComponentAction('additionalParticipants', 'add');

        $itemKey = array_key_first($component->get('data.additionalParticipants'));

        $component
            ->set("data.additionalParticipants.{$itemKey}.firstName", 'Jane')
            ->set("data.additionalParticipants.{$itemKey}.lastName", 'Doe')
            ->set("data.additionalParticipants.{$itemKey}.birthDate", '2000-01-01')
            ->set("data.additionalParticipants.{$itemKey}.gender", 'female')
            ->set("data.additionalParticipants.{$itemKey}.shirtSize", 'medium')
            ->set("data.additionalParticipants.{$itemKey}.pwd", false)
            ->set("data.additionalParticipants.{$itemKey}.rpwuds", false)
            ->assertSet("data.additionalParticipants.{$itemKey}.firstName", 'Jane')
            ->set('data.registrationType', 'individual')
            ->assertDontSee('Additional Participants');
    }

    public function test_couple_registration_is_rejected_when_the_partner_already_exists(): void
    {
        Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => '1',
            'firstName' => 'Existing',
            'lastName' => 'Partner',
            'middleInitial' => null,
            'categoryDescription' => 'OPEN CATEGORY',
            'subDescription' => 'Individual',
            'shirtSize' => 'medium',
            'birthDate' => '1995-05-05',
            'gender' => 'female',
            'waiver' => true,
            'distanceCategory' => '5km',
            'referenceNumber' => '0',
            'address' => 'Existing address',
            'contactNumber' => '09123456789',
            'pwd' => false,
            'rpwuds' => false,
            'couple' => false,
        ]);

        $component = Livewire::test(RegisterForm::class)
            ->set('data.firstName', 'New')
            ->set('data.lastName', 'Registrant')
            ->set('data.birthDate', '1990-01-01')
            ->set('data.gender', 'male')
            ->set('data.shirtSize', 'large')
            ->set('data.address', 'New address')
            ->set('data.contactNumber', '09987654321')
            ->set('data.pwd', '0')
            ->set('data.rpwuds', '0')
            ->set('data.registrationType', 'couple')
            ->set('data.distanceCategory', '5km')
            ->set('data.waiver', true)
            ->callFormComponentAction('additionalParticipants', 'add');

        $itemKey = array_key_first($component->get('data.additionalParticipants'));
        $errorPath = "data.additionalParticipants.{$itemKey}.firstName";

        $component
            ->set("data.additionalParticipants.{$itemKey}.firstName", 'Existing')
            ->set("data.additionalParticipants.{$itemKey}.lastName", 'Partner')
            ->set("data.additionalParticipants.{$itemKey}.birthDate", '1995-05-05')
            ->set("data.additionalParticipants.{$itemKey}.gender", 'female')
            ->set("data.additionalParticipants.{$itemKey}.shirtSize", 'medium')
            ->set("data.additionalParticipants.{$itemKey}.pwd", '0')
            ->set("data.additionalParticipants.{$itemKey}.rpwuds", '0')
            ->call('create');

        $component
            ->assertHasErrors([$errorPath])
            ->assertSet(
                'errorMessage',
                'Existing Partner is already registered for this year.',
            );

        $this->assertDatabaseCount('participants', 1);
    }

    public function test_receipt_includes_additional_participant_names(): void
    {
        $primaryParticipant = Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => '1',
            'firstName' => 'Primary',
            'lastName' => 'Runner',
            'categoryDescription' => 'OPEN CATEGORY',
            'subDescription' => 'Couple',
            'shirtSize' => 'large',
            'birthDate' => '1990-01-01',
            'gender' => 'male',
            'waiver' => true,
            'distanceCategory' => '5km',
            'referenceNumber' => '0',
            'address' => 'Primary address',
            'contactNumber' => '09987654321',
            'couple' => true,
        ]);
        $primaryParticipant->update([
            'participantNumber' => (string) $primaryParticipant->getKey(),
        ]);

        Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => (string) $primaryParticipant->getKey(),
            'firstName' => 'Additional',
            'middleInitial' => 'A',
            'lastName' => 'Runner',
            'categoryDescription' => 'OPEN CATEGORY',
            'subDescription' => 'Couple',
            'shirtSize' => 'medium',
            'birthDate' => '1992-02-02',
            'gender' => 'female',
            'waiver' => true,
            'distanceCategory' => '5km',
            'referenceNumber' => '0',
            'address' => 'Primary address',
            'contactNumber' => '09987654321',
            'couple' => true,
        ]);

        Livewire::test(RegisterForm::class)
            ->set('data.firstName', 'Primary')
            ->set('data.lastName', 'Runner')
            ->set('data.birthDate', '1990-01-01')
            ->assertSet('participantExists', true)
            ->assertSee('Additional Participants')
            ->assertSeeHtml('1.&nbsp;')
            ->assertSee('Additional A. Runner')
            ->assertSee('Female')
            ->assertSee('MEDIUM');

        $pdfDocument = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfDocument->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
        $pdfDocument->shouldReceive('output')->once()->andReturn('%PDF receipt');

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data): bool {
                if ($view !== 'pdf.registration-receipt') {
                    return false;
                }

                $html = view($view, $data)->render();

                return $data['participant']->firstName === 'Primary'
                    && $data['additionalParticipants']->count() === 1
                    && str_contains($html, 'Additional Participants')
                    && str_contains($html, 'Additional A. Runner')
                    && str_contains($html, 'Gender')
                    && str_contains($html, 'Female')
                    && str_contains($html, 'Shirt Size')
                    && str_contains($html, 'MEDIUM');
            })
            ->andReturn($pdfDocument);

        $component = Livewire::test(RegisterForm::class);
        $component->instance()->receiptParticipantId = $primaryParticipant->getKey();

        $response = $component->instance()->downloadRegistrationReceipt();

        $this->assertNotNull($response);
    }

    public function test_non_open_category_duplicate_shows_creator_contact_without_receipt(): void
    {
        $creator = User::factory()->create([
            'name' => 'Maria Recorder',
            'username' => 'mariarecorder',
            'cellphone_number' => '09171234567',
        ]);

        $participant = Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => '44',
            'firstName' => 'Existing',
            'lastName' => 'Runner',
            'categoryDescription' => 'PLGU',
            'subDescription' => 'Provincial Office',
            'shirtSize' => 'large',
            'birthDate' => '1990-01-01',
            'gender' => 'male',
            'waiver' => true,
            'distanceCategory' => '5km',
            'referenceNumber' => '0',
            'address' => 'Existing address',
            'contactNumber' => '09000000000',
            'created_by' => $creator->username,
        ]);

        $component = Livewire::test(RegisterForm::class)
            ->set('data.firstName', 'Existing')
            ->set('data.lastName', 'Runner')
            ->set('data.birthDate', '1990-01-01')
            ->assertSet('participantExists', true)
            ->assertSet('receiptParticipantId', null)
            ->assertSet('existingParticipant.can_download_receipt', false)
            ->assertSet('existingParticipant.registered_by', 'Maria Recorder')
            ->assertSet('existingParticipant.registered_by_phone', '09171234567')
            ->assertSee('This participant was added by Maria Recorder.')
            ->assertSee('Please contact this person')
            ->assertSee('09171234567')
            ->assertDontSee('Download Receipt');

        $component->instance()->receiptParticipantId = $participant->getKey();

        $this->assertNull($component->instance()->downloadRegistrationReceipt());
    }
}
