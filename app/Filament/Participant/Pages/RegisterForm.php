<?php

namespace App\Filament\Participant\Pages;

use Filament\Pages\Page;
use App\Models\Participant;
use App\Models\Subcategory;
use Filament\Support\RawJs;
use Filament\Schemas\Schema;
use Livewire\Attributes\Computed;
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
       
        return $schema
           ->components([
            Fieldset::make('Personal Information')
                ->schema([
                    TextInput::make('firstName')
                            ->required()
                            ->columnSpan(2)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get,$component) {
                                    $exists = self::checkAndFillParticipant($get, $set);
                                    
                            }),
                    TextInput::make('lastName')
                            ->required()
                            ->columnSpan(2)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get,$component) {
                                    $exists =self::checkAndFillParticipant($get, $set);
                                    
                            }),
                    TextInput::make('middleInitial')
                            ->maxLength(1),
                    DatePicker::make('birthDate')
                            ->columnSpan(2)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get,$component) {
                                   $exists = self::checkAndFillParticipant($get, $set);
                                  
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
                            ->columnSpan(3),
                    TextInput::make('address')
                            ->columnSpan(5)
                            ->required(),

                    TextInput::make('contactNumber')
                            ->minLength(11)
                            ->mask(RawJs::make(<<<'JS'
                                    '9999 999 9999'
                                JS))
                            ->stripCharacters(' ')
                            ->columnSpan(2),
                    Radio::make('pwd')
                            ->label('PWD')
                            ->required()
                            ->options([
                                    false =>    'NO',
                                    true =>    'YES',
                                ])
                            ->inline()
                            ->reactive(),
                    Radio::make('rpwuds')
                        ->label('RPWUDS')
                        ->required()
                        ->options([
                                false =>    'NO',
                                true =>    'YES',
                            ])
                        ->inline()
                        ->reactive(),
                
                ])
                ->columns(5),
                Fieldset::make('Funrun Information')
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
                                    return $cat->registered_count >= $cat->nop
                                        ? '⚠️ Participant slots already full.'
                                        : null;
                                }

                                return null;
                            })
                            ->hintColor('danger')
                            ->reactive(),
                        TextInput::make('subDescription')
                            ->label('Description')
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
                    ]),
                    Toggle::make('waiver')
                    ->label('Waiver?')
                    ->default(false)
                    ->inline(false)
                    ->hidden(),        
             ])
            ->statePath('data');
    }

    private static function checkAndFillParticipant(callable $get, callable $set): bool
    {
        $firstName = $get('firstName');
        $lastName = $get('lastName');
        $birthDate = $get('birthDate');
        $yearNow =  date('Y');
       
        
        
        if ($firstName && $lastName && $birthDate) {
            $participant = Participant::where('year', $yearNow)
                ->where('firstName', $firstName)
                ->where('lastName', $lastName)
                ->whereDate('birthDate', $birthDate)
                ->first();

            if ($participant) {
              
                $set('middleInitial', $participant->middleInitial);
                $set('gender', $participant->gender);
                $set('address', $participant->address);
                $set('contactNumber', $participant->contactNumber);
                $set('categoryDescription', $participant->categoryDescription);
                $set('subDescription', $participant->subDescription);
                $set('shirtSize', $participant->shirtSize);
                $set('distanceCategory', $participant->distanceCategory);
                $set('referenceNumber', $participant->referenceNumber);
                return true;
            } 
        }
        return false;
    }
    
   

   

    public function create()
    {
        $this->validate();
        try {

            $this->data['waiver'] = true;
            $this->data['referenceNumber'] = '0';
            
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
                        ->select([
                            'subcategories.*',
                            DB::raw("(SELECT COUNT(*) 
                                      FROM participants 
                                      WHERE participants.categoryDescription = subcategories.categoryDescription 
                                      AND participants.year = '2025') AS registered"),
                        ])
                        ->where('categoryDescription',$this->data['categoryDescription'])
                        ->first();

          
            if($max_n['registered'] >= $max_n['nop'])
            {
                throw ValidationException::withMessages([
                'Maximum Participants' => 'Participant slots are already full.',
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
                    ->persistent()
                    ->send();
            }
    }
}
