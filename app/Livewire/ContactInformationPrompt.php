<?php

namespace App\Livewire;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class ContactInformationPrompt extends Component
{
    public bool $shouldPrompt = false;

    public ?string $email = null;

    public ?string $cellphoneNumber = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $this->email = $user->email;
        $this->cellphoneNumber = $user->cellphone_number;
        $this->shouldPrompt = ! session()->get('contact_information_prompt_dismissed', false)
            && (blank($user->email) || blank($user->cellphone_number));
    }

    public function save(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $this->shouldPrompt = false;

            return;
        }

        $validated = $this->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
            'cellphoneNumber' => ['required', 'string', 'max:20'],
        ], [
            'email.required' => 'Enter the email address you will use for password recovery.',
            'email.unique' => 'This email address is already connected to another account.',
            'cellphoneNumber.required' => 'Enter a cellphone number or choose Not now to continue without saving.',
        ]);

        $user->forceFill([
            'email' => strtolower(trim($validated['email'])),
            'cellphone_number' => trim($validated['cellphoneNumber']),
        ])->save();

        session()->put('contact_information_prompt_dismissed', true);
        $this->shouldPrompt = false;
        $this->dispatch('close-modal', id: 'contact-information-prompt');

        Notification::make()
            ->title('Contact information saved')
            ->body('Your email can now be used for password recovery and account lookup.')
            ->success()
            ->send();
    }

    public function openPrompt(): void
    {
        if (! $this->shouldPrompt) {
            return;
        }

        $this->dispatch('open-modal', id: 'contact-information-prompt');
    }

    public function dismiss(): void
    {
        session()->put('contact_information_prompt_dismissed', true);
        $this->shouldPrompt = false;
        $this->resetValidation();
        $this->dispatch('close-modal', id: 'contact-information-prompt');
    }

    public function render(): View
    {
        return view('livewire.contact-information-prompt');
    }
}
