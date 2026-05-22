<x-app-layout>
    <!-- Header Section -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-2">Welcome back, <span class="font-semibold text-primary">{{ session('user_name') }}</span>!</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Vehicles Card -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Vehicles</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $vehicles->count() }}</p>
                </div>
                <div class="bg-blue-100 p-4 rounded-lg">
                    <i class="fas fa-car text-2xl text-blue-600"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-4">All vehicles registered</p>
        </div>

        <!-- Active Vehicles Card -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Active Vehicles</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $vehicles->where('status', 'active')->count() }}</p>
                </div>
                <div class="bg-green-100 p-4 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-4">Currently operational</p>
        </div>

        <!-- Authorized Drivers Card -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Authorized Drivers</p>
                    <p class="text-3xl font-bold text-primary mt-2">{{ $drivers->count() }}</p>
                </div>
                <div class="bg-red-100 p-4 rounded-lg">
                    <i class="fas fa-users text-2xl text-primary"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-4">Total drivers</p>
        </div>

        <!-- Pending Approvals Card -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Pending Approvals</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $vehicles->where('status', 'pending')->count() }}</p>
                </div>
                <div class="bg-yellow-100 p-4 rounded-lg">
                    <i class="fas fa-hourglass-half text-2xl text-yellow-600"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-4">Awaiting approval</p>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="card border-2 border-primary border-opacity-20 hover:border-opacity-100 transition duration-300 cursor-pointer">
            <a href="{{ route('vehicles.create') }}" class="flex flex-col items-center text-center py-2">
                <div class="bg-primary bg-opacity-10 p-4 rounded-lg mb-4">
                    <i class="fas fa-plus text-3xl text-primary"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">Register New Vehicle</h3>
                <p class="text-sm text-gray-600">Add a new vehicle to your account</p>
            </a>
        </div>

        <div class="card border-2 border-blue-500 border-opacity-20 hover:border-opacity-100 transition duration-300 cursor-pointer">
            <a href="{{ route('vehicles.index') }}" class="flex flex-col items-center text-center py-2">
                <div class="bg-blue-100 p-4 rounded-lg mb-4">
                    <i class="fas fa-list text-3xl text-blue-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">View All Vehicles</h3>
                <p class="text-sm text-gray-600">Manage your registered vehicles</p>
            </a>
        </div>

        <div class="card border-2 border-green-500 border-opacity-20 hover:border-opacity-100 transition duration-300 cursor-pointer">
            <a href="{{ route('vehicles.search') }}" class="flex flex-col items-center text-center py-2">
                <div class="bg-green-100 p-4 rounded-lg mb-4">
                    <i class="fas fa-search text-3xl text-green-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">Search Vehicle</h3>
                <p class="text-sm text-gray-600">Find vehicle information</p>
            </a>
        </div>
    </div>

    <!-- Recent Vehicles Section -->
    <div class="card">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Your Vehicles</h2>
            <a href="{{ route('vehicles.index') }}" class="text-primary hover:text-primary-dark text-sm font-semibold transition duration-200">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        @if($vehicles->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Registration</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Vehicle</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Last Updated</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehicles->take(5) as $vehicle)
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition duration-200">
                                <td class="px-4 py-4 text-sm font-semibold text-gray-900">
                                    {{ $vehicle->regNumber }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    {{ $vehicle->make }} <span class="font-medium">{{ $vehicle->model ?? 'N/A' }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        {{ $vehicle->status === 'active' ? 'bg-green-100 text-green-800' : ($vehicle->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($vehicle->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    {{ $vehicle->updated_at ? $vehicle->updated_at->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <div class="flex space-x-3">
                                        <a href="{{ route('vehicles.show', $vehicle->vehicle_id) }}" class="text-primary hover:text-primary-dark font-medium transition duration-200">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                        <a href="#" class="text-blue-600 hover:text-blue-800 font-medium transition duration-200">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <div class="inline-block bg-gray-100 p-6 rounded-full mb-4">
                    <i class="fas fa-car text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Vehicles Registered Yet</h3>
                <p class="text-gray-600 mb-6">Start by registering your first vehicle to get the ball rolling.</p>
                <a href="{{ route('vehicles.create') }}" class="btn-primary inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>Register Your First Vehicle
                </a>
            </div>
        @endif
    </div>
</x-app-layout>











