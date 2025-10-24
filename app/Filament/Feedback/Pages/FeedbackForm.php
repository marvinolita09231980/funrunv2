<?php

namespace App\Filament\Feedback\Pages;

use App\Models\Feedback;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class FeedbackForm extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    protected string $view = 'filament.feedback.pages.feedback-form';

    public function getHeading(): string
    {
        return __('Feedback');
    }

    public function mount(): void
    {
        $this->form->fill();
    }
    

    public function form(Schema $schema):Schema
    {
           return $schema
           ->components([
                Fieldset::make('Your Info')
                    ->schema([
                        TextInput::make('firstName')
                            ->required(),
                        TextInput::make('lastName')
                            ->required(),
                        DatePicker::make('birthDate')
                            ->required(),
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
                            ->required()
                            ->live(),
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
                            ->live(),
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
                            ->live(),
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
           
            $feedback = Feedback::where('year', $this->data['year'])
                ->where('firstName', $this->data['firstName'])
                ->where('lastName', $this->data['lastName'])
                ->whereDate('birthDate', $this->data['birthDate'])
                ->first();

            if ($feedback) {
               
                $feedback->update($this->data);
                $notifmessage = "Record updated successfully.";
            } else {
                
                Feedback::create($this->data);
                $notifmessage =  "Record created successfully.";
            }

            Notification::make()
                ->success()
                ->title('Success Message')
                ->body($notifmessage)
                ->send();

        } catch (\Throwable $th) {
            
                Notification::make()
                    ->danger()
                    ->title('Failed to register')
                    ->body($th->getMessage())
                    ->persistent()
                    ->send();
        }
    }

}
