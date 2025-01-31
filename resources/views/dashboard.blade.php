<!-- resources/views/dashboard.blade.php -->

<x-app-layout>
    {{-- PAGE HEADER --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Your Image Feed') }}
            </h2>
            <div class="flex space-x-2">
                {{-- Add Images Button --}}
                <button 
                    id="add-image-button"
                    aria-label="Add new images"
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Add Images
                </button>

                {{-- Connect Instagram Account Button (shows only if not connected) --}}
                @if(!Auth::user()->instagram_access_token)
                    <a 
                        href="{{ route('instagram.auth') }}" 
                        class="inline-flex items-center px-4 py-2 bg-pink-600 border border-transparent rounded-md font-semibold text-white hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Connect Instagram Account
                    </a>
                @else
                    <p class="inline-flex items-center px-4 py-2 bg-green-100 border border-transparent rounded-md font-semibold text-green-800">
                        Instagram connected
                    </p>
                @endif

                {{-- Import Instagram Images Button --}}
                <a 
                    href="{{ route('dashboard.importInstagram') }}" 
                    class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Import Last 9 Instagram Images
                </a>
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

            {{-- ERROR MESSAGE --}}
            @if ($errors->any())
                <div class="p-4 rounded bg-red-100 text-red-800">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- IMAGE GRID --}}
            <div class="bg-white overflow-hidden shadow rounded-lg p-0">
                @if ($images->isEmpty())
                    <p class="text-gray-500 px-6">No images to display. Start by uploading or importing images.</p>
                @else
                    <div id="image-grid" class="grid grid-cols-3 gap-4 p-4">
                        @foreach($images as $image)
                            <div class="relative bg-gray-100 rounded-lg overflow-hidden" data-id="{{ $image->id }}">
                                <a href="{{ route('dashboard.images.edit', $image->id) }}" class="block">
                                    <img 
                                        src="{{ $image->url }}" 
                                        alt="{{ $image->caption ?? 'User image' }}" 
                                        class="w-full h-48 object-cover" 
                                        loading="lazy"
                                    >
                                </a>
                                @if($image->caption)
                                    <div class="absolute bottom-0 bg-black bg-opacity-50 text-white w-full p-2 text-sm">
                                        {{ Str::limit($image->caption, 100) }}
                                    </div>
                                @endif
                                <div class="absolute top-2 right-2 flex space-x-2">
                                    {{-- Edit Button --}}
                                    <a 
                                        href="{{ route('dashboard.images.edit', $image->id) }}" 
                                        class="text-white bg-blue-500 hover:bg-blue-600 rounded-full p-1"
                                        aria-label="Edit Image"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M19.778 4.222a2.5 2.5 0 010 3.536L6.343 21.172a2.5 2.5 0 01-1.768.732H3v-3.105a2.5 2.5 0 01.732-1.768L19.778 4.222z" />
                                        </svg>
                                    </a>
                                    {{-- Delete Button --}}
                                    <form action="{{ route('dashboard.images.destroy', $image->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            class="text-white bg-red-500 hover:bg-red-600 rounded-full p-1"
                                            aria-label="Delete Image"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3H5m16 0h-4" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ADD IMAGE MODAL --}}
    <div id="add-image-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden transition-opacity duration-300">
        <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-md p-6 transform transition-transform duration-300">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modal-title" class="text-lg font-semibold">Upload New Image</h3>
                <button id="close-modal" class="text-gray-600 hover:text-gray-800" aria-label="Close modal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('dashboard.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="photo" class="block font-medium">Select Image</label>
                    <input type="file" name="photo" id="photo" accept="image/*" required class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none mt-1">
                    @error('photo')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="caption" class="block font-medium">Caption (Optional)</label>
                    <input type="text" name="caption" id="caption" maxlength="255" value="{{ old('caption') }}" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none mt-1">
                    @error('caption')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
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

            let isDragging = false;

            // Open modal on clicking Add Images button
            addButton.addEventListener('click', () => {
                modal.classList.remove('hidden');
                document.getElementById('photo').focus();
            });

            // Close modal on clicking close button
            closeModal.addEventListener('click', () => {
                modal.classList.add('hidden');
                addButton.focus();
            });

            // Close modal when clicking outside of it
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    addButton.focus();
                }
            });

            // Close modal on Esc key
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                    addButton.focus();
                }
            });

            // Initialize SortableJS for drag-and-drop reordering
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
                        let orderedIds = [];
                        grid.querySelectorAll('[data-id]').forEach((item) => {
                            orderedIds.push(item.getAttribute('data-id'));
                        });
                        // Update order on the server
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
                // Prevent navigation if dragging
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
