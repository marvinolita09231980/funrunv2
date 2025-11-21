<?php

namespace App\Filament\Feedback\Pages;

use App\Models\Feedback;
use Filament\Pages\Page;
use App\Models\Participant;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\ValidationException;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class FeedbackForm extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];
    public ?string $errorMessage = null;

    protected string $view = 'filament.feedback.pages.feedback-form';

    public function getHeading(): string
    {
        return __('Feedback');
    }

    public function mount(): void
    {
        $this->form->fill([
              'feedback_exists' => false
         ]);

        $this->form->fill();
    }
    

    public function form(Schema $schema):Schema
    {
           return $schema
           ->components([
                Hidden::make('feedback_exists')->default(false),
                Fieldset::make('Your Info')
                    ->schema([
                        TextInput::make('firstName')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, $set) {
                                  self::findParticipant($get, $set);
                            }),
                        TextInput::make('lastName')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, $set) {
                                  self::findParticipant($get, $set);
                            }),
                        DatePicker::make('birthDate')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, $set) {
                                  self::findParticipant($get, $set);
                            }),
                    ]),
                   
                Fieldset::make('Your Feedback')
                    ->schema([
                        Select::make('rate')
                            ->label('How would you rate your overall satisfaction with the Fun Run?')
                            ->options([
                                'Very Satisfied' => 'Very Satisfied',
                                'Satisfied' => 'Satisfied',
                                'Neutral' => 'Neutral',
                                'Dissatisfied' => 'Dissatisfied',
                                'Very Dissatisfied' => 'Very Dissatisfied',
                            ])
                            ->required(),
                        Select::make('aware_of_funrun')
                            ->label('How did you become aware of the Fun Run event?')
                            ->options([
                                'Social Media' => 'Social Media',
                                'Website' => 'Website',
                                'Flyers' => 'Flyers',
                                'Word of Mouth' => 'Word of Mouth',
                                'Other' => 'Other',
                            ])
                            ->reactive()
                            ->required(),
                            
                        TextInput::make('aware_of_funrun_other')
                            ->label('Please Specify')
                            ->visible(fn (Get $get): string => $get('aware_of_funrun') === 'Other')
                            ->required(fn (Get $get): string => 
                                $get('aware_of_funrun') === 'Other'
                            ),
                        Select::make('inspired')
                            ->label('What inspired you to join the Fun Run?')
                            ->options([
                                'To support the Drug Abuse Program' => 'To support the Drug Abuse Program',
                                'For exercise/ fitness' => 'For exercise/ fitness',
                                'To participate in a community event' => 'To participate in a community event',
                                'Other' => 'Other',
                            ])
                            ->required()
                            ->reactive(),
                        TextInput::make('inspired_other')
                            ->label('Please Specify')
                            ->visible(fn (Get $get): string => $get('inspired') === 'Other')
                            ->required(fn (Get $get): string => 
                                $get('inspired') === 'Other'
                            ),
                        Select::make('raise_awareness')
                            ->label('Did the Fun Run raise awareness about drug abuse?')
                            ->options([
                                'Yes' => 'Yes',
                                'No' => 'No',
                            ])
                            ->required()
                            ->reactive(),
                        Textarea::make('contribute_to_your_understanding')
                                ->label('If yes, how did the event contribute to your understanding of drug abuse?')
                                ->visible(fn (Get $get): string => $get('raise_awareness') === 'Yes')
                                ->required(fn (Get $get): string => 
                                    $get('raise_awareness') === 'Yes'
                                ),
                         Select::make('encouraged_healthy_lifestyle')
                            ->label('Do you think the Fun Run encouraged healthy lifestyle choices and drug-free living?')
                            ->options([
                                'Yes' => 'Yes',
                                'No' => 'No',
                            ])
                            ->required(),   
                        Textarea::make('which_part_enjoy')
                                ->label('Which part of the Fun Run did you enjoy the most?')
                                ->required(),  
                        Textarea::make('recommendation')
                                ->label('What changes would you recommend to improve the Fun Run?')
                                ->required(),   
                    ])
                   
                    ->visible(function(callable $get, $set){
                           
                           return !self::findParticipant($get, $set);
                    })
                    ->columns(1),
           ])
           ->statePath('data');
    }
    public function create()
    {
        $this->validate();
       
        $notifmessage = "";
        try {
            $this->data['year'] = date('Y');

           $this->data['full_name'] = $this->data['firstName'] . ' ' . $this->data['lastName'];
           
            $participant = Participant::where('year', $this->data['year'])
                ->where('firstName', $this->data['firstName'])
                ->where('lastName', $this->data['lastName'])
                ->whereDate('birthDate', $this->data['birthDate'])
                ->first();

             $dataToSave = collect($this->data)
                ->except(['feedback_exists']) // remove unwanted fields
                ->toArray();

            // dd($dataToSave);

            if(!$participant)
            {
                 throw ValidationException::withMessages([
                'Not found!' => 'No participant matches the details you entered.',
                ]);
            }
            
            $feedback = Feedback::where('year', $this->data['year'])
                ->where('firstName', $this->data['firstName'])
                ->where('lastName', $this->data['lastName'])
                ->whereDate('birthDate', $this->data['birthDate'])
                ->first();
               
            
            if ($feedback) {
                $feedback->update($dataToSave);
                $notifmessage = "Record updated successfully.";
                dd('feedback');
            } else {
                dd('no feedback');
                Feedback::create($this->data);
                $notifmessage =  "Record created successfully.";
            }

            Notification::make()
                ->success()
                ->title('Success Message')
                ->body($notifmessage)
                ->send();

            $participant = Participant::where('year', $this->data['year'])
                ->where('firstName', $this->data['firstName'])
                ->where('lastName', $this->data['lastName'])
                ->whereDate('birthDate', $this->data['birthDate'])
                ->first();

        } catch (\Throwable $th) {
            
            $this->errorMessage = $th->getMessage();
            $this->dispatch('open-modal', id: 'not-found', errorMessage: $this->errorMessage);
        }
    }

    public static function findParticipant(Callable $get, Callable $set):bool
    {
        $year = date('Y');
        $first = $get('firstName');
        $last = $get('lastName');
        $birth = $get('birthDate');

        if (! $first || ! $last || ! $birth) {
            return false;
        }

       
        $feedback = Feedback::where('year', $year)
            ->where('firstName', $first)
            ->where('lastName', $last)
            ->where('birthDate', $birth)
            ->first();

        if ($feedback) {
            $set('rate', $feedback->rate);
            $set('aware_of_funrun', $feedback->aware_of_funrun);
            $set('aware_of_funrun_other', $feedback->aware_of_funrun_other);
            $set('inspired', $feedback->inspired);
            $set('inspired_other', $feedback->inspired_other);
            $set('raise_awareness', $feedback->raise_awareness);
            $set('contribute_to_your_understanding', $feedback->contribute_to_your_understanding);
            $set('encouraged_healthy_lifestyle', $feedback->encouraged_healthy_lifestyle);
            $set('which_part_enjoy', $feedback->which_part_enjoy);
            $set('recommendation', $feedback->recommendation);
            $set('feedback_exists', true);
        }
        else{
             $set('feedback_exists', false);
        }
        // else{
        //     $set('rate', null);
        //     $set('aware_of_funrun', null);
        //     $set('aware_of_funrun_other', null);
        //     $set('inspired', null);
        //     $set('inspired_other', null);
        //     $set('raise_awareness', null);
        //     $set('contribute_to_your_understanding', null);
        //     $set('encouraged_healthy_lifestyle', null);
        //     $set('which_part_enjoy', null);
        //     $set('recommendation', null);
        //     $set('feedback_exists', false);
        // }

        return (bool) $feedback; 
    }


}
