{{-- resources/views/edit.blade.php --}}
<x-app-layout>
    {{-- Push Cropper.js CSS into the head --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" integrity="sha512-DZy5LprcsI1P5eH58PEf7zkd9gLEkby/2fhsnxobtmWQwB+ukH9hiVO1R6Mfp7p7PfiGqMnWug32d5gD8aY4eQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @endpush

    {{-- Optionally push Alpine.js if you are not using another modal solution --}}
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" integrity="sha512-1ShzRQf+3TOQrOmabUvxjXfUGMqlkwItMBFWSfd4lA54aOZ+IL5XlqFkJ41AoxGw7OLxvL6v9h5USorfeJ5sRw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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
                <form 
                    action="{{ route('dashboard.images.update', $image->id) }}" 
                    method="POST" 
                    enctype="multipart/form-data"
                    id="edit-form"
                >
                    @csrf
                    @method('PATCH')

                    {{-- Current Image Preview --}}
                    <div class="mb-4">
                        <img 
                            id="current-image" 
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

                    {{-- Button to open cropping modal --}}
                    <div class="mb-4">
                        <button 
                            type="button" 
                            id="open-crop-modal" 
                            class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                        >
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
                        <button 
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            Save Changes
                        </button>
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>

            {{-- Delete Button (Below Form) --}}
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
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                    >
                        Delete Image
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Crop Modal using Alpine.js for simplicity --}}
    <div x-data="{ open: false }" x-show="open" 
         class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-75 z-50"
         x-cloak>
        <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-4xl sm:w-full">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Crop Image</h3>
                <div class="w-full">
                    <!-- Cropper image (using current image source) -->
                    <img id="modal-cropper-image" src="{{ Storage::url($image->file_path) }}" alt="Crop Preview" class="w-full object-contain">
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" id="modal-apply-crop" class="mr-2 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Apply Crop
                    </button>
                    <button type="button" @click="open = false" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-gray-700 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Push Cropper.js and Modal Script --}}
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js" integrity="sha512-Ht5eT0DYkaehugpYlYt7pVREIoyNwrA9np8hVwP5HbWSorC/bCq0gI4hHNeTkGugJ2X3Ek4RhPN+1P6/65HS4Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let cropperModal = null;

                // Button to open the modal
                const openCropModalBtn = document.getElementById('open-crop-modal');
                // Modal elements using Alpine.js (populated with x-data)
                const modalElement = document.querySelector('[x-data]');
                const modalApplyBtn = document.getElementById('modal-apply-crop');
                const modalCropperImage = document.getElementById('modal-cropper-image');

                // When the "Crop Current Image" button is clicked, open the modal.
                openCropModalBtn.addEventListener('click', function() {
                    // Using Alpine.js to toggle modal: set open = true
                    modalElement.__x.$data.open = true;
                    // Wait for the modal to display and then initialize Cropper.js on the modal image.
                    if (modalCropperImage.complete) {
                        initializeModalCropper();
                    } else {
                        modalCropperImage.onload = initializeModalCropper;
                    }
                });

                function initializeModalCropper() {
                    if (cropperModal) {
                        cropperModal.destroy();
                    }
                    cropperModal = new Cropper(modalCropperImage, {
                        aspectRatio: 4 / 5,
                        viewMode: 1,
                    });
                }

                // When the "Apply Crop" button in the modal is clicked:
                modalApplyBtn.addEventListener('click', function() {
                    if (cropperModal) {
                        const cropData = cropperModal.getData(true);
                        console.log('Modal Crop Data:', cropData);
                        // Copy crop data from modal to the hidden fields in the form.
                        document.getElementById('crop_x').value = cropData.x;
                        document.getElementById('crop_y').value = cropData.y;
                        document.getElementById('crop_width').value = cropData.width;
                        document.getElementById('crop_height').value = cropData.height;
                        // Destroy the cropper instance.
                        cropperModal.destroy();
                        cropperModal = null;
                        // Close the modal.
                        modalElement.__x.$data.open = false;
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
