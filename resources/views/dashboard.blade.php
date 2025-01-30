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
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            {{-- 2) IMAGE GRID (Always 3x3 Layout) --}}
            <div class="bg-white overflow-hidden shadow rounded-lg p-0">
                <h3 class="text-lg font-semibold mb-4 px-6">
                    Your Images (Drag to Reorder)
                </h3>

                @if ($images->count() === 0)
                    <p class="text-gray-500 px-6">No images yet.</p>
                @else
                    <!-- Always 3x3 Grid (Max 9 images) -->
                    <div 
                        id="image-grid"
                        class="grid grid-cols-3 gap-0 w-full mx-auto"
                    >
                        @foreach($images->take(9) as $image) {{-- Only show 9 images --}}
                            <a 
                                href="{{ route('dashboard.images.edit', $image->id) }}" 
                                class="border-none"
                            >
                                <img 
                                    src="{{ Storage::url($image->file_path) }}" 
                                    alt="User image"
                                    class="w-full aspect-square object-cover"
                                >
                            </a>
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
            
            // Initialize SortableJS on the grid
            new Sortable(grid, {
                animation: 150,
                ghostClass: 'bg-gray-100',
                onEnd: function () {
                    // After dragging ends, build a list of IDs in new order
                    let orderedIds = [];
                    grid.querySelectorAll('a').forEach((item) => {
                        orderedIds.push(item.getAttribute('href').split('/').pop());
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
