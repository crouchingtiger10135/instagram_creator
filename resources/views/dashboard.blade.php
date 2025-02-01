{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>
    {{-- PAGE HEADER --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Your Feed') }}
            </h2>
            <div class="flex space-x-2">
                {{-- Toggle Selection Mode Button --}}
                <button 
                    id="toggle-selection-mode"
                    aria-label="Toggle selection mode"
                    class="inline-flex items-center px-4 py-2 
                           bg-yellow-600 border border-transparent 
                           rounded-md font-semibold text-white 
                           hover:bg-yellow-700 focus:outline-none 
                           focus:ring-2 focus:ring-yellow-500 
                           focus:ring-offset-2 transition 
                           ease-in-out duration-150"
                >
                    Select Images
                </button>
                {{-- Add Images Button --}}
                <button 
                    id="add-image-button"
                    aria-label="Add new images"
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
        </div>
    </x-slot>

    {{-- MAIN CONTENT WRAPPER --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- SUCCESS MESSAGE --}}
            @if (session('success'))
                <div class="p-4 rounded bg-green-100 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- IMAGE GRID WITH BULK DELETE FORM --}}
            <div class="bg-white overflow-hidden shadow rounded-lg p-0">
                @if ($images->count() === 0)
                    <p class="text-gray-500 px-6">No images yet.</p>
                @else
                    <form action="{{ route('dashboard.images.bulk-delete') }}" method="POST">
                        @csrf
                        {{-- 3-column, gap-0, full-width grid --}}
                        <div 
                            id="image-grid"
                            class="grid grid-cols-3 gap-0 w-full mx-auto relative"
                        >
                            @foreach($images as $image)
                                <div class="relative" data-id="{{ $image->id }}">
                                    {{-- Bulk Delete Checkbox (hidden by default) --}}
                                    <input 
                                        type="checkbox" 
                                        name="image_ids[]" 
                                        value="{{ $image->id }}"
                                        class="bulk-checkbox absolute top-2 left-2 z-10 w-5 h-5 hidden"
                                    >
                                    <!-- Click image to edit (goes to resources/views/edit.blade.php) -->
                                    <a 
                                        href="{{ route('dashboard.images.edit', $image->id) }}" 
                                        class="block"
                                    >
                                        <img 
                                            src="{{ asset('storage/'.$image->file_path) }}" 
                                            alt="{{ $image->caption ?? 'User image' }}"
                                            class="w-full aspect-[4/5] object-cover"
                                            loading="lazy"
                                        >
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        {{-- Bulk Delete Button (hidden by default) --}}
                        <div id="bulk-delete-container" class="mt-4 text-right px-6 hidden">
                            <button 
                                type="submit"
                                class="inline-flex items-center px-4 py-2 
                                       bg-red-600 border border-transparent 
                                       rounded-md font-semibold text-white 
                                       hover:bg-red-700 focus:outline-none 
                                       focus:ring-2 focus:ring-red-500 
                                       focus:ring-offset-2 transition 
                                       ease-in-out duration-150"
                            >
                                Delete Selected
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ADD IMAGE MODAL (Multiple Images) --}}
    <div 
        id="add-image-modal" 
        role="dialog" 
        aria-modal="true" 
        aria-labelledby="modal-title"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden transition-opacity duration-300"
    >
        <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-md p-6 transform transition-transform duration-300">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modal-title" class="text-lg font-semibold">Upload New Image(s)</h3>
                <button id="close-modal" class="text-gray-600 hover:text-gray-800" aria-label="Close modal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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

                {{-- Multiple Photos Input --}}
                <div class="mb-4">
                    <label for="photos" class="block font-medium">Select Image(s)</label>
                    <input 
                        type="file" 
                        name="photos[]" 
                        id="photos" 
                        accept="image/*" 
                        multiple
                        required
                        class="block w-full text-sm text-gray-900 
                               border border-gray-300 rounded-lg 
                               cursor-pointer bg-gray-50 focus:outline-none mt-1"
                    >
                    @error('photos')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                    @enderror
                    @error('photos.*')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Optional Caption Input (applies to all images) --}}
                <div class="mb-4">
                    <label for="caption" class="block font-medium">Caption (Optional)</label>
                    <input 
                        type="text" 
                        name="caption" 
                        id="caption" 
                        maxlength="255"
                        class="block w-full text-sm text-gray-900 
                               border border-gray-300 rounded-lg 
                               bg-gray-50 focus:outline-none mt-1"
                        value="{{ old('caption') }}"
                    >
                    @error('caption')
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
            const toggleSelectionModeButton = document.getElementById('toggle-selection-mode');
            const bulkDeleteContainer = document.getElementById('bulk-delete-container');
            const checkboxes = document.querySelectorAll('.bulk-checkbox');
            let isDragging = false; // Flag to track dragging state
            let selectionMode = false; // Flag to track selection mode

            // Open modal
            addButton.addEventListener('click', () => {
                modal.classList.remove('hidden');
                document.getElementById('photos').focus();
            });

            // Close modal
            closeModal.addEventListener('click', () => {
                modal.classList.add('hidden');
                addButton.focus();
            });

            // Close modal on outside click
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    addButton.focus();
                }
            });

            // Close modal on Escape key
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                    addButton.focus();
                }
            });

            // Toggle selection mode for checkboxes
            toggleSelectionModeButton.addEventListener('click', () => {
                selectionMode = !selectionMode;
                if (selectionMode) {
                    // Show checkboxes and bulk delete button
                    checkboxes.forEach(cb => cb.classList.remove('hidden'));
                    bulkDeleteContainer.classList.remove('hidden');
                    toggleSelectionModeButton.textContent = 'Cancel Selection';
                } else {
                    // Hide checkboxes and bulk delete button, and uncheck any selected boxes
                    checkboxes.forEach(cb => {
                        cb.classList.add('hidden');
                        cb.checked = false;
                    });
                    bulkDeleteContainer.classList.add('hidden');
                    toggleSelectionModeButton.textContent = 'Select Images';
                }
            });

            // Initialize SortableJS on the grid (if it exists)
            if (grid) {
                new Sortable(grid, {
                    animation: 150,
                    ghostClass: 'bg-gray-100',
                    delay: 100,
                    delayOnTouchOnly: true,
                    touchStartThreshold: 15,
                    onStart: function () {
                        isDragging = true;
                        grid.classList.add('dragging');
                    },
                    onEnd: function () {
                        isDragging = false;
                        grid.classList.remove('dragging');

                        // Build a list of IDs in the new order
                        let orderedIds = [];
                        grid.querySelectorAll('[data-id]').forEach((item) => {
                            orderedIds.push(item.getAttribute('data-id'));
                        });

                        // Send new order to the server
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
                                alert('Failed to update order. Please try again.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while updating the order.');
                        });
                    }
                });

                // Prevent navigation when dragging
                grid.querySelectorAll('a').forEach(function(anchor) {
                    anchor.addEventListener('click', function(e) {
                        if (isDragging) {
                            e.preventDefault();
                        }
                    });
                });
            }
        });
    </script>
</x-app-layout>
