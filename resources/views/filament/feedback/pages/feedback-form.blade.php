<x-filament-panels::page>
    <div>
        <form class="p-3 border-3 border-orange-300/50 shadow-2xl rounded-2xl" wire:submit="create">
            {{ $this->form }}
            <div class="flex flex-col gap-4">
                
                <div class="flex justify-between items-center mt-4 w-full gap-x-4">
                    @if (! $data['feedback_exists'])
                        <button 
                            class="bg-orange-300 px-3 py-1 rounded-lg">
                            Submit Feedback
                        </button>
                    @endif
                    @if ($data['feedback_exists'])
                        @php
                            
                            $middleInitial = trim($this->data['middleInitial'] ?? '');
                            $middle = $middleInitial ? $middleInitial . '. ' : '';
                            $fullname = $this->data['firstName'] . ' ' . $middle . $this->data['lastName'];
                           
                            $jasperUrl = 'https://paps.davaodeoro.gov.ph/jasperserver/flow.html?';
                            $jasperParams = http_build_query([
                                'pp' => 'u=Jamshasadid|r=Manager|o=EMEA,Sales|pa1=Sweden',
                                '_flowId' => 'viewReportFlow',
                                'ParentFolderUri' => '/reports/marvin_reports',
                                'reportUnit' => '/reports/marvin_reports/kontrun_certificate',
                                'standAlone' => 'true',
                                'output' => 'pdf',
                                'name' => $fullname,
                            ]);
                           
                        @endphp
                        <div class="flex flex-col items-center gap-2 mt-4 p-4 border border-green-200 rounded-2xl bg-green-50 shadow-sm">
                            <p class="text-green-800 font-semibold text-center">
                                You have already submitted your feedback
                            </p>
                             <a href="{{ $jasperUrl . $jasperParams }}"
                                class="bg-orange-300 px-3 py-1 rounded-lg inline-flex justify-center items-center"
                                role="button"
                                target="_blank">
                                    Download Certificate
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
    <x-filament::modal id="not-found" alignment="center" width="md">
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
     
</x-filament-panels::page>
