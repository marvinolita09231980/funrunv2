<?php

namespace App\Filament\Participant\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Livewire\Attributes\Computed;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Facades\Exceptions;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Validation\ValidationException;
use App\Models\Participant;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use App\Filament\Resources\Participants\Schemas\ParticipantForm;

class RegisterForm extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.participant.pages.register-form';
    public array $data = [];
    public ?string $errorMessage = null;
   
    public function getHeading(): string
    {
        return __('Fun Run 2025');
    }

    public function mount(): void
    {
        $this->form->fill([
            'waiver' => false
        ]);

    }
    
    public function form(Schema $schema): Schema
    {
        return ParticipantForm::configure($schema)
                ->extraAttributes(['class' => ''])
                ->statePath('data');
    }

    public function create()
    {
        $this->validate();
        try {


            $this->data['year'] = date('Y');

            $exists = Participant::where('year', $this->data['year'])
            ->where('firstName', $this->data['firstName'])
            ->where('lastName', $this->data['lastName'])
            ->whereDate('birthDate', $this->data['birthDate'])
            ->exists();
            
            if($exists)
            {
                 throw ValidationException::withMessages([
                'duplicate' => 'Duplicate record! Participant is already registered for this year.',
                ]);
            }
            
            Participant::create($this->data);

            Notification::make()
                ->success()
                ->title('Successfully Registered')
                ->send();
            
            $this->data = [];

            $this->errorMessage = null; 

            } catch (\Throwable $th) {

                $this->errorMessage = $th->getMessage();

                Notification::make()
                    ->danger()
                    ->title('Failed to register')
                    ->body($th->getMessage())
                    ->send();
            }
    }
    
}
