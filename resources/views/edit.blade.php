{{-- resources/views/edit.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Image') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Show success or error messages if any --}}
            @if ($errors->any())
                <div class="p-4 mb-4 rounded bg-red-100 text-red-800">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Edit form --}}
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <form 
                    action="{{ route('dashboard.images.update', $image->id) }}" 
                    method="POST" 
                    enctype="multipart/form-data"
                >
                    @csrf
                    @method('PATCH')

                    {{-- Current image preview --}}
                    <div class="mb-4">
                        <img 
                            src="{{ Storage::url($image->file_path) }}" 
                            alt="Current Image" 
                            class="w-full object-cover"
                        >
                    </div>

                    {{-- Caption --}}
                    <div class="mb-4">
                        <label for="caption" class="block font-medium">Caption</label>
                        <input 
                            type="text" 
                            name="caption" 
                            id="caption" 
                            value="{{ old('caption', $image->caption) }}"
                            class="block w-full border-gray-300 rounded mt-1"
                        >
                    </div>

                    {{-- Optional new photo --}}
                    <div class="mb-4">
                        <label for="new_photo" class="block font-medium">Replace Photo (optional)</label>
                        <input 
                            type="file" 
                            name="new_photo" 
                            id="new_photo"
                            accept="image/*"
                            class="block w-full text-sm text-gray-900 
                                   border border-gray-300 rounded-lg 
                                   cursor-pointer bg-gray-50 focus:outline-none mt-1"
                        >
                    </div>

                    {{-- Submit button --}}
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
                        Save Changes
                    </button>

                    {{-- Or link back to dashboard --}}
                    <a 
                        href="{{ route('dashboard') }}" 
                        class="inline-flex items-center ml-4 text-sm text-gray-600 hover:underline"
                    >
                        Cancel
                    </a>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
