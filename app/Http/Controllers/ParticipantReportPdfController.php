<?php

namespace App\Http\Controllers;

use App\Filament\Pages\ParticipantReports;
use App\Models\User;
use App\Services\ParticipantReportPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ParticipantReportPdfController extends Controller
{
    public function age(Request $request): Response
    {
        $user = $this->authorizedUser();
        $validated = validator($request->query(), [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'ranking' => ['required', Rule::in(['all', 'oldest', 'youngest'])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'distance' => ['nullable', Rule::in(['5km', '10km'])],
        ])->validate();

        return $this->inlineResponse(ParticipantReportPdf::age(
            $user,
            (int) $validated['year'],
            $validated['ranking'],
            $validated['gender'] ?? null,
            $validated['distance'] ?? null,
        ));
    }

    public function group(Request $request, string $type): Response
    {
        $user = $this->authorizedUser();
        $validated = validator([
            'year' => $request->query('year'),
            'type' => $type,
        ], [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'type' => ['required', Rule::in(['couple', 'family'])],
        ])->validate();

        return $this->inlineResponse(ParticipantReportPdf::group(
            $user,
            (int) $validated['year'],
            $validated['type'],
        ));
    }

    private function authorizedUser(): User
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User && ParticipantReports::canAccess(),
            403,
        );

        return $user;
    }

    /**
     * @param  array{content: string, filename: string}  $document
     */
    private function inlineResponse(array $document): Response
    {
        return response($document['content'], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'inline; filename="'.$document['filename'].'"',
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
