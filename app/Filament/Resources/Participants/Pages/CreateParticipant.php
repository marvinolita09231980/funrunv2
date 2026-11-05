<?php

namespace App\Filament\Resources\Participants\Pages;

use App\Models\Participant;
use App\Models\Subcategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Validation\ValidationException;
use App\Filament\Resources\Participants\ParticipantResource;
use App\Filament\Resources\Participants\Widgets\ParticipantAllocationOverview;

class CreateParticipant extends CreateRecord
{
    protected static string $resource = ParticipantResource::class;

    protected string $view = 'filament.resources.participants.pages.create-participant';

    public ?array $duplicateParticipant = null;

    protected function getHeaderWidgets(): array
    {
        return [
            ParticipantAllocationOverview::class,
        ];
    }

    public function clearDuplicateParticipantForm(): void
    {
        $this->form->fill();
        $this->duplicateParticipant = null;
        $this->resetErrorBag();
        $this->dispatch('close-modal', id: 'duplicate-participant');
    }

    public function showExistingParticipantModal(Participant|int $participant): void
    {
        if (! $participant instanceof Participant) {
            $participant = Participant::query()->findOrFail($participant);
        }

        abort_unless($this->matchesEnteredIdentity($participant), 403);

        $participant->loadMissing('creator.roles');
        $creator = $participant->creator;

        $this->duplicateParticipant = [
            'id' => $participant->getKey(),
            'name' => collect([
                $participant->firstName,
                $participant->middleInitial,
                $participant->lastName,
            ])->filter()->join(' '),
            'birth_date' => filled($participant->birthDate)
                ? Carbon::parse($participant->birthDate)->format('F j, Y')
                : 'Not recorded',
            'year' => $participant->year,
            'category' => $participant->categoryDescription ?: 'Not recorded',
            'subcategory' => $participant->subDescription ?: 'Not recorded',
            'distance' => strtoupper((string) $participant->distanceCategory),
            'shirt_size' => strtoupper((string) $participant->shirtSize),
            'creator_name' => $participant->creator_name,
            'creator_role' => $this->creatorRoleLabel($creator),
            'creator_phone' => $creator?->cellphone_number,
        ];

        $this->dispatch('open-modal', id: 'duplicate-participant');
    }

    private function matchesEnteredIdentity(Participant $participant): bool
    {
        $enteredBirthDate = $this->data['birthDate'] ?? null;

        if (blank($enteredBirthDate)) {
            return false;
        }

        return strcasecmp(
            trim((string) ($this->data['firstName'] ?? '')),
            trim((string) $participant->firstName),
        ) === 0
            && strcasecmp(
                trim((string) ($this->data['lastName'] ?? '')),
                trim((string) $participant->lastName),
            ) === 0
            && Carbon::parse($enteredBirthDate)->isSameDay(Carbon::parse($participant->birthDate));
    }

    private function creatorRoleLabel(?User $creator): ?string
    {
        if (! $creator) {
            return null;
        }

        return match (true) {
            $creator->isAdministrativeOfficer() => 'Administrative Officer',
            $creator->hasRole('focal user') => 'Focal User',
            $creator->hasAnyRole(['admin', 'superadmin', 'super_admin']) => 'Administrator',
            default => 'User',
        };
    }

    protected function mutateFormDataBeforeCreate(array $data):array
    {

            $cat = null;
            $year = now()->year;
            $this->validate();
            $this->data['year'] = $year;
            $this->data['waiver'] = true;
            $this->data['referenceNumber'] = '0';
            $this->data['created_by'] = Auth::user()->username;

            if (
                Auth::user()->isAdministrativeOfficer()
                && ! Auth::user()->administersCategory($this->data['categoryDescription'] ?? null)
            ) {
                throw ValidationException::withMessages([
                    'categoryDescription' => 'You may only add participants to your administered category.',
                ]);
            }

            if (
                Auth::user()->hasRole('focal user')
                && ! Auth::user()->canManageParticipantData(
                    $this->data['categoryDescription'] ?? null,
                    $this->data['subDescription'] ?? null,
                )
            ) {
                throw ValidationException::withMessages([
                    'subDescription' => 'You may only add participants to your assigned subcategory.',
                ]);
            }

            $existingParticipant = Participant::with('creator.roles')
            ->where('year', $this->data['year'])
            ->where('firstName', $this->data['firstName'])
            ->where('lastName', $this->data['lastName'])
            ->whereDate('birthDate', $this->data['birthDate'])
            ->first();
            
            if ($existingParticipant) {
                $this->showExistingParticipantModal($existingParticipant);
                $this->addError(
                    'data.firstName',
                    'This participant is already registered for the current year.',
                );
                $this->halt();
            }

            if($this->data['categoryDescription'] ==='OPEN CATEGORY')
            {
                $cat = DB::table('subcategories as s')
                                        ->leftJoin('participants as p',function($join) use ($year) {
                                            $join->on('p.categoryDescription','=','s.categoryDescription')    
                                                ->where('p.year','=',$year);             
                                        })
                                        ->select(
                                            's.nop',
                                            's.categoryDescription',
                                            DB::raw('COUNT(p.id) as registered_count')
                                        )
                                        ->where('s.categoryDescription',$this->data['categoryDescription']) 
                                        ->groupBy('s.nop', 's.categoryDescription')
                                        ->first();
            }
            else
            {
                $cat = DB::table('subcategories as s')
                        ->leftJoin('participants as p',function($join) use ($year) {
                            $join->on('p.categoryDescription','=','s.categoryDescription')    
                                ->on('p.subDescription','=','s.subDescription')   
                                ->where('p.year','=',$year);             
                        })
                        ->select(
                            's.nop',
                            's.categoryDescription',
                            's.subDescription',
                            DB::raw('COUNT(p.id) as registered_count')
                        )
                        ->where('s.categoryDescription',$this->data['categoryDescription']) 
                        ->where('s.subDescription',$this->data['subDescription']) 
                        ->groupBy('s.nop', 's.categoryDescription', 's.subDescription')
                        ->first();
            }

           
                    

            if (! $cat) {
                throw ValidationException::withMessages([
                    'subDescription' => 'The selected subcategory is not available.',
                ]);
            }

            if($cat->registered_count >= $cat->nop)
            {
                Notification::make()
                    ->danger()
                    ->title('Maximum Participants')
                    ->body('Participant slots are already full.')
                    ->send();
    
                throw ValidationException::withMessages([
                    'Maximum Participants' => 'Participant slots are already full.',
                ]);

            }
        
        return $this->data;
        
    }
    
}
