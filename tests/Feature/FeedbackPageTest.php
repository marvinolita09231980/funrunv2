<?php

namespace Tests\Feature;

use App\Filament\Feedback\Pages\FeedbackForm;
use App\Models\Feedback;
use App\Models\Participant;
use App\Services\FinisherCertificatePdf;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class FeedbackPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_date_field_allows_typing_with_a_separate_calendar_button(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('feedback'));

        $response = $this->get('/feedback/feedback-form')->assertOk();
        preg_match('/<input\s+type="text"\s+x-ref="typedDate".*?>/s', $response->getContent(), $matches);

        $this->assertNotEmpty($matches);
        $this->assertStringContainsString('placeholder="mm/dd/yyyy"', $matches[0]);
        $this->assertStringContainsString('x-mask="99/99/9999"', $matches[0]);
        $this->assertStringContainsString('inputmode="numeric"', $matches[0]);
        $this->assertStringNotContainsString('readonly', $matches[0]);
        $this->assertStringContainsString('x-on:change=', $matches[0]);
        $response->assertSee('aria-label="Open calendar"', false);
    }

    public function test_feedback_page_uses_the_padac_2026_browser_title(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('feedback'));

        $response = $this->get('/feedback/feedback-form')
            ->assertOk();

        preg_match('/<title>(.*?)<\/title>/s', $response->getContent(), $matches);

        $browserTitle = preg_replace(
            '/\s+/',
            ' ',
            trim((string) ($matches[1] ?? '')),
        );

        $this->assertSame(
            'Feedback Form - PADAC Furn Run 2026',
            $browserTitle,
        );

        $response->assertSee('Already submitted?');
        $response->assertSee('Download certificate');
        $response->assertSee('flex-wrap: nowrap', false);
        $response->assertSee('flex: 0 0 1rem', false);
    }

    public function test_feedback_requires_a_participant_registered_in_the_current_year(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('feedback'));
        $participant = $this->participant(['year' => $this->currentYear() - 1]);

        $component = $this->feedbackComponent($participant);
        $this->completeFeedback($component)
            ->call('create')
            ->assertHasErrors(['data.firstName'])
            ->assertSet('data.feedback_exists', false);

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_feedback_is_recorded_for_the_current_year(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('feedback'));
        $participant = $this->participant();

        $this->completeFeedback($this->feedbackComponent($participant))
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('data.feedback_exists', true);

        $this->assertDatabaseHas('feedback', [
            'year' => $this->currentYear(),
            'participantNumber' => $participant->participantNumber,
            'firstName' => $participant->firstName,
            'lastName' => $participant->lastName,
            'birthDate' => '1990-01-01',
        ]);
    }

    public function test_existing_current_year_feedback_is_not_submitted_or_overwritten_again(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('feedback'));
        $participant = $this->participant();
        $this->feedback($participant, [
            'recommendation' => 'Keep the original response.',
        ]);

        $component = $this->feedbackComponent($participant)
            ->assertSet('data.feedback_exists', true);

        $component
            ->call('create')
            ->assertHasErrors(['data.firstName'])
            ->assertSet('data.feedback_exists', true);

        $this->assertDatabaseCount('feedback', 1);
        $this->assertDatabaseHas('feedback', [
            'year' => $this->currentYear(),
            'recommendation' => 'Keep the original response.',
        ]);
    }

    public function test_previous_year_feedback_does_not_block_current_year_feedback(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('feedback'));
        $participant = $this->participant();
        $this->feedback($participant, ['year' => $this->currentYear() - 1]);

        $component = $this->feedbackComponent($participant)
            ->assertSet('data.feedback_exists', false);

        $this->completeFeedback($component)
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('data.feedback_exists', true);

        $this->assertDatabaseCount('feedback', 2);
        $this->assertDatabaseHas('feedback', [
            'year' => $this->currentYear(),
            'firstName' => $participant->firstName,
            'lastName' => $participant->lastName,
        ]);
    }

    public function test_certificate_uses_the_validated_current_year_participants_distance(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('feedback'));
        $participant = $this->participant([
            'middleInitial' => 'Q',
            'distanceCategory' => '10km',
        ]);
        $this->feedback($participant);

        $component = $this->feedbackComponent($participant)
            ->assertSet('data.feedback_exists', true)
            ->set('data.distanceCategory', '5km')
            ->assertSee('Download Certificate')
            ->assertDontSee('Preview Certificate');

        foreach (['3km', '5km', '10km'] as $storedDistance) {
            $participant->update(['distanceCategory' => $storedDistance]);

            $certificateUrl = $component->instance()->certificateUrl();
            $certificatePreviewUrl = $component->instance()->certificatePreviewUrl();

            $this->assertSame(
                '/feedback/certificates/'.$participant->getKey(),
                parse_url((string) $certificateUrl, PHP_URL_PATH),
            );
            $this->get((string) $certificateUrl)
                ->assertOk()
                ->assertHeader('Content-Type', 'application/pdf')
                ->assertDownload(
                    'feedback-q-runner-finisher-certificate-'.$this->currentYear().'.pdf',
                );
            $this->assertSame(
                '/feedback/certificates/'.$participant->getKey().'/preview',
                parse_url((string) $certificatePreviewUrl, PHP_URL_PATH),
            );
            $this->get((string) $certificatePreviewUrl)
                ->assertOk()
                ->assertSee('Certificate Preview')
                ->assertSee('Download PDF')
                ->assertSee('Finisher certificate preview');

            $inlineUrl = URL::temporarySignedRoute(
                'feedback.certificate.inline',
                now()->addMinutes(30),
                ['participant' => $participant->getKey()],
            );
            $this->get($inlineUrl)
                ->assertOk()
                ->assertHeader(
                    'Content-Disposition',
                    'inline; filename="feedback-q-runner-finisher-certificate-'.$this->currentYear().'.pdf"',
                );
        }
    }

    public function test_certificate_uses_the_requested_distance_labels(): void
    {
        $this->assertSame('Rave Run (3 km)', FinisherCertificatePdf::distanceLabel('3km'));
        $this->assertSame('5 km', FinisherCertificatePdf::distanceLabel('5km'));
        $this->assertSame('10 km', FinisherCertificatePdf::distanceLabel('10km'));
    }

    public function test_existing_feedback_can_be_validated_separately_for_certificate_download(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('feedback'));
        $participant = $this->participant(['distanceCategory' => '5km']);
        $this->feedback($participant);

        $component = Livewire::test(FeedbackForm::class)
            ->set('certificateLookup.firstName', $participant->firstName)
            ->set('certificateLookup.lastName', $participant->lastName)
            ->set('certificateLookup.birthDate', '1990-01-01')
            ->call('findCertificate')
            ->assertHasNoErrors()
            ->assertSet('certificateParticipantId', $participant->getKey());

        $certificateUrl = $component->instance()->certificateLookupUrl();

        $this->assertSame(
            '/feedback/certificates/'.$participant->getKey(),
            parse_url((string) $certificateUrl, PHP_URL_PATH),
        );
        $this->get((string) $certificateUrl)
            ->assertOk()
            ->assertDownload(
                'feedback-runner-finisher-certificate-'.$this->currentYear().'.pdf',
            );
    }

    public function test_certificate_download_requires_a_valid_signature_and_completed_feedback(): void
    {
        $participant = $this->participant();

        $this->get(route('feedback.certificate.download', $participant))
            ->assertForbidden();
        $this->get(route('feedback.certificate.preview', $participant))
            ->assertForbidden();
        $this->get(route('feedback.certificate.inline', $participant))
            ->assertForbidden();

        $signedUrl = URL::temporarySignedRoute(
            'feedback.certificate.download',
            now()->addMinutes(30),
            ['participant' => $participant->getKey()],
        );

        $this->get($signedUrl)->assertNotFound();
    }

    public function test_certificate_lookup_rejects_feedback_from_a_previous_year(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('feedback'));
        $participant = $this->participant();
        $this->feedback($participant, ['year' => $this->currentYear() - 1]);

        Livewire::test(FeedbackForm::class)
            ->set('certificateLookup.firstName', $participant->firstName)
            ->set('certificateLookup.lastName', $participant->lastName)
            ->set('certificateLookup.birthDate', '1990-01-01')
            ->call('findCertificate')
            ->assertHasErrors(['certificateLookup.feedback'])
            ->assertSet('certificateParticipantId', null);
    }

    private function feedbackComponent(Participant $participant)
    {
        return Livewire::test(FeedbackForm::class)
            ->set('data.firstName', $participant->firstName)
            ->set('data.lastName', $participant->lastName)
            ->set('data.birthDate', '1990-01-01');
    }

    private function completeFeedback($component)
    {
        return $component
            ->set('data.rate', 'Very Satisfied')
            ->set('data.aware_of_funrun', 'Social Media')
            ->set('data.inspired', 'For exercise/ fitness')
            ->set('data.raise_awareness', 'No')
            ->set('data.encouraged_healthy_lifestyle', 'Yes')
            ->set('data.which_part_enjoy', 'The run')
            ->set('data.recommendation', 'Keep it organized');
    }

    private function participant(array $attributes = []): Participant
    {
        return Participant::query()->create(array_merge([
            'year' => $this->currentYear(),
            'participantNumber' => '1001',
            'firstName' => 'Feedback',
            'lastName' => 'Runner',
            'middleInitial' => null,
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
            'created_by' => 'opencategory',
        ], $attributes));
    }

    private function feedback(Participant $participant, array $attributes = []): Feedback
    {
        return Feedback::query()->create(array_merge([
            'year' => $this->currentYear(),
            'participantNumber' => $participant->participantNumber,
            'full_name' => 'Feedback Runner',
            'firstName' => $participant->firstName,
            'lastName' => $participant->lastName,
            'birthDate' => '1990-01-01',
            'rate' => 'Satisfied',
            'aware_of_funrun' => 'Social Media',
            'inspired' => 'For exercise/ fitness',
            'raise_awareness' => 'No',
            'encouraged_healthy_lifestyle' => 'Yes',
            'which_part_enjoy' => 'The run',
            'recommendation' => 'Original response',
        ], $attributes));
    }

    private function currentYear(): int
    {
        return (int) now('Asia/Manila')->year;
    }
}
