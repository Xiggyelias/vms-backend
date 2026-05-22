<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h1 class="ml-2 text-2xl font-medium text-gray-900">
                            Admin Dashboard
                        </h1>
                    </div>

                    <p class="mt-6 text-gray-500 leading-relaxed">
                        Monitor and manage the Vehicle Registration System.
                    </p>
                </div>

                <div class="bg-gray-200 bg-opacity-25 grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 p-6 lg:p-8">
                    <!-- Total Users -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-500 rounded-md">
                                <i class="fas fa-users text-white"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $totalUsers ?? 0 }}</h3>
                                <p class="text-gray-500 text-sm">Total Users</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Vehicles -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-500 rounded-md">
                                <i class="fas fa-car text-white"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $totalVehicles ?? 0 }}</h3>
                                <p class="text-gray-500 text-sm">Total Vehicles</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Drivers -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-500 rounded-md">
                                <i class="fas fa-id-card text-white"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $total_drivers ?? 0 }}</h3>
                                <p class="text-gray-500 text-sm">Authorized Drivers</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="p-6 lg:p-8 border-t border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('users.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-lg text-center">
                            <i class="fas fa-users text-2xl mb-2"></i>
                            <div>Manage Users</div>
                        </a>
                        <a href="{{ route('vehicles.index') }}" class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-lg text-center">
                            <i class="fas fa-car text-2xl mb-2"></i>
                            <div>View Vehicles</div>
                        </a>
                        <a href="{{ route('reports.index') }}" class="bg-red-500 hover:bg-red-600 text-white p-4 rounded-lg text-center">
                            <i class="fas fa-file-alt text-2xl mb-2"></i>
                            <div>View Reports</div>
                        </a>
                        <a href="{{ route('owners.index') }}" class="bg-purple-500 hover:bg-purple-600 text-white p-4 rounded-lg text-center">
                            <i class="fas fa-address-card text-2xl mb-2"></i>
                            <div>Manage Owners</div>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="p-6 lg:p-8 border-t border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
                    <div class="space-y-4">
                        @if(isset($recentVehicles) && count($recentVehicles) > 0)
                            @foreach($recentVehicles as $vehicle)
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-car text-green-500"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">
                                            New vehicle registered: {{ $vehicle->regNumber }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Owner: {{ $vehicle->applicant->fullName ?? 'Unknown' }} • {{ $vehicle->registration_date->format('M d, Y h:i A') }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8">
                                <i class="fas fa-chart-line text-gray-400 text-4xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No recent activity</h3>
                                <p class="text-gray-500">Recent vehicle registrations will appear here.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>











