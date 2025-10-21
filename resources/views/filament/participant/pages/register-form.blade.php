<x-filament-panels::page>
<div  x-data="{
        agreed: @entangle('data.waiver')
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
            <div>
                <button 
                    
                    class="bg-orange-300 px-3 py-1 rounded-lg" 
                    :class="{'!bg-gray-300/60 !text-gray-600/80': !agreed}" 
                    :disabled="!agreed">
                    Submit Registration
                </button>
            </div>
        </div>
    </form>

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
            participation in the "KONTRUN SA DROGA" event organized by the Provincial Anti-Drug Abuse Council (PADAC). 
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
