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
            {{-- Analytics Metrics --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700">Follower Count</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $followerCount }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700">Engagement Rate</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $engagementRate }}%</p>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-700">Total Posts</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $totalPosts }}</p>
                </div>
            </div>

            {{-- Chart for Post Impressions Over Time --}}
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-700 mb-4">Post Impressions Over Time</h3>
                <div class="relative" style="height: 300px;">
                    <canvas id="impressionsChart" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Include Chart.js via CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // The controller should pass the following arrays:
        // $impressionsDates: e.g., ['2025-01-01', '2025-01-02', '2025-01-03', ...]
        // $impressionsData: e.g., [150, 200, 170, ...]
        const ctx = document.getElementById('impressionsChart').getContext('2d');
        const impressionsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($impressionsDates) !!},
                datasets: [{
                    label: 'Impressions',
                    data: {!! json_encode($impressionsData) !!},
                    backgroundColor: 'rgba(37, 99, 235, 0.2)', // Tailwind blue-600 at 20%
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
    </script>
</x-app-layout>
