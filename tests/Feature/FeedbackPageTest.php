<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }
}
