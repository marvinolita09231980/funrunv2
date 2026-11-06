<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_displays_dynamic_feedback_qr_and_messenger_share_link(): void
    {
        config()->set('app.url', 'https://configured-host.example');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->get('/admin/login')
            ->assertOk()
            ->assertSee('ddo-login-card')
            ->assertSee('ddo-login-form-panel')
            ->assertSee('color-scheme: light;', false)
            ->assertSee("input[type='text']", false)
            ->assertSee('input:-webkit-autofill', false)
            ->assertSee('-webkit-text-fill-color: #1c1917 !important;', false)
            ->assertSee('@media (max-width: 767px)', false)
            ->assertSee('overflow-x: hidden;', false)
            ->assertSee('env(safe-area-inset-left)', false)
            ->assertSee('width: calc(100% - 1.5rem);', false)
            ->assertSee('Scan to Open the Feedback Form')
            ->assertSee('http://localhost/feedback/feedback-form')
            ->assertDontSee('configured-host.example')
            ->assertSee('Share on Messenger')
            ->assertSee('https://www.messenger.com/')
            ->assertSee('navigator.share')
            ->assertDontSee('fb-messenger://')
            ->assertDontSee('Share on Facebook')
            ->assertDontSee(':alignment="$alignment"', false)
            ->assertDontSee(':full-width="$isFullWidth"', false)
            ->assertDontSee('isSticky ? `width:', false);

        preg_match(
            '/<img[^>]+src="(data:image\/svg\+xml;base64,[^"]+)"[^>]+alt="QR code for the PADAC Fun Run feedback form"/s',
            $response->getContent(),
            $matches,
        );

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $matches[1]);
    }

    public function test_feedback_page_has_social_preview_metadata(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('feedback'));

        $this->get('/feedback/feedback-form')
            ->assertOk()
            ->assertSee('<meta property="og:type" content="website" />', false)
            ->assertSee('<meta property="og:title" content="PADAC Fun Run 2026 Feedback Form" />', false)
            ->assertSee(
                '<meta property="og:url" content="http://localhost/feedback/feedback-form" />',
                false,
            );
    }
}
