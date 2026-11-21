<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Participant;
use App\Services\FinisherCertificatePdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class FinisherCertificateController extends Controller
{
    public function preview(Participant $participant): Response
    {
        $this->ensureCertificateIsAvailable($participant);

        return response()
            ->view('feedback.certificate-preview', [
                'downloadUrl' => URL::temporarySignedRoute(
                    'feedback.certificate.download',
                    now()->addMinutes(30),
                    ['participant' => $participant->getKey()],
                ),
                'inlineUrl' => URL::temporarySignedRoute(
                    'feedback.certificate.inline',
                    now()->addMinutes(30),
                    ['participant' => $participant->getKey()],
                ),
                'participant' => $participant,
            ])
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function inline(Participant $participant): Response
    {
        return $this->certificateResponse($participant, 'inline');
    }

    public function download(Participant $participant): Response
    {
        return $this->certificateResponse($participant, 'attachment');
    }

    private function certificateResponse(Participant $participant, string $disposition): Response
    {
        $this->ensureCertificateIsAvailable($participant);

        $document = FinisherCertificatePdf::make($participant);

        return response($document['content'], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => $disposition.'; filename="'.$document['filename'].'"',
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ensureCertificateIsAvailable(Participant $participant): void
    {
        $eventYear = (int) now('Asia/Manila')->year;

        abort_unless((int) $participant->year === $eventYear, 404);

        $hasFeedback = Feedback::query()
            ->where('year', $eventYear)
            ->where('firstName', $participant->firstName)
            ->where('lastName', $participant->lastName)
            ->whereDate('birthDate', $participant->birthDate)
            ->exists();

        abort_unless($hasFeedback, 404);
    }
}
