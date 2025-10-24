<x-filament-panels::page>
    <form class="p-3 border-3 border-orange-300/50 shadow-2xl rounded-2xl" wire:submit="create">
        {{ $this->form }}
        <div class="flex flex-col gap-4">
            
            <div class="flex justify-between items-center mt-4 w-full gap-x-4">
                <button 
                    class="bg-orange-300 px-3 py-1 rounded-lg">
                    Submit Feedback
                </button>
            </div>
        </div>
    </form>
     
</x-filament-panels::page>
