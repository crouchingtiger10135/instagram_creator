{{-- resources/views/instagram-analytics.blade.php --}}
<x-app-layout>
    {{-- PAGE HEADER --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Instagram Analytics') }}
        </h2>
    </x-slot>

    {{-- MAIN CONTENT WRAPPER --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Primary Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Follower Count -->
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700">Follower Count</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $followerCount }}</p>
                </div>
                <!-- Engagement Rate -->
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700">Engagement Rate</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $engagementRate }}%</p>
                </div>
                <!-- Total Posts -->
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700">Total Posts</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $totalPosts }}</p>
                </div>
            </div>

            <!-- Additional Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Average Likes per Post -->
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700">Average Likes per Post</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $averageLikes }}</p>
                </div>
                <!-- Average Comments per Post -->
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700">Average Comments per Post</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $averageComments }}</p>
                </div>
                <!-- Profile Views -->
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700">Profile Views</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $profileViews }}</p>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Impressions Over Time Chart -->
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Impressions Over Time</h3>
                    <div class="relative" style="height: 300px;">
                        <canvas id="impressionsChart" class="w-full h-full"></canvas>
                    </div>
                </div>
                <!-- Reach Over Time Chart -->
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Reach Over Time</h3>
                    <div class="relative" style="height: 300px;">
                        <canvas id="reachChart" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Impressions Chart Setup
        const impressionsCtx = document.getElementById('impressionsChart').getContext('2d');
        const impressionsChart = new Chart(impressionsCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($impressionsDates) !!},
                datasets: [{
                    label: 'Impressions',
                    data: {!! json_encode($impressionsData) !!},
                    backgroundColor: 'rgba(37, 99, 235, 0.2)', // Tailwind blue-600 (20% opacity)
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'MMM DD, YYYY'
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Impressions'
                        }
                    }
                }
            }
        });

        // Reach Chart Setup
        const reachCtx = document.getElementById('reachChart').getContext('2d');
        const reachChart = new Chart(reachCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($impressionsDates) !!},
                datasets: [{
                    label: 'Reach',
                    data: {!! json_encode($reachData) !!},
                    backgroundColor: 'rgba(16, 185, 129, 0.2)', // Tailwind green-500 (20% opacity)
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'MMM DD, YYYY'
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Reach'
                        }
                    }
                }
            }
        });
    </script>
</x-app-layout>
