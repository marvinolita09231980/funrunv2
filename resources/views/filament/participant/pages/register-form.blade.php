<x-filament-panels::page>
<div  x-data="{
        agreed: @entangle('data.waiver'),
        participantExists: @entangle('participantExists')
    }">
    <form class="p-3 border-3 border-orange-300/50 shadow-2xl rounded-2xl" wire:submit="create">
       
        @if ($errorMessage)
            <div 
                x-data="{ show: true }" 
                x-show="show" 
                x-init="setTimeout(() => show = false, 5000)" 
                class="flex items-center mb-4 p-4 text-sm text-red-800 bg-red-100 border border-red-300 rounded-lg"
            >
                <!-- Danger Icon -->
                <svg class="w-5 h-5 mr-2 text-red-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path fill="currentColor" d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 11.5a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM9 6.25a.75.75 0 011.5 0v4a.75.75 0 01-1.5 0v-4z" />
                </svg>

                <span>{{ $errorMessage }}</span>
            </div>
        @endif
        {{ $this->form }}
        
        <div class="flex flex-col gap-4">
            <div>
                <span class="hover:cursor-default">
                    <label>
                        <x-filament::input.checkbox wire:model="data.waiver"/>
                        I have read and agree to the  
            
                    </label>
                    <x-filament::link @click="$dispatch('open-modal', { id: 'waiver' })">
                        Terms and Conditions, Privacy Notice, and Waiver
                    </x-filament::link>
                </span>
            </div>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-filament::button
                    type="submit"
                    :color="$participantExists ? 'gray' : 'primary'"
                    :disabled="$participantExists || ! ($data['waiver'] ?? false)"
                    wire:loading.attr="disabled"
                    wire:target="create, data"
                >
                    {{ $participantExists ? 'Already Exists' : 'Register' }}
                </x-filament::button>

                @if ($participantExists)
                    <x-filament::button
                        type="button"
                        color="primary"
                        outlined
                        icon="heroicon-o-eye"
                        wire:click="viewExistingParticipant"
                        wire:loading.attr="disabled"
                        wire:target="viewExistingParticipant, data"
                    >
                        View Info
                    </x-filament::button>
                @endif
            </div>
        </div>
    </form>

    <x-filament::modal id="registration-success" alignment="center" width="md">
        <div class="flex flex-col items-center justify-center space-y-6 p-8">
            <!-- Success Icon -->
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600">
                <x-heroicon-o-check-circle class="w-10 h-10" />
            </div>

            <!-- Title -->
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 text-center">
                Registration Successful!
            </h2>

            <!-- Message -->
            <p class="text-center text-gray-600 dark:text-gray-300 text-base leading-relaxed">
                Thank you for registering for the <strong>Kalamboan Fun Run</strong>!  
                Your information has been successfully recorded.  
                
            </p>

            <!-- Action Button -->
            <div class="pt-4">
                <x-filament::button color="success" x-on:click="$dispatch('close-modal', { id: 'registration-success' })">
                    Got it!
                </x-filament::button>
            </div>
        </div>
    </x-filament::modal>

    <x-filament::modal id="already-exists" alignment="center" width="md">
        <div class="flex flex-col items-center justify-center space-y-6 p-8">
           
            <div class="flex items-center justify-center w-20 h-20 rounded-full bg-red-600/10 border-4 border-red-500 shadow-lg">
                <x-heroicon-o-x-circle class="w-12 h-12 text-red-600" />
            </div>

            
            <h2 class="text-3xl font-extrabold text-red-700 dark:text-red-400 text-center tracking-tight">
                {{ $errorTitle ?? 'Registration Failed' }}
            </h2>

           
            <p class="text-center text-gray-700 dark:text-gray-300 text-lg leading-relaxed">
                {{ $errorMessage ?? 'Something went wrong. Please try again.' }}
            </p>

          
            <div class="w-full border-t border-red-300 dark:border-red-700 my-2"></div>

            
            <div class="pt-2">
                <x-filament::button color="danger" size="lg"
                    x-on:click="$dispatch('close-modal', { id: 'already-exists' })"
                    icon="heroicon-o-x-mark">
                    Close
                </x-filament::button>
            </div>
        </div>
    </x-filament::modal>

    <x-filament::modal id="participant-existing" width="xl">
        <x-slot name="heading">
            Registration Already Exists
        </x-slot>

        <x-slot name="description">
            This participant is already registered for the current year.
        </x-slot>

        @if ($existingParticipant)
            <div class="space-y-6">
                {{-- Participant summary --}}
                <div class="rounded-xl bg-gray-50 p-5 ring-1 ring-gray-950/5
                            dark:bg-white/5 dark:ring-white/10">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center
                                    rounded-full bg-amber-100 text-amber-600
                                    dark:bg-amber-400/10 dark:text-amber-400">
                            <x-filament::icon
                                icon="heroicon-o-user"
                                class="h-6 w-6"
                            />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400">
                                Registered Participant
                            </p>

                            <h3 class="mt-1 break-words text-lg font-semibold
                                    text-gray-950 dark:text-white">
                                {{ $existingParticipant['name'] }}
                            </h3>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <x-filament::badge color="success">
                                    Registered
                                </x-filament::badge>

                                <x-filament::badge color="gray">
                                    Year {{ $existingParticipant['year'] }}
                                </x-filament::badge>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Registration details --}}
                <section aria-labelledby="existing-registration-details">
                    <h4
                        id="existing-registration-details"
                        class="text-sm font-semibold text-gray-950 dark:text-white"
                    >
                        Registration Details
                    </h4>

                    <dl class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ([
                            'category' => 'Category',
                            'distance' => 'Race Distance',
                            'shirt_size' => 'Shirt Size',
                        ] as $key => $label)
                            <div class="rounded-lg border border-gray-200 p-4
                                        dark:border-gray-700">
                                <dt class="text-xs font-medium text-gray-500
                                        dark:text-gray-400">
                                    {{ $label }}
                                </dt>

                                <dd class="mt-1 break-words text-sm font-semibold
                                        text-gray-950 dark:text-white">
                                    {{ filled($existingParticipant[$key] ?? null)
                                        ? $existingParticipant[$key]
                                        : 'Not provided' }}
                                </dd>
                            </div>
                        @endforeach

                        <div class="rounded-lg border border-gray-200 p-4
                                    dark:border-gray-700 sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500
                                    dark:text-gray-400">
                                Organization / Description
                            </dt>

                            <dd class="mt-1 break-words text-sm font-semibold
                                    text-gray-950 dark:text-white">
                                {{ filled($existingParticipant['organization'] ?? null)
                                    ? $existingParticipant['organization']
                                    : 'Not provided' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                {{-- Registration history --}}
                <section class="border-t border-gray-200 pt-5 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <x-filament::icon
                            icon="heroicon-o-clock"
                            class="h-4 w-4 text-gray-400"
                        />

                        <h4 class="text-sm font-semibold text-gray-950 dark:text-white">
                            Registration History
                        </h4>
                    </div>

                    <dl class="mt-3 space-y-3 text-sm">
                        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">
                                Registered By
                            </dt>

                            <dd class="break-words font-medium text-gray-950
                                    dark:text-white sm:text-right">
                                {{ $existingParticipant['registered_by'] }}
                            </dd>
                        </div>

                        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">
                                Date Registered
                            </dt>

                            <dd class="font-medium text-gray-950 dark:text-white
                                    sm:text-right">
                                {{ $existingParticipant['registered_at'] }}
                            </dd>
                        </div>
                    </dl>
                </section>

                {{-- Guidance --}}
                <div class="flex items-start gap-3 rounded-lg bg-amber-50 p-4
                            text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                    <x-filament::icon
                        icon="heroicon-o-information-circle"
                        class="h-5 w-5 shrink-0"
                    />

                    <p class="text-sm leading-relaxed">
                        No additional registration is needed. If these details
                        are incorrect, please contact the event organizers.
                    </p>
                </div>
            </div>
        @endif

        <x-slot name="footerActions">
            <x-filament::button
                color="primary"
                x-on:click="$dispatch('close-modal', { id: 'participant-existing' })"
            >
                Understood
            </x-filament::button>
        </x-slot>
    </x-filament::modal>


    <x-filament::modal width="2xl"
    :close-by-escaping="false"
    :close-by-clicking-away="false"
        alignment="center"
         id="waiver">

        <div id="privacy_policy_text_content">
        <center class="leading-4"><h1><b>PRIVACY NOTICE</b></h1></center>  <br>
        </div>
        
        <p>
            Purpose of Data Collection (in accordance with RA 10173): The Provincial Government of Davao de Oro requires your personal information to verify your 
            participation in the <b>"Kalamboan: Dagan Kontra Droga 2026"</b> event organized by the Provincial Anti-Drug Abuse Council (PADAC). 
        </p>
       
        <p>
            This data will be securely included in both print and electronic reports related to this event and will only be accessible to authorized personnel.
             By signing this notice, you consent to the collection and use of your information strictly for these purposes.
             Access to your data is strictly limited to authorized personnel, ensuring its confidentiality and security. 
        </p>
       
        <p>
            Should you wish to withdraw your consent, please inform us, and we will permanently delete your data once the reports are completed. Event Photography and Videography: 
            Photographs and videos will be taken throughout the event to serve as documentary evidence and other promotional purposes. 
        </p>
       
        <p>
            If featured, we may publish your name, organization, and position title with associated photos/videos to recognize roles as key participants. 
        </p>
       
        <p>
            Smart DDO Infocast: The PLGU Davao de Oro will also distribute advisories, alerts and information to particular groups via SMS. 
            The acquired data will be uploaded to the SMART InfoCast system and protected by the terms and conditions of SMART communications 
            on privacy and security as well as the provisions of the Data Privacy Protection law. 
        </p>
      
        <p>
            If the registrant wishes to stop receiving SMS advisories in the future, they can unsubscribe via SMS or contact information@davaodeoro.gov.ph. 
        </p>
       
        <p>
            By Registering, you agree to our Privacy Notice. 
        </p>
        <br>
       
       
    
   
        <center class="leading-4"><h1><b>Waiver</b></h1></center>
        
        <p> <span style="margin-left:20px;">I</span> know that running a road race is a potentially hazardous activity and that I should
            not enter and run unless I am medically able and properly trained. I agree to abide
            by any race official's decision relative to my ability to complete the run safely . I
            assume all risks associated with running in this event including, but not limited to:
            falls, contact with other participants, the effects of the weather, including high heat
            and/or humidity, traffic , and the conditions of the road, all such risks being known
            and appreciated by me.
        </p>
        
        <p>
            <span style="margin-left:20px;">Having</span> read this waiver and knowing these facts and in
            consideration of your accepting my entry, I, for myself and anyone entitled to act
            on my behalf, waive and release the organizers of this Fun Run and any
            other sponsors, their representatives , and successors from all claims or liabilities of
            any kind arising out of my participation in this event or carelessness on the part of
            the person named in this waiver. 
        </p>
       
        <p>
            <span style="margin-left:20px;">Further</span>, I grant permission to all of the foregoing
            to use any photographs, motion pictures, recordings, or any other record of this
            event for legitimate purposes. 
        </p>
        
       
        <div class="flex items-center justify-center mt-4">
            {{-- <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a> --}}

           <button class="bg-black-300 px-3 py-1 rounded-lg"  x-on:click="$dispatch('close-modal', { id: 'waiver' })">
                    Close
           </button>
           
            
        </div>
   
    </x-filament::modal>
</div>

</x-filament-panels::page>
