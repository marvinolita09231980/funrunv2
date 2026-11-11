<?php

use App\Http\Controllers\ParticipantReportPdfController;
use App\Support\FeedbackShareLink;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get(
    '/admin/participant-reports/preview/age',
    [ParticipantReportPdfController::class, 'age'],
)->name('participant-reports.preview.age');

Route::get(
    '/admin/participant-reports/preview/groups/{type}',
    [ParticipantReportPdfController::class, 'group'],
)->whereIn('type', ['couple', 'family'])
    ->name('participant-reports.preview.group');

Route::get('/feedback/feedback-qr-code.png', function () {
    return response(FeedbackShareLink::downloadableQrCodePng(), 200, [
        'Cache-Control' => 'no-store, private',
        'Content-Disposition' => 'attachment; filename="padac-fun-run-2026-feedback-qr.png"',
        'Content-Type' => 'image/png',
    ]);
})->name('feedback.qr-code.download');

Route::get('/feedback/feedback-qr-code-preview.png', function () {
    return response(FeedbackShareLink::qrCodePng(), 200, [
        'Cache-Control' => 'public, max-age=3600',
        'Content-Disposition' => 'inline; filename="padac-fun-run-2026-feedback-qr.png"',
        'Content-Type' => 'image/png',
    ]);
})->name('feedback.qr-code.preview');

Route::get('/feedback/share-feedback-form', function () {
    return view('feedback.share', [
        'feedbackUrl' => FeedbackShareLink::url(),
        'previewImageUrl' => FeedbackShareLink::qrCodePreviewUrl(),
        'shareUrl' => FeedbackShareLink::shareUrl(),
    ]);
})->name('feedback.share');
