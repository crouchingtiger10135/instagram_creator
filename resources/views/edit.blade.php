{{-- resources/views/edit.blade.php --}}
<x-app-layout>
    {{-- Push Cropper.js CSS into the head --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" integrity="sha512-DZy5LprcsI1P5eH58PEf7zkd9gLEkby/2fhsnxobtmWQwB+ukH9hiVO1R6Mfp7p7PfiGqMnWug32d5gD8aY4eQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- Optional: Basic styling for the modal -->
        <style>
            /* Modal backdrop */
            #crop-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.75);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }
            /* Modal content */
            #crop-modal .modal-content {
                background: #fff;
                padding: 1.5rem;
                border-radius: 0.5rem;
                max-width: 600px;
                width: 90%;
            }
        </style>
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

            {{-- Edit Form --}}
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <form action="{{ route('dashboard.images.update', $image->id) }}" method="POST" enctype="multipart/form-data" id="edit-form">
                    @csrf
                    @method('PATCH')

                    {{-- Current Image Preview --}}
                    <div class="mb-4">
                        <img id="current-image" src="{{ Storage::url($image->file_path) }}" alt="Current Image" class="w-full object-cover">
                    </div>

                    {{-- Caption --}}
                    <div class="mb-4">
                        <label for="caption" class="block font-medium">Caption</label>
                        <input type="text" name="caption" id="caption" value="{{ old('caption', $image->caption) }}" class="block w-full border-gray-300 rounded mt-1">
                    </div>

                    {{-- Crop Current Image Button --}}
                    <div class="mb-4">
                        <button type="button" id="open-crop-modal" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            Crop Current Image
                        </button>
                    </div>

                    {{-- Hidden Fields to Store Cropping Data --}}
                    <input type="hidden" name="crop_x" id="crop_x">
                    <input type="hidden" name="crop_y" id="crop_y">
                    <input type="hidden" name="crop_width" id="crop_width">
                    <input type="hidden" name="crop_height" id="crop_height">

                    {{-- Submit Button --}}
                    <div class="flex items-center justify-between">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Save Changes
                        </button>
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>

            {{-- Delete Button (Below Form) --}}
            <div class="mt-6 bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-red-600 mb-4">Delete Image</h3>
                <p class="text-gray-600 mb-4">Deleting this image is permanent and cannot be undone.</p>
                <form action="{{ route('dashboard.images.destroy', $image->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this image? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Delete Image
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Crop Modal (Hidden by Default) --}}
    <div id="crop-modal" class="hidden">
        <div class="modal-content">
            <h3 class="mb-2 text-xl font-semibold">Crop Image</h3>
            <div class="w-full">
                <!-- The image to be cropped inside the modal -->
                <img id="modal-crop-image" src="{{ Storage::url($image->file_path) }}" alt="Crop Preview" class="w-full object-contain">
            </div>
            <div class="mt-4 flex justify-end space-x-2">
                <button type="button" id="cancel-crop" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">Cancel</button>
                <button type="button" id="apply-crop-modal" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Apply Crop</button>
            </div>
        </div>
    </div>

    {{-- Push Cropper.js and jQuery Script (if needed) --}}
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js" integrity="sha512-Ht5eT0DYkaehugpYlYt7pVREIoyNwrA9np8hVwP5HbWSorC/bCq0gI4hHNeTkGugJ2X3Ek4RhPN+1P6/65HS4Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log("Edit page loaded.");
                const openCropModalBtn = document.getElementById('open-crop-modal');
                const cropModal = document.getElementById('crop-modal');
                const modalCropImage = document.getElementById('modal-crop-image');
                const cancelCropBtn = document.getElementById('cancel-crop');
                const applyCropModalBtn = document.getElementById('apply-crop-modal');
                
                let cropperModal = null;

                // Open the crop modal and initialize Cropper.js
                openCropModalBtn.addEventListener('click', function() {
                    console.log("Opening crop modal.");
                    cropModal.classList.remove('hidden');
                    
                    // Ensure the image is loaded before initializing Cropper.js
                    if (modalCropImage.complete) {
                        initializeModalCropper();
                    } else {
                        modalCropImage.onload = initializeModalCropper;
                    }
                });

                function initializeModalCropper() {
                    console.log("Initializing Cropper on modal image:", modalCropImage.src);
                    if (cropperModal) {
                        cropperModal.destroy();
                    }
                    cropperModal = new Cropper(modalCropImage, {
                        aspectRatio: 4 / 5,
                        viewMode: 1,
                    });
                }

                // Cancel cropping: destroy cropper and close modal
                cancelCropBtn.addEventListener('click', function() {
                    if (cropperModal) {
                        cropperModal.destroy();
                        cropperModal = null;
                    }
                    cropModal.classList.add('hidden');
                });

                // Apply cropping: capture crop data, store in hidden inputs, destroy cropper, and close modal
                applyCropModalBtn.addEventListener('click', function() {
                    if (cropperModal) {
                        const cropData = cropperModal.getData(true);
                        console.log("Modal Crop Data:", cropData);
                        document.getElementById('crop_x').value = cropData.x;
                        document.getElementById('crop_y').value = cropData.y;
                        document.getElementById('crop_width').value = cropData.width;
                        document.getElementById('crop_height').value = cropData.height;
                        cropperModal.destroy();
                        cropperModal = null;
                    }
                    cropModal.classList.add('hidden');
                });
            });
        </script>
    @endpush
</x-app-layout>
