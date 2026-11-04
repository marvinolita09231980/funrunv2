<?php

namespace App\Filament\Participant\Pages;

use Filament\Pages\Page;
use App\Models\Participant;
use App\Models\Subcategory;
use Filament\Support\RawJs;
use Filament\Schemas\Schema;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

use Filament\Schemas\Components\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Facades\Exceptions;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use App\Filament\Resources\Participants\Schemas\ParticipantForm;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegisterForm extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    
    protected static ?string $slug = 'funrun-register';
    protected static ?string $title = 'Kalamboan PADAC Dagan Kontra Droga 2026';
    protected string $view = 'filament.participant.pages.register-form';
  
    public array $data = [];
    public ?string $errorMessage = null;
    public bool $participantExists = false;
    public ?array $existingParticipant = null;

   
    
    public function getHeading(): string | Htmlable
    {
        return new HtmlString(
            '<span style="display: block; text-align: center; line-height: 1.2;">'
            . '<span style="display: block;">Kalamboan</span>'
            . '<span style="display: block;">PADAC Dagan Kontra Droga 2026</span>'
            . '</span>'
        );
    }

    public function getExtraBodyAttributes(): array
    {
        return ['class' => 'registration-form-page'];
    }
     
    

     public function mount(): void
     {
         $this->form->fill([
              'waiver' => false
         ]);

         $this->participantExists = false;
     }


    public function form(Schema $schema): Schema
    {
       
        return $schema
           ->components([
            Fieldset::make('Personal Information')
                ->schema([
                    TextInput::make('firstName')
                        ->required()
                        ->columnSpan(2)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (): void {
                            $this->checkParticipantExists();
                        }),
                    TextInput::make('lastName')
                        ->required()
                        ->columnSpan(2)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (): void {
                            $this->checkParticipantExists();
                        }),
                    TextInput::make('middleInitial')
                            ->maxLength(1)
                            ->disabled(fn (): bool => $this->participantExists),
                    DatePicker::make('birthDate')
                        ->required()
                        ->columnSpan(2)
                        ->live()
                        ->afterStateUpdated(function (): void {
                            $this->checkParticipantExists();
                        }),

                    Select::make('gender')
                            ->required()
                            ->options([
                                'male' =>     'Male',
                                'female' => 'Female',
                                'non-binary' => 'Non-binary',
                                'transgender' => 'Transgender',
                                'genderqueer' => 'Genderqueer',
                                'agender' => 'Agender',
                                'prefer not to say' => 'Prefer not to say'
                            ])
                            ->searchable()
                            ->disabled(fn (): bool => $this->participantExists)
                            ->columnSpan(3),
                    TextInput::make('address')
                            ->columnSpan(5)
                            ->disabled(fn (): bool => $this->participantExists)
                            ->required(),

                    TextInput::make('contactNumber')
                            ->minLength(11)
                            ->mask(RawJs::make(<<<'JS'
                                    '9999 999 9999'
                                JS))
                            ->stripCharacters(' ')
                            ->columnSpan(2)
                            ->disabled(fn (): bool => $this->participantExists)
                            ->required(),
                    Radio::make('pwd')
                            ->label('Person With Disability')
                            // ->hint('Persons with Disability')
                            ->required()
                            ->options([
                                    false =>    'NO',
                                    true =>    'YES',
                                ])
                            ->inline()
                            ->disabled(fn (): bool => $this->participantExists)
                            ->reactive(),
                    Radio::make('rpwuds')
                        ->label('Recovering Person Who Used Drugs')
                        // ->hint('Recovering Persons Who Used Drugs')
                        ->required()
                        ->options([
                                false =>    'NO',
                                true =>    'YES',
                            ])
                        ->inline()
                        ->columnSpan(2)
                        ->disabled(fn (): bool => $this->participantExists)
                        ->reactive(),
                    
                
                ])
                ->columns(5),
                Fieldset::make('Funrun Information')
                    ->disabled(fn (): bool => $this->participantExists)
                    ->schema([
                        Select::make('categoryDescription')
                            ->label('Category')
                            
                            ->required()
                            ->options([
                                'OPEN CATEGORY' => 'OPEN CATEGORY',
                            ])
                            ->searchable()
                            ->columnSpanFull() 
                            ->hint(function ($get) {
                                $year = now()->year;
                                if ($this->participantExists) {
                                    return null;
                                }
                                $category = $get('categoryDescription');
                              

                                // 🔹 Only run when both values are selected
                                if (! $category) {
                                    return null;
                                }
                                $cat = DB::table('subcategories as s')
                                    ->leftJoin('participants as p', function ($join) use ($year) {
                                        $join->on('p.categoryDescription', '=', 's.categoryDescription')
                                            ->where('p.year', '=', $year);
                                    })
                                    ->select(
                                        's.nop',
                                        's.categoryDescription',
                                        DB::raw('COUNT(p.id) as registered_count')
                                    )
                                    ->where('s.categoryDescription', $category)
                                    ->groupBy('s.nop', 's.categoryDescription')
                                    ->first();

                                if ($cat) {
                               

                                    if($cat->registered_count >= $cat->nop)
                                    {
                                        return '⚠️ Participant slots already full.';
                                    }
                                    else{
                                        null;
                                    }
                                    
                                    // //  dd($cat->registered_count);
                                    // return $cat->registered_count >= $cat->nop
                                    //     ? '⚠️ Participant slots already full.'
                                    //     : null;
                                }

                                return null;
                            })
                            ->hintColor('danger')
                          
                            ->reactive(),
                        TextInput::make('subDescription')
                            ->label('Description')
                           
                            ->hint('Name of your organization (e.g., Individual, Family/Group, Couple, Team, or Company)')
                            ->required()
                            ->columnSpanFull(),
                            
                        Select::make('shirtSize')
                            
                            ->options([
                                'xs' =>     'XS',
                                'small' => 'Small',
                                'medium' => 'Medium',
                                'large' => 'Large',
                                'xl' => 'XL',
                                '2xl' => '2XL',
                                '3xl' => '3XL',
                                '4xl' => '4XL',
                                '5xl' => '5XL'
                            ])
                            ->searchable()
                            ->required(),
                            
                        Radio::make('distanceCategory')
                           
                            ->required()
                            ->options([
                                    '3km' =>    '3KM',
                                    '5km' =>    '5KM',
                                    '10km' =>   '10KM',
                                ])
                            ->inline()
                            ->reactive(),

                        // Radio::make('couple')
                        //     ->label('Couple Runner')
                        //     ->required()
                        //     ->options([
                        //            false =>    'NO',
                        //            true =>    'YES',
                        //         ])
                        //     ->inline()
                        //     ->reactive(),
                    ]),
                    \Filament\Forms\Components\Hidden::make('waiver')
                    ->default(false)
                    ->rules(['accepted']),
             ])
            ->statePath('data');
    }

    // private static function checkAndFillParticipant(callable $get, callable $set): bool
    // {
    //     $firstName = $get('firstName');
    //     $lastName = $get('lastName');
    //     $birthDate = $get('birthDate');
    //     $yearNow =  date('Y');
       
        
        
    //     if ($firstName && $lastName && $birthDate) {
    //         $participant = Participant::where('year', $yearNow)
    //             ->where('firstName', $firstName)
    //             ->where('lastName', $lastName)
    //             ->whereDate('birthDate', $birthDate)
    //             ->first();

    //         if ($participant) {

                
              
    //              $set('middleInitial', $participant->middleInitial);
    //              $set('gender', $participant->gender);
    //              $set('address', $participant->address);
    //              $set('contactNumber', $participant->contactNumber);
    //              $set('categoryDescription', $participant->categoryDescription);
    //              $set('subDescription', $participant->subDescription);
    //              $set('shirtSize', $participant->shirtSize);
    //              $set('distanceCategory', $participant->distanceCategory);
    //              $set('referenceNumber', $participant->referenceNumber);

               
    //             return true;
    //         } 
    //     }

     
    //     return false;
    // }
    
     

   

    public function create()
    {
        if ($this->checkParticipantExists()) {
            return;
        }

        $this->validate(
            [
                'data.waiver' => ['required', 'accepted'],
            ],
            [
                'data.waiver.required' =>
                    'Please read and agree to the terms and waiver before registering.',

                'data.waiver.accepted' =>
                    'You must agree to the terms and waiver before registering.',
            ]
        );

        $this->data = $this->form->getState();

        $this->data['firstName'] = trim($this->data['firstName']);
        $this->data['lastName'] = trim($this->data['lastName']);
        try {

           
            $this->data['referenceNumber'] = '0';
            $this->data['created_by'] = 'opencategory';
            
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

           
              $max_n =  Subcategory::query()
                        ->select('subcategories.*')
                        ->selectRaw("(SELECT COUNT(*)
                                      FROM participants
                                      WHERE participants.categoryDescription = subcategories.categoryDescription
                                      AND participants.year = ?) AS registered", [$this->data['year']])
                        ->where('categoryDescription',$this->data['categoryDescription'])
                        ->first();

          
            if($max_n['registered'] >= $max_n['nop'])
            {
                throw ValidationException::withMessages([
                'Maximum Participants' => 'Participant slots are already full.',
                ]);
            }
            
            Participant::create($this->data);

            // Notification::make()
            //     ->success()
            //     ->title('Successfully Registered')
            //     ->send();

             
            $this->dispatch('open-modal', id: 'registration-success');
            
            $this->participantExists = false;
            $this->existingParticipant = null;
            $this->form->fill([
                'waiver' => false,
            ]);

            $this->errorMessage = null; 
        

        } catch (\Throwable $th) {
            $this->errorMessage = $th->getMessage();
            if (str_contains($this->errorMessage, 'Duplicate record! Participant is already registered for this year.')) {
                $this->dispatch('open-modal', id: 'already-exists', errorMessage: $this->errorMessage);
            } elseif (str_contains($this->errorMessage, 'Participant slots are already full')) {
                $this->dispatch('open-modal', id: 'already-exists', errorMessage: 'Slots for this category are already full.');
            } else {
                $this->dispatch('open-modal', id: 'already-exists', errorMessage: 'An unexpected error occurred. Please try again.');
            }
        }
    }


    private function checkParticipantExists(): bool
    {
        $firstName = trim((string) ($this->data['firstName'] ?? ''));
        $lastName = trim((string) ($this->data['lastName'] ?? ''));
        $birthDate = $this->data['birthDate'] ?? null;

        $wasDuplicate = $this->participantExists;

        $this->participantExists = false;
        $this->existingParticipant = null;

        $fieldsToFill = [
            'middleInitial',
            'gender',
            'address',
            'contactNumber',
            'pwd',
            'rpwuds',
            'categoryDescription',
            'subDescription',
            'shirtSize',
            'distanceCategory',
        ];

        // Clear previously loaded information when the identity changes.
        if ($wasDuplicate) {
            foreach ($fieldsToFill as $field) {
                $this->data[$field] = null;
            }
        }

        if ($firstName === '' || $lastName === '' || blank($birthDate)) {
            return false;
        }

        $participant = Participant::query()
            ->where('year', now()->year)
            ->where('firstName', $firstName)
            ->where('lastName', $lastName)
            ->whereDate('birthDate', $birthDate)
            ->first();

        if (! $participant) {
            return false;
        }

        $this->participantExists = true;

        // Fill the remaining fields without saving any changes.
        foreach ($fieldsToFill as $field) {
            $this->data[$field] = $participant->getAttribute($field);
        }

        $this->form->fill($this->data);

        // Preserve the keys used by your existing professional modal.
        $this->existingParticipant = [
            'name' => trim(
                $participant->firstName . ' ' .
                ($participant->middleInitial
                    ? $participant->middleInitial . '. '
                    : '') .
                $participant->lastName
            ),
            'year' => $participant->year,
            'category' => $participant->categoryDescription,
            'organization' => $participant->subDescription,
            'distance' => strtoupper((string) $participant->distanceCategory),
            'shirt_size' => strtoupper((string) $participant->shirtSize),
            'registered_by' => $participant->created_by ?: 'Not recorded',
            'registered_at' => $participant->created_at
                ? $participant->created_at->format('F d, Y h:i A')
                : 'Not recorded',
        ];

        $this->dispatch('open-modal', id: 'participant-existing');

        return true;
    }


    public function viewExistingParticipant(): void
    {
        // Refresh the information from the database before opening.
        $this->checkParticipantExists();
    }
    public function downloadRegistrationReceipt(): ?StreamedResponse
    {
        $identity = validator($this->data, [
            'firstName' => ['required', 'string'],
            'lastName' => ['required', 'string'],
            'birthDate' => ['required', 'date'],
        ])->validate();

        $participant = Participant::query()
            ->where('year', now()->year)
            ->where('firstName', trim($identity['firstName']))
            ->where('lastName', trim($identity['lastName']))
            ->whereDate('birthDate', $identity['birthDate'])
            ->first();

        if (! $participant) {
            Notification::make()
                ->title('Registration not found')
                ->body('Please check the participant details and try again.')
                ->warning()
                ->send();

            return null;
        }

        $pdfContent = Pdf::loadView(
            'pdf.registration-receipt',
            ['participant' => $participant]
        )
            ->setPaper('a4')
            ->output();

        return response()->streamDownload(
            static function () use ($pdfContent): void {
                echo $pdfContent;
            },
            "registration-receipt-{$participant->year}-{$participant->id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}
