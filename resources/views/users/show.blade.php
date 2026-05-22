@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('users.index') }}" class="text-blue-600 hover:text-blue-800 font-medium mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i>Back to Users
            </a>
            <h1 class="text-4xl font-bold text-gray-900">{{ $user->fullName }}</h1>
            <p class="mt-2 text-gray-600">User Details</p>
        </div>

        <!-- User Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- User Information -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Information</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-semibold text-gray-600">Full Name</label>
                                <p class="text-gray-900">{{ $user->fullName }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-600">Email</label>
                                <p class="text-gray-900">{{ $user->email }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-600">Type</label>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($user->registrantType === 'student') bg-blue-100 text-blue-800
                                    @elseif($user->registrantType === 'staff') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($user->registrantType) }}
                                </span>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-600">Phone</label>
                                <p class="text-gray-900">{{ $user->phone ?? '-' }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-600">Registration No</label>
                                <p class="text-gray-900">{{ $user->studentRegNo ?? $user->staffsRegNo ?? '-' }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-600">College</label>
                                <p class="text-gray-900">{{ $user->college ?? '-' }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-600">ID Number</label>
                                <p class="text-gray-900">{{ $user->idNumber ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicles -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Vehicles ({{ $user->vehicles->count() }})</h2>
                        
                        @if($user->vehicles->count() > 0)
                            <div class="space-y-4">
                                @foreach($user->vehicles as $vehicle)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h3 class="font-semibold text-gray-900">{{ $vehicle->plateNumber }}</h3>
                                        <p class="text-sm text-gray-600">{{ $vehicle->vehicleMake ?? '-' }} {{ $vehicle->vehicleModel ?? '-' }}</p>
                                        <p class="text-sm text-gray-600">Status: 
                                            <span class="font-medium
                                                @if($vehicle->status === 'active') text-green-600
                                                @elseif($vehicle->status === 'pending') text-yellow-600
                                                @else text-red-600
                                                @endif">
                                                {{ ucfirst($vehicle->status) }}
                                            </span>
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-8">No vehicles registered</p>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-8 pt-8 border-t border-gray-200 space-x-4">
                    <a href="{{ route('users.edit', $user->applicant_id) }}" 
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-edit mr-2"></i>Edit User
                    </a>
                    <form method="POST" action="{{ route('users.destroy', $user->applicant_id) }}" 
                          style="display: inline;" 
                          onsubmit="return confirm('Are you sure you want to delete this user?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-trash mr-2"></i>Delete User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
