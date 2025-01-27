{{-- resources/views/feed.blade.php --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Put your feed content here -->

            <!-- Success Alert -->
            @if (session('success'))
                <div class="mb-4 p-4 rounded bg-green-100 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Upload Form -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-8 p-6">
                <h2 class="text-lg font-semibold mb-4">Upload an Image</h2>
                <form 
                    action="{{ route('feed.store') }}" 
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
                               focus:ring-offset-2 transition ease-in-out 
                               duration-150"
                    >
                        Upload
                    </button>
                </form>
            </div>

            <!-- Image Feed -->
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold mb-4">Your Images</h2>
                
                @if ($images->count() === 0)
                    <p class="text-gray-500">No images yet.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        @foreach($images as $image)
                            <div class="border border-gray-200 rounded-md overflow-hidden">
                                <img 
                                    src="{{ Storage::url($image->file_path) }}" 
                                    alt="User image" 
                                    class="w-full h-60 object-cover"
                                >
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
