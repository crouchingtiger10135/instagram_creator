{{-- resources/views/dashboard.blade.php --}}

<x-app-layout>
    {{-- PAGE HEADER --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Your Dashboard') }}
        </h2>
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

            {{-- 1) UPLOAD FORM --}}
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Upload an Image</h3>

                <form 
                    action="{{ route('dashboard.store') }}"
                    method="POST" 
                    enctype="multipart/form-data"
                    class="flex flex-col sm:flex-row items-center gap-4"
                >
                    @csrf

                    <input 
                        type="file" 
                        name="photo" 
                        accept="image/*" 
                        required
                        class="block w-full text-sm text-gray-900 
                               border border-gray-300 rounded-lg 
                               cursor-pointer bg-gray-50 focus:outline-none"
                    >
                    @error('photo')
                        <div class="text-red-600 text-sm">{{ $message }}</div>
                    @enderror

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
                </form>
            </div>

            {{-- 2) IMAGE GRID (3 Columns, Drag-and-Drop) --}}
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">
                    Your Images (Drag to Reorder)
                </h3>

                @if ($images->count() === 0)
                    <p class="text-gray-500">No images yet.</p>
                @else
                    <!-- 
                      SortableJS Container:
                      For a fixed 3-column grid, we use grid-cols-3 
                    -->
                    <div 
                        id="image-grid"
                        class="grid grid-cols-3 gap-2"
                    >
                        @foreach($images as $image)
                            <div 
                                class="border border-gray-200 rounded-md overflow-hidden relative"
                                data-id="{{ $image->id }}"
                            >
                                <!-- Display the image -->
                                <img 
                                    src="{{ Storage::url($image->file_path) }}" 
                                    alt="User image"
                                    class="w-full aspect-square object-cover"
                                >

                                <!-- If you have a caption -->
                                @if($image->caption)
                                    <div class="p-2 text-sm">
                                        {{ $image->caption }}
                                    </div>
                                @endif

                                <!-- EDIT & DELETE BUTTONS (top-right corner) -->
                                <div class="absolute top-2 right-2 flex gap-2">
                                    {{-- EDIT LINK --}}
                                    <a 
                                        href="{{ route('dashboard.images.edit', $image->id) }}"
                                        class="px-2 py-1 bg-yellow-300 text-xs rounded shadow hover:bg-yellow-400"
                                    >
                                        Edit
                                    </a>

                                    {{-- DELETE FORM --}}
                                    <form 
                                        action="{{ route('dashboard.images.destroy', $image->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this image?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit"
                                            class="px-2 py-1 bg-red-500 text-white text-xs rounded shadow hover:bg-red-600"
                                        >
                                            Delete
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

    {{-- SORTABLEJS (CDN) --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const grid = document.getElementById('image-grid');
            
            // Initialize SortableJS on the 3-col grid
            new Sortable(grid, {
                animation: 150,
                ghostClass: 'bg-gray-100',
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
