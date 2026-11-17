<?php

namespace App\Filament\Feedback\Pages;

use App\Models\Feedback;
use App\Models\Participant;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

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
            'feedback_exists' => false,
        ]);
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
    public function create(): void
    {
        $this->validate();

        $year = self::currentEventYear();
        $firstName = trim((string) ($this->data['firstName'] ?? ''));
        $lastName = trim((string) ($this->data['lastName'] ?? ''));
        $birthDate = self::normalizeBirthDate($this->data['birthDate'] ?? null);

        if (! $birthDate) {
            throw ValidationException::withMessages([
                'data.birthDate' => 'Enter a valid birth date.',
            ]);
        }

        try {
            DB::transaction(function () use ($year, $firstName, $lastName, $birthDate): void {
                $participant = Participant::query()
                    ->where('year', $year)
                    ->where('firstName', $firstName)
                    ->where('lastName', $lastName)
                    ->whereDate('birthDate', $birthDate)
                    ->lockForUpdate()
                    ->first();

                if (! $participant) {
                    throw ValidationException::withMessages([
                        'data.firstName' => "No {$year} registered participant matches these details.",
                    ]);
                }

                if (self::feedbackForParticipant($participant, $year)) {
                    throw ValidationException::withMessages([
                        'data.firstName' => "Feedback has already been submitted for this participant in {$year}.",
                    ]);
                }

                $dataToSave = collect($this->data)
                    ->except(['feedback_exists'])
                    ->merge([
                        'year' => $year,
                        'participantNumber' => $participant->participantNumber,
                        'firstName' => $participant->firstName,
                        'lastName' => $participant->lastName,
                        'birthDate' => CarbonImmutable::parse((string) $participant->birthDate)->toDateString(),
                        'full_name' => trim(collect([
                            $participant->firstName,
                            filled($participant->middleInitial)
                                ? rtrim((string) $participant->middleInitial, '.').'.'
                                : null,
                            $participant->lastName,
                        ])->filter()->join(' ')),
                    ])
                    ->toArray();

                Feedback::query()->create($dataToSave);
            });

            $this->data['year'] = $year;
            $this->data['feedback_exists'] = true;
            $this->errorMessage = null;

            Notification::make()
                ->success()
                ->title('Feedback submitted')
                ->body("Your feedback for {$year} has been recorded successfully.")
                ->send();
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? 'The feedback could not be submitted.';

            if (str_contains($message, 'already been submitted')) {
                $this->data['feedback_exists'] = true;

                Notification::make()
                    ->warning()
                    ->title('Feedback already submitted')
                    ->body($message)
                    ->send();
            } else {
                Notification::make()
                    ->danger()
                    ->title('Participant not found')
                    ->body($message)
                    ->send();
            }

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = 'An unexpected error occurred. Please try again.';
            $this->dispatch(
                'open-modal',
                id: 'not-found',
                errorMessage: $this->errorMessage,
            );
        }
    }

    public static function findParticipant(callable $get, callable $set): bool
    {
        $year = self::currentEventYear();
        $firstName = trim((string) $get('firstName'));
        $lastName = trim((string) $get('lastName'));
        $birthDate = self::normalizeBirthDate($get('birthDate'));

        if ($firstName === '' || $lastName === '' || ! $birthDate) {
            self::clearExistingFeedbackState($get, $set);

            return false;
        }

        $participant = Participant::query()
            ->where('year', $year)
            ->where('firstName', $firstName)
            ->where('lastName', $lastName)
            ->whereDate('birthDate', $birthDate)
            ->first();

        if (! $participant) {
            self::clearExistingFeedbackState($get, $set);

            return false;
        }

        $feedback = self::feedbackForParticipant($participant, $year);

        if (! $feedback) {
            self::clearExistingFeedbackState($get, $set);

            return false;
        }

        foreach ([
            'rate',
            'aware_of_funrun',
            'aware_of_funrun_other',
            'inspired',
            'inspired_other',
            'raise_awareness',
            'contribute_to_your_understanding',
            'encouraged_healthy_lifestyle',
            'which_part_enjoy',
            'recommendation',
        ] as $field) {
            $set($field, $feedback->getAttribute($field));
        }

        $set('feedback_exists', true);

        return true;
    }

    private static function feedbackForParticipant(Participant $participant, int $year): ?Feedback
    {
        return Feedback::query()
            ->where('year', $year)
            ->where('firstName', $participant->firstName)
            ->where('lastName', $participant->lastName)
            ->whereDate('birthDate', $participant->birthDate)
            ->first();
    }

    private static function clearExistingFeedbackState(callable $get, callable $set): void
    {
        $wasExistingFeedback = (bool) $get('feedback_exists');
        $set('feedback_exists', false);

        if (! $wasExistingFeedback) {
            return;
        }

        foreach ([
            'rate',
            'aware_of_funrun',
            'aware_of_funrun_other',
            'inspired',
            'inspired_other',
            'raise_awareness',
            'contribute_to_your_understanding',
            'encouraged_healthy_lifestyle',
            'which_part_enjoy',
            'recommendation',
        ] as $field) {
            $set($field, null);
        }
    }

    private static function normalizeBirthDate(mixed $birthDate): ?string
    {
        if (blank($birthDate)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $birthDate)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private static function currentEventYear(): int
    {
        return (int) now('Asia/Manila')->year;
    }


}
