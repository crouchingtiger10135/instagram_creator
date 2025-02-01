{{-- resources/views/edit.blade.php --}}
<x-app-layout>
    {{-- Push Cropper.js CSS into the head --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" integrity="sha512-DZy5LprcsI1P5eH58PEf7zkd9gLEkby/2fhsnxobtmWQwB+ukH9hiVO1R6Mfp7p7PfiGqMnWug32d5gD8aY4eQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Image') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Display error messages if any --}}
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
                    id="edit-form"
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
                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none mt-1"
                        >
                    </div>

                    {{-- Cropping Container (hidden by default) --}}
                    <div id="crop-container" class="mb-4 hidden">
                        <p class="mb-2 font-medium">Crop Your Image</p>
                        <div class="w-full">
                            <img id="cropper-image" src="" alt="Crop Preview" class="w-full object-contain">
                        </div>
                        <button 
                            type="button" 
                            id="apply-crop" 
                            class="mt-2 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Apply Crop
                        </button>
                    </div>

                    {{-- Hidden fields to store cropping data --}}
                    <input type="hidden" name="crop_x" id="crop_x">
                    <input type="hidden" name="crop_y" id="crop_y">
                    <input type="hidden" name="crop_width" id="crop_width">
                    <input type="hidden" name="crop_height" id="crop_height">

                    {{-- Submit button --}}
                    <div class="flex items-center justify-between">
                        <button 
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Save Changes
                        </button>

                        {{-- Cancel link --}}
                        <a 
                            href="{{ route('dashboard') }}" 
                            class="text-sm text-gray-600 hover:underline"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            {{-- DELETE BUTTON (Below Form) --}}
            <div class="mt-6 bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-red-600 mb-4">Delete Image</h3>
                <p class="text-gray-600 mb-4">
                    Deleting this image is permanent and cannot be undone.
                </p>

                <form 
                    action="{{ route('dashboard.images.destroy', $image->id) }}" 
                    method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this image? This action cannot be undone.')"
                >
                    @csrf
                    @method('DELETE')

                    <button 
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Delete Image
                    </button>
                </form>
            </div>

        </div>
    </div>

    {{-- Push Cropper.js Script --}}
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js" integrity="sha512-Ht5eT0DYkaehugpYlYt7pVREIoyNwrA9np8hVwP5HbWSorC/bCq0gI4hHNeTkGugJ2X3Ek4RhPN+1P6/65HS4Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const newPhotoInput = document.getElementById('new_photo');
                const cropContainer = document.getElementById('crop-container');
                const cropperImage = document.getElementById('cropper-image');
                const applyCropButton = document.getElementById('apply-crop');
                let cropper = null;

                // When a new file is selected, display the cropping interface.
                newPhotoInput.addEventListener('change', function(event) {
                    const files = event.target.files;
                    if (files && files.length > 0) {
                        const file = files[0];
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            cropperImage.src = e.target.result;
                            // Show crop container.
                            cropContainer.classList.remove('hidden');
                            // Destroy any previous cropper instance.
                            if (cropper) {
                                cropper.destroy();
                            }
                            // Initialize Cropper.js with a 4:5 aspect ratio.
                            cropper = new Cropper(cropperImage, {
                                aspectRatio: 4 / 5,
                                viewMode: 1,
                            });
                        };
                        reader.readAsDataURL(file);
                    } else {
                        cropContainer.classList.add('hidden');
                        if (cropper) {
                            cropper.destroy();
                            cropper = null;
                        }
                    }
                });

                // When "Apply Crop" is clicked, get the cropping data and store it in hidden inputs.
                applyCropButton.addEventListener('click', function() {
                    if (cropper) {
                        const cropData = cropper.getData(true);
                        document.getElementById('crop_x').value = cropData.x;
                        document.getElementById('crop_y').value = cropData.y;
                        document.getElementById('crop_width').value = cropData.width;
                        document.getElementById('crop_height').value = cropData.height;
                        // Optionally, you could destroy the cropper and hide the container.
                        cropper.destroy();
                        cropper = null;
                        cropContainer.classList.add('hidden');
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
