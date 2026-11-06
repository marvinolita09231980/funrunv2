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
            ->assertSee('Share QR on Messenger')
            ->assertSee('Download QR')
            ->assertSee('https://www.messenger.com/')
            ->assertSee('http://localhost/feedback/feedback-qr-code.png')
            ->assertSee('http://localhost/feedback/share-feedback-form')
            ->assertSee('download="padac-fun-run-2026-feedback-qr.png"', false)
            ->assertSee('navigator.share')
            ->assertSee('url: shareButton.dataset.shareUrl', false)
            ->assertDontSee('new File(', false)
            ->assertDontSee('Please complete the PADAC Fun Run 2026 feedback form.')
            ->assertDontSee('fb-messenger://')
            ->assertDontSee('Share on Facebook')
            ->assertDontSee(':alignment="$alignment"', false)
            ->assertDontSee(':full-width="$isFullWidth"', false)
            ->assertDontSee('isSticky ? `width:', false);

        preg_match(
            '/<img[^>]+src="(data:image\/png;base64,[^"]+)"[^>]+alt="QR code for the PADAC Fun Run feedback form"/s',
            $response->getContent(),
            $matches,
        );

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertStringStartsWith('data:image/png;base64,', $matches[1]);
    }

    public function test_feedback_qr_code_can_be_downloaded_as_a_png(): void
    {
        $response = $this->get('/feedback/feedback-qr-code.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertDownload('padac-fun-run-2026-feedback-qr.png');

        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $response->getContent());

        $imageSize = getimagesizefromstring($response->getContent());

        $this->assertSame(900, $imageSize[0]);
        $this->assertSame(1120, $imageSize[1]);
    }

    public function test_clickable_share_page_uses_the_qr_as_its_social_preview(): void
    {
        $this->get('/feedback/share-feedback-form')
            ->assertOk()
            ->assertSee('<meta property="og:url" content="http://localhost/feedback/share-feedback-form" />', false)
            ->assertSee('<meta property="og:image" content="http://localhost/feedback/feedback-qr-code-preview.png" />', false)
            ->assertSee('<meta property="og:image:type" content="image/png" />', false)
            ->assertSee('window.location.replace', false)
            ->assertSee('http://localhost/feedback/feedback-form');

        $preview = $this->get('/feedback/feedback-qr-code-preview.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', 'inline; filename="padac-fun-run-2026-feedback-qr.png"');

        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $preview->getContent());
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
