<x-app-layout>
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-4xl font-bold text-gray-900">My Registered Vehicles</h1>
            <p class="text-gray-600 mt-2">Manage and view all your registered vehicles</p>
        </div>
        <a href="{{ route('vehicles.create') }}" class="btn-primary flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Add New Vehicle</span>
        </a>
    </div>

    @if($vehicles->count() > 0)
        <!-- Vehicles Grid -->
        <div class="grid grid-cols-1 gap-6">
            <!-- Table View -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200 bg-gray-50">
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Vehicle</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Registration Number</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Last Updated</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehicles as $vehicle)
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition duration-200">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="bg-blue-100 p-3 rounded-lg">
                                                <i class="fas fa-car text-blue-600 text-lg"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ $vehicle->make }} {{ $vehicle->model ?? '' }}</p>
                                                <p class="text-sm text-gray-500">{{ $vehicle->year ?? 'N/A' }} • {{ $vehicle->color ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $vehicle->regNumber }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        @php
                                            $statusClass = match($vehicle->status) {
                                                'active' => 'bg-green-100 text-green-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'inactive' => 'bg-gray-100 text-gray-800',
                                                'expired' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                        @endphp
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                                            {{ ucfirst($vehicle->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $vehicle->updated_at ? $vehicle->updated_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('vehicles.show', $vehicle->vehicle_id) }}" 
                                               class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition duration-200 font-medium text-xs">
                                                <i class="fas fa-eye mr-1"></i>View
                                            </a>
                                            <a href="#" 
                                               class="inline-flex items-center px-3 py-2 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition duration-200 font-medium text-xs">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <button onclick="if(confirm('Delete this vehicle?')) fetch('{{ route('vehicles.destroy', $vehicle->vehicle_id) }}', {method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(() => location.reload())"
                                                    class="inline-flex items-center px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition duration-200 font-medium text-xs">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="card text-center py-16">
            <div class="inline-block bg-gray-100 p-6 rounded-full mb-6">
                <i class="fas fa-car text-5xl text-gray-400"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">No Vehicles Registered</h3>
            <p class="text-gray-600 mb-8">You haven't registered any vehicles yet. Start by adding your first vehicle.</p>
            <a href="{{ route('vehicles.create') }}" class="btn-primary inline-flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Register Your First Vehicle</span>
            </a>
        </div>
    @endif
</x-app-layout>
