<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        session()->forget('url.intended');

        $destination = Filament::getCurrentOrDefaultPanel()->getRedirectUrl()
            ?? Filament::getUrl()
            ?? url('/');

        return redirect()->to($destination);
    }
}
