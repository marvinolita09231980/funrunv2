<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackPageTest extends TestCase
{
    use RefreshDatabase;

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
