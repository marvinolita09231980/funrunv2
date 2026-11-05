<?php

namespace App\Filament\Participant\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Models\Participant;
use App\Models\Subcategory;
use Filament\Support\RawJs;
use Filament\Schemas\Schema;
use Livewire\Attributes\Locked;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;

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
    public bool $categoryFull = false;
    public ?array $existingParticipant = null;
    public bool $registrationSuccessful = false;

    #[Locked]
    public ?int $receiptParticipantId = null;

   
    
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
              'waiver' => false,
              'categoryDescription' => 'OPEN CATEGORY',
         ]);

         $this->participantExists = false;
         $this->updateCategoryAvailability('OPEN CATEGORY');

         if ($this->categoryFull) {
             $this->dispatch('open-modal', id: 'registration-full');
         }
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
                    Select::make('shirtSize')
                            ->label('Shirt Size')
                            ->options([
                                'xs' => 'XS',
                                'small' => 'Small',
                                'medium' => 'Medium',
                                'large' => 'Large',
                                'xl' => 'XL',
                                '2xl' => '2XL',
                                '3xl' => '3XL',
                                '4xl' => '4XL',
                                '5xl' => '5XL',
                            ])
                            ->searchable()
                            ->required()
                            ->disabled(fn (): bool => $this->participantExists || $this->categoryFull)
                            ->columnSpan(2),
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
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $this->updateCategoryAvailability($state);

                                if ($this->categoryFull) {
                                    $set('registrationType', null);
                                    $set('organizationName', null);
                                    $set('additionalParticipants', []);
                                    $set('shirtSize', null);
                                    $set('distanceCategory', null);
                                }
                            })
                            ->hint(fn (): ?string => $this->categoryFull
                                ? '⚠️ Participant slots are already full. Registration cannot continue.'
                                : null)
                            ->hintColor('danger')
                            ->reactive(),
                        Select::make('registrationType')
                            ->label('Description')
                            ->options([
                                'individual' => 'Individual',
                                'organization' => 'Organization',
                                'couple' => 'Couple',
                                'family' => 'Family',
                                'group' => 'Group',
                            ])
                            ->required()
                            ->disabled(fn (): bool => $this->participantExists || $this->categoryFull)
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (! in_array($state, ['organization', 'group'], true)) {
                                    $set('organizationName', null);
                                }

                                if (! in_array($state, ['couple', 'family', 'group'], true)) {
                                    $set('additionalParticipants', []);
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('organizationName')
                            ->label(fn (Get $get): string => $get('registrationType') === 'group'
                                ? 'Group Name'
                                : 'Organization Name')
                            ->required(fn (Get $get): bool => in_array(
                                $get('registrationType'),
                                ['organization', 'group'],
                                true,
                            ))
                            ->disabled(fn (): bool => $this->participantExists || $this->categoryFull)
                            ->visible(fn (Get $get): bool => in_array(
                                $get('registrationType'),
                                ['organization', 'group'],
                                true,
                            ))
                            ->maxLength(130)
                            ->columnSpanFull(),

                        Repeater::make('additionalParticipants')
                            ->label('Additional Participants')
                            ->helperText('Address, contact number, category, and race distance are shared with the primary participant. Select a shirt size for each runner.')
                            ->schema([
                                Hidden::make('_confirmed')
                                    ->default(false),
                                TextInput::make('firstName')
                                    ->label('First Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $component): void {
                                        $set('_confirmed', false);
                                        $this->checkAdditionalParticipantExists(
                                            $get('firstName'),
                                            $get('lastName'),
                                            $get('birthDate'),
                                            $component->getStatePath(),
                                        );
                                    })
                                    ->columnSpan(2),
                                TextInput::make('lastName')
                                    ->label('Last Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $component): void {
                                        $set('_confirmed', false);
                                        $this->checkAdditionalParticipantExists(
                                            $get('firstName'),
                                            $get('lastName'),
                                            $get('birthDate'),
                                            $component->getStatePath(),
                                        );
                                    })
                                    ->columnSpan(2),
                                TextInput::make('middleInitial')
                                    ->label('Middle Initial')
                                    ->maxLength(1),
                                DatePicker::make('birthDate')
                                    ->label('Birth Date')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $component): void {
                                        $set('_confirmed', false);
                                        $this->checkAdditionalParticipantExists(
                                            $get('firstName'),
                                            $get('lastName'),
                                            $get('birthDate'),
                                            $component->getStatePath(),
                                        );
                                    })
                                    ->columnSpan(2),
                                Select::make('gender')
                                    ->label('Gender')
                                    ->required()
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                        'non-binary' => 'Non-binary',
                                        'transgender' => 'Transgender',
                                        'genderqueer' => 'Genderqueer',
                                        'agender' => 'Agender',
                                        'prefer not to say' => 'Prefer not to say',
                                    ])
                                    ->searchable()
                                    ->columnSpan(3),
                                Select::make('shirtSize')
                                    ->label('Shirt Size')
                                    ->options([
                                        'xs' => 'XS',
                                        'small' => 'Small',
                                        'medium' => 'Medium',
                                        'large' => 'Large',
                                        'xl' => 'XL',
                                        '2xl' => '2XL',
                                        '3xl' => '3XL',
                                        '4xl' => '4XL',
                                        '5xl' => '5XL',
                                    ])
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(2),
                                Radio::make('pwd')
                                    ->label('Person With Disability')
                                    ->required()
                                    ->options([
                                        false => 'NO',
                                        true => 'YES',
                                    ])
                                    ->inline()
                                    ->columnSpan(2),
                                Radio::make('rpwuds')
                                    ->label('Recovering Person Who Used Drugs')
                                    ->required()
                                    ->options([
                                        false => 'NO',
                                        true => 'YES',
                                    ])
                                    ->inline()
                                    ->columnSpan(3),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->minItems(fn (Get $get): ?int => in_array(
                                $get('registrationType'),
                                ['couple', 'family', 'group'],
                                true,
                            ) ? 1 : null)
                            ->maxItems(fn (Get $get): ?int => match ($get('registrationType')) {
                                'couple' => 1,
                                'family' => 3,
                                default => null,
                            })
                            ->addActionLabel(fn (Get $get): string => match ($get('registrationType')) {
                                'couple' => 'Add Partner',
                                'family' => 'Add Family Member',
                                'group' => 'Add Group Member',
                                default => 'Add Participant',
                            })
                            ->extraItemActions([
                                Action::make('confirmParticipant')
                                    ->label('Add Participant')
                                    ->icon('heroicon-o-user-plus')
                                    ->color('success')
                                    ->button()
                                    ->action(function (array $arguments, Repeater $component): void {
                                        $this->confirmAdditionalParticipant($arguments, $component);
                                    }),
                            ])
                            ->reorderable(false)
                            ->disabled(fn (): bool => $this->participantExists || $this->categoryFull)
                            ->visible(fn (Get $get): bool => in_array(
                                $get('registrationType'),
                                ['couple', 'family', 'group'],
                                true,
                            ))
                            ->columnSpanFull(),
                            
                        Radio::make('distanceCategory')
                           
                            ->required()
                            ->options([
                                    '3km' =>    '3KM',
                                    '5km' =>    '5KM',
                                    '10km' =>   '10KM',
                                ])
                            ->inline()
                            ->disabled(fn (): bool => $this->participantExists || $this->categoryFull)
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
    
     

   

    private function updateCategoryAvailability(?string $category): void
    {
        if (blank($category)) {
            $this->categoryFull = false;

            return;
        }

        $subcategory = Subcategory::query()
            ->where('categoryDescription', $category)
            ->first();

        if (! $subcategory) {
            $this->categoryFull = true;

            return;
        }

        $registeredCount = Participant::query()
            ->where('categoryDescription', $category)
            ->where('year', now()->year)
            ->count();

        $this->categoryFull = $registeredCount >= (int) $subcategory->nop;
    }

    public function checkRegistrationAvailability(): void
    {
        $this->updateCategoryAvailability('OPEN CATEGORY');

        if ($this->categoryFull) {
            $this->dispatch('open-modal', id: 'registration-full');
        }
    }

    public function create()
    {
        if ($this->checkParticipantExists()) {
            return;
        }

        $this->updateCategoryAvailability($this->data['categoryDescription'] ?? null);

        if ($this->categoryFull) {
            $this->errorMessage = 'Participant slots are already full. Registration cannot continue.';
            $this->dispatch('open-modal', id: 'registration-full');

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

        // Keep Filament's dehydrated save payload separate from the live form state.
        // Repeaters are dehydrated with numeric keys, while their rendered child
        // schemas use UUID keys. Assigning this payload back to `$this->data`
        // causes the repeater to crash when a rejected submission is rerendered.
        $formData = $this->form->getState();
        $registrationType = $formData['registrationType'];
        $additionalParticipants = in_array($registrationType, ['couple', 'family', 'group'], true)
            ? ($formData['additionalParticipants'] ?? [])
            : [];
        $additionalParticipantKeys = array_keys($this->data['additionalParticipants'] ?? []);

        $registrants = [$this->personalData($formData)];
        $registrantFieldPaths = ['data.firstName'];

        foreach (array_values($additionalParticipants) as $index => $additionalParticipant) {
            $itemKey = $additionalParticipantKeys[$index] ?? $index;
            $registrants[] = $this->personalData($additionalParticipant);
            $registrantFieldPaths[] = "data.additionalParticipants.{$itemKey}.firstName";
        }

        try {
            $this->validateRegistrantDuplicates(
                $registrants,
                (int) now()->year,
                $registrantFieldPaths,
            );
        } catch (ValidationException $exception) {
            $this->showRegistrantValidationErrors($exception);

            return;
        }

        foreach (array_values($additionalParticipants) as $index => $additionalParticipant) {
            if (! (bool) ($additionalParticipant['_confirmed'] ?? false)) {
                $itemKey = $additionalParticipantKeys[$index] ?? $index;

                $this->addError(
                    "data.additionalParticipants.{$itemKey}.firstName",
                    'Click Add Participant to confirm this runner before registering.',
                );

                return;
            }
        }

        try {
            $participant = DB::transaction(function () use (
                $registrants,
                $registrantFieldPaths,
                $formData,
                $registrationType,
            ): Participant {
                $year = (int) now()->year;
                $subcategory = Subcategory::query()
                    ->where('categoryDescription', $formData['categoryDescription'])
                    ->lockForUpdate()
                    ->first();

                if (! $subcategory) {
                    throw ValidationException::withMessages([
                        'data.categoryDescription' => 'The selected category is not available.',
                    ]);
                }

                $registeredCount = Participant::query()
                    ->where('categoryDescription', $formData['categoryDescription'])
                    ->where('year', $year)
                    ->count();
                $remainingSlots = max(0, (int) $subcategory->nop - $registeredCount);

                if (count($registrants) > $remainingSlots) {
                    throw ValidationException::withMessages([
                        'data.additionalParticipants' => $remainingSlots === 0
                            ? 'Participant slots are already full.'
                            : "Only {$remainingSlots} participant slot(s) remain for this category.",
                    ]);
                }

                $this->validateRegistrantDuplicates($registrants, $year, $registrantFieldPaths);

                $sharedData = [
                    'year' => $year,
                    'participantNumber' => '0',
                    'categoryDescription' => $formData['categoryDescription'],
                    'subDescription' => $this->registrationDescription(
                        $registrationType,
                        $formData['organizationName'] ?? null,
                    ),
                    'address' => $formData['address'],
                    'contactNumber' => $formData['contactNumber'],
                    'distanceCategory' => $formData['distanceCategory'],
                    'waiver' => true,
                    'referenceNumber' => '0',
                    'created_by' => 'opencategory',
                    'couple' => $registrationType === 'couple',
                ];

                $primaryParticipant = Participant::create([
                    ...$sharedData,
                    ...$registrants[0],
                ]);
                $participantNumber = (string) $primaryParticipant->getKey();

                $primaryParticipant->forceFill([
                    'participantNumber' => $participantNumber,
                ])->save();

                foreach (array_slice($registrants, 1) as $registrant) {
                    Participant::create([
                        ...$sharedData,
                        ...$registrant,
                        'participantNumber' => $participantNumber,
                    ]);
                }

                return $primaryParticipant->fresh();
            });

            $this->setParticipantModalData($participant, registrationSuccessful: true);
            $this->dispatch('open-modal', id: 'participant-existing');

            $this->participantExists = false;
            $this->categoryFull = false;
            $this->form->fill([
                'waiver' => false,
            ]);

            $this->errorMessage = null; 
        

        } catch (\Throwable $th) {
            if ($th instanceof ValidationException) {
                $this->showRegistrantValidationErrors($th);

                return;
            }

            $this->errorMessage = $th->getMessage();

            if (str_contains($this->errorMessage, 'already registered for this year')) {
                $this->dispatch('open-modal', id: 'already-exists', errorMessage: $this->errorMessage);
            } elseif (str_contains($this->errorMessage, 'slots are already full')) {
                $this->categoryFull = true;
                $this->dispatch('open-modal', id: 'registration-full');
            } elseif (str_contains($this->errorMessage, 'slot')) {
                $this->dispatch('open-modal', id: 'already-exists', errorMessage: $this->errorMessage);
            } else {
                $this->dispatch('open-modal', id: 'already-exists', errorMessage: 'An unexpected error occurred. Please try again.');
            }
        }
    }

    private function personalData(array $participant): array
    {
        return [
            'firstName' => trim((string) ($participant['firstName'] ?? '')),
            'lastName' => trim((string) ($participant['lastName'] ?? '')),
            'middleInitial' => filled($participant['middleInitial'] ?? null)
                ? strtoupper(trim((string) $participant['middleInitial']))
                : null,
            'birthDate' => $participant['birthDate'],
            'gender' => $participant['gender'],
            'shirtSize' => $participant['shirtSize'],
            'pwd' => (bool) $participant['pwd'],
            'rpwuds' => (bool) $participant['rpwuds'],
        ];
    }

    private function registrationDescription(string $registrationType, ?string $name): string
    {
        return match ($registrationType) {
            'organization' => 'Organization: ' . trim((string) $name),
            'group' => 'Group: ' . trim((string) $name),
            default => ucfirst($registrationType),
        };
    }

    private function checkAdditionalParticipantExists(
        ?string $firstName,
        ?string $lastName,
        mixed $birthDate,
        string $statePath,
    ): void {
        $itemStatePath = preg_replace('/\.[^.]+$/', '', $statePath) ?? $statePath;
        $errorPath = "{$itemStatePath}.firstName";

        $this->resetErrorBag($errorPath);

        $firstName = trim((string) $firstName);
        $lastName = trim((string) $lastName);

        if ($firstName === '' || $lastName === '' || blank($birthDate)) {
            return;
        }

        $exists = Participant::query()
            ->where('year', now()->year)
            ->where('firstName', $firstName)
            ->where('lastName', $lastName)
            ->whereDate('birthDate', $birthDate)
            ->exists();

        if ($exists) {
            $this->addError(
                $errorPath,
                'This participant is already registered for the current year and cannot be added again.',
            );
        }
    }

    private function confirmAdditionalParticipant(array $arguments, Repeater $component): void
    {
        $itemKey = $arguments['item'];
        $errorPath = "{$component->getStatePath()}.{$itemKey}.firstName";

        $this->resetErrorBag($errorPath);

        $itemData = $component->getItemState($itemKey);
        $identity = $this->participantIdentity($itemData);

        $alreadyRegistered = Participant::query()
            ->where('year', now()->year)
            ->where('firstName', trim((string) $itemData['firstName']))
            ->where('lastName', trim((string) $itemData['lastName']))
            ->whereDate('birthDate', $itemData['birthDate'])
            ->exists();

        if ($alreadyRegistered) {
            $this->addError(
                $errorPath,
                'This participant is already registered for the current year and cannot be added again.',
            );

            Notification::make()
                ->title('Participant already registered')
                ->body('This runner cannot be added to the registration.')
                ->danger()
                ->send();

            return;
        }

        if ($identity === $this->participantIdentity($this->data)) {
            $this->addError($errorPath, 'The primary participant cannot be added again.');

            return;
        }

        foreach ($component->getRawState() as $otherKey => $otherParticipant) {
            if ((string) $otherKey === (string) $itemKey) {
                continue;
            }

            if ($identity === $this->participantIdentity($otherParticipant)) {
                $this->addError(
                    $errorPath,
                    'This participant has already been added to this registration.',
                );

                return;
            }
        }

        $state = $component->getRawState();
        $state[$itemKey]['_confirmed'] = true;
        $component->rawState($state);
        $component->callAfterStateUpdated();

        Notification::make()
            ->title('Participant added')
            ->body('The runner has been confirmed for this registration.')
            ->success()
            ->send();
    }

    private function participantIdentity(array $participant): ?string
    {
        $firstName = trim((string) ($participant['firstName'] ?? ''));
        $lastName = trim((string) ($participant['lastName'] ?? ''));
        $birthDate = $participant['birthDate'] ?? null;

        if ($firstName === '' || $lastName === '' || blank($birthDate)) {
            return null;
        }

        return mb_strtolower(implode('|', [
            $firstName,
            $lastName,
            (string) $birthDate,
        ]));
    }

    private function validateRegistrantDuplicates(
        array $registrants,
        int $year,
        array $fieldPaths,
    ): void
    {
        $submittedIdentities = [];

        foreach ($registrants as $index => $registrant) {
            $identity = mb_strtolower(implode('|', [
                $registrant['firstName'],
                $registrant['lastName'],
                (string) $registrant['birthDate'],
            ]));
            $name = trim($registrant['firstName'] . ' ' . $registrant['lastName']);

            if (isset($submittedIdentities[$identity])) {
                throw ValidationException::withMessages([
                    $fieldPaths[$index] => "{$name} was entered more than once.",
                ]);
            }

            $submittedIdentities[$identity] = true;

            $exists = Participant::query()
                ->where('year', $year)
                ->where('firstName', $registrant['firstName'])
                ->where('lastName', $registrant['lastName'])
                ->whereDate('birthDate', $registrant['birthDate'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    $fieldPaths[$index] => "{$name} is already registered for this year.",
                ]);
            }
        }
    }

    private function showRegistrantValidationErrors(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $this->addError($field, $message);
            }
        }

        $this->errorMessage = collect($exception->errors())->flatten()->first()
            ?? 'One or more participants cannot be registered.';

        $this->dispatch(
            'open-modal',
            id: 'already-exists',
            errorMessage: $this->errorMessage,
        );
    }


    private function checkParticipantExists(): bool
    {
        $firstName = trim((string) ($this->data['firstName'] ?? ''));
        $lastName = trim((string) ($this->data['lastName'] ?? ''));
        $birthDate = $this->data['birthDate'] ?? null;

        $wasDuplicate = $this->participantExists;

        $this->participantExists = false;
        $this->existingParticipant = null;
        $this->registrationSuccessful = false;
        $this->receiptParticipantId = null;

        $fieldsToFill = [
            'middleInitial',
            'gender',
            'address',
            'contactNumber',
            'pwd',
            'rpwuds',
            'categoryDescription',
            'shirtSize',
            'distanceCategory',
        ];

        // Clear previously loaded information when the identity changes.
        if ($wasDuplicate) {
            foreach ($fieldsToFill as $field) {
                $this->data[$field] = null;
            }

            $this->data['registrationType'] = null;
            $this->data['organizationName'] = null;
            $this->data['additionalParticipants'] = [];
            $this->categoryFull = false;
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

        [$registrationType, $organizationName] = $this->registrationTypeFromParticipant($participant);
        $this->data['registrationType'] = $registrationType;
        $this->data['organizationName'] = $organizationName;
        $this->data['additionalParticipants'] = [];

        $this->form->fill($this->data);

        $this->setParticipantModalData($participant);

        $this->dispatch('open-modal', id: 'participant-existing');

        return true;
    }

    private function setParticipantModalData(
        Participant $participant,
        bool $registrationSuccessful = false,
    ): void {
        $participant->loadMissing('creator');

        $isOpenCategoryRegistration = $this->isOpenCategoryRegistration($participant);
        $this->registrationSuccessful = $registrationSuccessful;
        $this->receiptParticipantId = $isOpenCategoryRegistration
            ? $participant->getKey()
            : null;
        $participantNumber = $participant->participantNumber ?: (string) $participant->getKey();
        $registrationParticipants = $this->registrationParticipantsFor($participant);
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
            'participant_number' => $participantNumber,
            'registration_count' => max(1, $registrationParticipants->count()),
            'additional_participants' => $registrationParticipants
                ->reject(fn (Participant $registrant): bool => $registrant->is($participant))
                ->map(fn (Participant $registrant): array => [
                    'name' => trim(
                        $registrant->firstName . ' ' .
                        ($registrant->middleInitial ? $registrant->middleInitial . '. ' : '') .
                        $registrant->lastName
                    ),
                    'gender' => filled($registrant->gender)
                        ? ucwords(str_replace('-', ' ', $registrant->gender))
                        : 'Not provided',
                    'shirt_size' => filled($registrant->shirtSize)
                        ? strtoupper($registrant->shirtSize)
                        : 'Not provided',
                ])
                ->values()
                ->all(),
            'can_download_receipt' => $isOpenCategoryRegistration,
            'registered_by' => $participant->creator_name,
            'registered_by_phone' => $participant->creator?->cellphone_number,
            'registered_at' => $participant->created_at
                ? $participant->created_at->format('F d, Y h:i A')
                : 'Not recorded',
        ];
    }

    private function registrationTypeFromParticipant(Participant $participant): array
    {
        $description = trim((string) $participant->subDescription);

        if ($participant->couple || strcasecmp($description, 'Couple') === 0) {
            return ['couple', null];
        }

        if (strcasecmp($description, 'Individual') === 0) {
            return ['individual', null];
        }

        if (strcasecmp($description, 'Family') === 0) {
            return ['family', null];
        }

        if (str_starts_with($description, 'Group: ')) {
            return ['group', trim(substr($description, 7))];
        }

        if (str_starts_with($description, 'Organization: ')) {
            return ['organization', trim(substr($description, 14))];
        }

        return ['organization', $description];
    }


    public function viewExistingParticipant(): void
    {
        // Refresh the information from the database before opening.
        $this->checkParticipantExists();
    }
    public function downloadRegistrationReceipt(): ?StreamedResponse
    {
        $participant = $this->receiptParticipantId
            ? Participant::query()->find($this->receiptParticipantId)
            : null;

        if (! $participant) {
            Notification::make()
                ->title('Registration not found')
                ->body('Please check the participant details and try again.')
                ->warning()
                ->send();

            return null;
        }

        if (! $this->isOpenCategoryRegistration($participant)) {
            Notification::make()
                ->title('Receipt unavailable')
                ->body('Receipts can only be downloaded for Open Category registrations.')
                ->warning()
                ->send();

            return null;
        }

        $participantNumber = $participant->participantNumber ?: (string) $participant->getKey();
        $registrationParticipants = $this->registrationParticipantsFor($participant);
        $primaryParticipant = $registrationParticipants->first(
            fn (Participant $registrant): bool => (string) $registrant->getKey() === (string) $participantNumber,
        ) ?? $registrationParticipants->first() ?? $participant;
        $additionalParticipants = $registrationParticipants
            ->reject(
                fn (Participant $registrant): bool => $registrant->is($primaryParticipant),
            )
            ->values();

        $pdfContent = Pdf::loadView(
            'pdf.registration-receipt',
            [
                'participant' => $primaryParticipant,
                'additionalParticipants' => $additionalParticipants,
                'logoDataUri' => $this->receiptLogoDataUri(),
            ]
        )
            ->setPaper('a4')
            ->output();

        return response()->streamDownload(
            static function () use ($pdfContent): void {
                echo $pdfContent;
            },
            "registration-receipt-{$primaryParticipant->year}-{$primaryParticipant->id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    private function registrationParticipantsFor(Participant $participant): EloquentCollection
    {
        if (blank($participant->participantNumber)) {
            return $participant->newCollection([$participant]);
        }

        return Participant::query()
            ->where('year', $participant->year)
            ->where('participantNumber', $participant->participantNumber)
            ->orderBy('id')
            ->get();
    }

    private function isOpenCategoryRegistration(Participant $participant): bool
    {
        return strcasecmp(
            trim((string) $participant->categoryDescription),
            'OPEN CATEGORY',
        ) === 0;
    }

    private function receiptLogoDataUri(): ?string
    {
        $logoPath = public_path('images/login-logo.png');

        if (! is_file($logoPath) || ! is_readable($logoPath)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath));
    }
}
