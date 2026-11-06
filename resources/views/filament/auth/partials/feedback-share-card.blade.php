@php
    use App\Support\FeedbackShareLink;

    $feedbackUrl = FeedbackShareLink::url();
    $messengerShareUrl = FeedbackShareLink::messengerShareUrl();
    $qrCodeDataUri = FeedbackShareLink::qrCodeDataUri();
@endphp

<style>
    .feedback-share-card {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        width: min(72rem, calc(100% - 2rem));
        margin: 1.25rem auto 2rem;
        overflow: hidden;
        border: 1px solid rgba(217, 119, 6, 0.2);
        border-radius: 1.25rem;
        background: #ffffff;
        box-shadow: 0 14px 38px rgba(69, 43, 2, 0.12);
    }

    .feedback-share-qr-wrap {
        display: grid;
        place-items: center;
        padding: 1.25rem;
        background: linear-gradient(145deg, #fff7d6, #fef3c7);
    }

    .feedback-share-qr {
        width: 9.5rem;
        height: 9.5rem;
        padding: 0.55rem;
        border-radius: 0.9rem;
        background: #ffffff;
        box-shadow: 0 6px 20px rgba(69, 43, 2, 0.12);
    }

    .feedback-share-content {
        min-width: 0;
        padding: 1.5rem;
    }

    .feedback-share-eyebrow {
        margin: 0;
        color: #b45309;
        font-size: 0.7rem;
        font-weight: 750;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .feedback-share-heading {
        margin: 0.45rem 0 0;
        color: #292524;
        font-size: 1.25rem;
        font-weight: 750;
        line-height: 1.25;
    }

    .feedback-share-copy {
        max-width: 42rem;
        margin: 0.65rem 0 0;
        color: #57534e;
        font-size: 0.88rem;
        line-height: 1.65;
    }

    .feedback-share-url {
        display: block;
        margin-top: 0.85rem;
        overflow-wrap: anywhere;
        color: #92400e;
        font-size: 0.78rem;
        font-weight: 650;
        text-decoration: none;
    }

    .feedback-share-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    .feedback-share-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        min-height: 2.6rem;
        padding: 0.65rem 0.9rem;
        border-radius: 0.7rem;
        font-size: 0.8rem;
        font-weight: 700;
        text-decoration: none;
        transition: transform 150ms ease, box-shadow 150ms ease;
    }

    .feedback-share-button:hover {
        transform: translateY(-1px);
    }

    .feedback-share-button-primary {
        color: #ffffff;
        background: #b45309;
        box-shadow: 0 5px 14px rgba(180, 83, 9, 0.2);
    }

    .feedback-share-button-messenger {
        color: #ffffff;
        background: #0866ff;
        box-shadow: 0 5px 14px rgba(8, 102, 255, 0.2);
    }

    .feedback-share-button svg {
        width: 1rem;
        height: 1rem;
        flex: 0 0 auto;
    }

    @media (max-width: 639px) {
        .feedback-share-card {
            grid-template-columns: 1fr;
        }

        .feedback-share-qr-wrap {
            padding-bottom: 0.75rem;
        }

        .feedback-share-content {
            padding: 1.1rem 1.25rem 1.35rem;
            text-align: center;
        }

        .feedback-share-copy {
            margin-inline: auto;
        }

        .feedback-share-actions {
            justify-content: center;
        }
    }
</style>

<section class="feedback-share-card" aria-labelledby="feedback-share-heading">
    <div class="feedback-share-qr-wrap">
        <img
            src="{{ $qrCodeDataUri }}"
            alt="QR code for the PADAC Fun Run feedback form"
            class="feedback-share-qr"
            width="152"
            height="152"
        />
    </div>

    <div class="feedback-share-content">
        <p class="feedback-share-eyebrow">PADAC Fun Run 2026</p>
        <h2 id="feedback-share-heading" class="feedback-share-heading">Scan to Open the Feedback Form</h2>
        <p class="feedback-share-copy">
            Participants can scan the QR code, open the feedback form directly, or share the form with others on Messenger.
        </p>
        <a href="{{ $feedbackUrl }}" class="feedback-share-url" target="_blank" rel="noopener noreferrer">
            {{ $feedbackUrl }}
        </a>

        <div class="feedback-share-actions">
            <a
                href="{{ $feedbackUrl }}"
                class="feedback-share-button feedback-share-button-primary"
                target="_blank"
                rel="noopener noreferrer"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M14 3h7v7" />
                    <path d="M10 14 21 3" />
                    <path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" />
                </svg>
                Open Feedback Form
            </a>
            <a
                href="{{ $messengerShareUrl }}"
                class="feedback-share-button feedback-share-button-messenger"
                data-feedback-url="{{ $feedbackUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                onclick="
                    if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent) && navigator.share) {
                        event.preventDefault();
                        navigator.share({
                            title: 'PADAC Fun Run 2026 Feedback Form',
                            text: 'Please complete the PADAC Fun Run 2026 feedback form.',
                            url: this.dataset.feedbackUrl,
                        }).catch((error) => {
                            if (error.name !== 'AbortError') {
                                window.open(this.href, '_blank', 'noopener,noreferrer');
                            }
                        });
                    } else if (navigator.clipboard) {
                        navigator.clipboard.writeText(this.dataset.feedbackUrl).catch(() => {});
                    }
                "
            >
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2C6.5 2 2 6.1 2 11.2c0 2.9 1.4 5.5 3.7 7.2V22l3.4-1.9c.9.2 1.9.4 2.9.4 5.5 0 10-4.1 10-9.2S17.5 2 12 2Zm1 12.4-2.5-2.7-4.8 2.7 5.3-5.6 2.5 2.7 4.8-2.7-5.3 5.6Z" />
                </svg>
                Share on Messenger
            </a>
        </div>
    </div>
</section>
