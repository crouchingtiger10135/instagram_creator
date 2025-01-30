{{-- resources/views/dashboard.blade.php --}}

<x-app-layout>
    {{-- PAGE HEADER --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Your Feed') }}
            </h2>
            {{-- Add Images Button --}}
            <button 
                id="add-image-button"
                class="inline-flex items-center px-4 py-2 
                       bg-green-600 border border-transparent 
                       rounded-md font-semibold text-white 
                       hover:bg-green-700 focus:outline-none 
                       focus:ring-2 focus:ring-green-500 
                       focus:ring-offset-2 transition 
                       ease-in-out duration-150"
            >
                Add Images
            </button>
        </div>
    </x-slot>

    {{-- MAIN CONTENT WRAPPER --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- SUCCESS MESSAGE (e.g., after uploading/deleting/editing) --}}
            @if (session('success'))
                <div class="p-4 rounded bg-green-100 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- 2) IMAGE GRID (Responsive, 3 Columns, Full-Width) --}}
            <div class="bg-white overflow-hidden shadow rounded-lg p-0">

                @if ($images->count() === 0)
                    <p class="text-gray-500 px-6">No images yet.</p>
                @else
                    <!-- Instagram-style grid: 3 columns, no gaps, full width -->
                    <div 
                        id="image-grid"
                        class="grid grid-cols-3 gap-0 w-full mx-auto"
                    >
                        @foreach($images as $image)
                            <div 
                                class="relative"
                                data-id="{{ $image->id }}"
                            >
                                <a 
                                    href="{{ route('dashboard.images.edit', $image->id) }}" 
                                    class="block"
                                >
                                    <img 
                                        src="{{ Storage::url($image->file_path) }}" 
                                        alt="{{ $image->caption ?? 'User image' }}"
                                        class="w-full aspect-square object-cover"
                                        loading="lazy"
                                    >
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- ADD IMAGE MODAL --}}
    <div 
        id="add-image-modal" 
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden"
    >
        <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Upload New Image</h3>
                <button id="close-modal" class="text-gray-600 hover:text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Upload Form --}}
            <form 
                action="{{ route('dashboard.store') }}"
                method="POST" 
                enctype="multipart/form-data"
            >
                @csrf

                {{-- Photo Input --}}
                <div class="mb-4">
                    <label for="photo" class="block font-medium">Select Image</label>
                    <input 
                        type="file" 
                        name="photo" 
                        id="photo" 
                        accept="image/*" 
                        required
                        class="block w-full text-sm text-gray-900 
                               border border-gray-300 rounded-lg 
                               cursor-pointer bg-gray-50 focus:outline-none mt-1"
                    >
                    @error('photo')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end">
                    <button 
                        type="submit"
                        class="inline-flex items-center px-4 py-2 
                               bg-blue-600 border border-transparent 
                               rounded-md font-semibold text-white 
                               hover:bg-blue-700 focus:outline-none 
                               focus:ring-2 focus:ring-blue-500 
                               focus:ring-offset-2 transition 
                               ease-in-out duration-150"
                    >
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- SORTABLEJS (CDN) --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const grid = document.getElementById('image-grid');
            const addButton = document.getElementById('add-image-button');
            const modal = document.getElementById('add-image-modal');
            const closeModal = document.getElementById('close-modal');

            // Function to open modal
            addButton.addEventListener('click', () => {
                modal.classList.remove('hidden');
            });

            // Function to close modal
            closeModal.addEventListener('click', () => {
                modal.classList.add('hidden');
            });

            // Close modal when clicking outside the modal content
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });

            // Initialize SortableJS on the grid
            new Sortable(grid, {
                animation: 150,
                ghostClass: 'bg-gray-100',
                handle: 'img', // Make the image the drag handle
                delay: 200, // Delay in ms before drag starts
                delayOnTouchOnly: true, // Apply delay only on touch devices
                touchStartThreshold: 10, // Number of pixels to move before drag starts
                onEnd: function () {
                    // After dragging ends, build a list of IDs in new order
                    let orderedIds = [];
                    grid.querySelectorAll('[data-id]').forEach((item) => {
                        orderedIds.push(item.getAttribute('data-id'));
                    });

                    // Send the new order to the server
                    fetch('{{ route("dashboard.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ orderedIds: orderedIds })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            console.log('Order updated!');
                        } else {
                            console.error('Failed to update order:', data);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            });
        });
    </script>
</x-app-layout>
