<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\Applicant;
use App\Models\AuthorizedDriver;
use App\Models\SearchLog;
use App\Models\UnregisteredPlate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;
use App\Exceptions\VehicleRegistrationException;

class VehicleService
{
    public function __construct(private VehicleRepository $vehicleRepository) {}

    /**
     * Register a new vehicle for a user
     *
     * @param Applicant $user
     * @param array $data
     * @return Vehicle
     * @throws VehicleRegistrationException
     */
    public function registerVehicle(Applicant $user, array $data): Vehicle
    {
        if (!$this->canRegisterVehicle($user)) {
            throw VehicleRegistrationException::permissionDenied('register vehicles');
        }

        $vehicleCount = $this->getUserVehicleCount($user);
        if ($vehicleCount >= $user->max_vehicles && $user->max_vehicles > 0) {
            throw VehicleRegistrationException::vehicleLimitExceeded($vehicleCount, $user->max_vehicles);
        }

        // Check if registration number already exists
        if (Vehicle::where('regNumber', $data['regNumber'])->exists()) {
            throw VehicleRegistrationException::registrationNumberExists($data['regNumber']);
        }

        $status = $user->isStudent() ? 'active' : 'pending';

        DB::beginTransaction();
        try {
            $expiryDays = (int) config('app.vehicle_registration_expiry_days', 365);

            $vehicle = Vehicle::create([
                'applicant_id'        => $user->applicant_id,
                'make'                => $data['make'],
                'model'               => $data['model'] ?? null,
                'regNumber'           => $data['regNumber'],
                'PlateNumber'         => $data['PlateNumber'] ?? null,
                'owner'               => $data['owner'] ?? null,
                'address'             => $data['address'] ?? null,
                'status'              => $status,
                'registration_date'   => now(),
                'registration_expiry' => now()->addDays($expiryDays)->toDateString(),
                'last_updated'        => now(),
            ]);

            // If student, deactivate other vehicles
            if ($user->isStudent()) {
                Vehicle::where('applicant_id', $user->applicant_id)
                    ->where('vehicle_id', '!=', $vehicle->vehicle_id)
                    ->update(['status' => 'inactive', 'last_updated' => now()]);
            }

            DB::commit();

            // Clear user-specific caches
            $this->clearUserCache($user->applicant_id);

            return $vehicle;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new VehicleRegistrationException(
                'Failed to register vehicle. Please try again.',
                'REGISTRATION_FAILED',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Update an existing vehicle
     *
     * @param Vehicle $vehicle
     * @param array $data
     * @return Vehicle
     * @throws ValidationException
     */
    public function updateVehicle(Vehicle $vehicle, array $data): Vehicle
    {
        try {
            $vehicle->update([
                'make' => $data['make'],
                'model' => $data['model'] ?? $vehicle->model,
                'regNumber' => $data['regNumber'],
                'PlateNumber' => $data['PlateNumber'] ?? $vehicle->PlateNumber,
                'owner' => $data['owner'] ?? $vehicle->owner,
                'address' => $data['address'] ?? $vehicle->address,
                'last_updated' => now(),
            ]);

            return $vehicle;
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'error' => 'Failed to update vehicle. Please try again.'
            ]);
        }
    }

    /**
     * Delete a vehicle
     *
     * @param Vehicle $vehicle
     * @param Applicant $user
     * @return bool
     * @throws ValidationException
     */
    public function deleteVehicle(Vehicle $vehicle, Applicant $user): bool
    {
        $wasActive = $vehicle->isActive();

        DB::beginTransaction();
        try {
            $vehicle->delete();

            // If the deleted vehicle was active, activate the most recent one
            if ($wasActive) {
                $latestVehicle = Vehicle::where('applicant_id', $user->applicant_id)
                    ->orderBy('last_updated', 'desc')
                    ->first();

                if ($latestVehicle) {
                    $latestVehicle->update(['status' => 'active', 'last_updated' => now()]);
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' => 'Failed to delete vehicle. Please try again.'
            ]);
        }
    }

    /**
     * Search for a vehicle by plate number
     *
     * @param string $plateNumber
     * @param int|null $userId
     * @return array
     */
    public function searchByPlateNumber(string $plateNumber, ?int $userId = null): array
    {
        // Log every search once, regardless of cache state
        $this->logSearch($userId, 'plate', $plateNumber);

        $cacheKey = "vehicle_search_plate:{$plateNumber}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $vehicle = $this->vehicleRepository->findByPlateNumber($plateNumber, ['applicant', 'authorizedDrivers']);

        if ($vehicle) {
            $result = [
                'found' => true,
                'vehicle' => $vehicle,
            ];
        } else {
            // Record unregistered plate
            UnregisteredPlate::firstOrCreate(
                ['plate_number' => $plateNumber],
                ['detected_at' => now()]
            );

            $result = [
                'found' => false,
                'plateNumber' => $plateNumber,
            ];
        }

        // Cache the result
        $ttl = config('performance.cache.ttl.search_results', 15);
        Cache::put($cacheKey, $result, now()->addMinutes($ttl));

        return $result;
    }

    /**
     * Log a search operation
     *
     * @param int|null $userId
     * @param string $searchType
     * @param string $searchTerm
     * @return void
     */
    private function logSearch(?int $userId, string $searchType, string $searchTerm): void
    {
        if ($userId) {
            SearchLog::create([
                'user_id' => $userId,
                'search_type' => $searchType,
                'search_term' => $searchTerm,
                'search_date' => now(),
            ]);
        }
    }

    /**
     * Clear user-specific cache
     *
     * @param int $userId
     * @return void
     */
    private function clearUserCache(int $userId): void
    {
        $cacheKeys = [
            "user_vehicle_count:{$userId}",
            "user_vehicles:{$userId}",
            "user_active_vehicles:{$userId}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get vehicles for a specific user
     *
     * @param Applicant $user
     * @return Collection
     */
    public function getUserVehicles(Applicant $user): Collection
    {
        $cacheKey = "user_vehicles:{$user->applicant_id}";
        $ttl = config('performance.cache.ttl.user_stats', 5);

        return Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($user) {
            return $user->vehicles()
                ->with('authorizedDrivers')
                ->orderBy('status', 'desc')
                ->orderBy('last_updated', 'desc')
                ->get();
        });
    }

    /**
     * Check if user can register a vehicle
     *
     * @param Applicant $user
     * @return bool
     */
    public function canRegisterVehicle(Applicant $user): bool
    {
        return $user->canRegisterVehicles();
    }

    /**
     * Get user's current vehicle count
     *
     * @param Applicant $user
     * @return int
     */
    public function getUserVehicleCount(Applicant $user): int
    {
        $cacheKey = "user_vehicle_count:{$user->applicant_id}";
        $ttl = config('performance.cache.ttl.vehicle_counts', 10);

        return Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($user) {
            return $user->vehicles()->count();
        });
    }

    /**
     * Renew a vehicle registration by extending its expiry date
     *
     * @param Vehicle $vehicle
     * @param Applicant $user
     * @return Vehicle
     * @throws VehicleRegistrationException
     */
    public function renewVehicle(Vehicle $vehicle, Applicant $user): Vehicle
    {
        if ($vehicle->applicant_id !== $user->applicant_id) {
            throw VehicleRegistrationException::permissionDenied('renew this vehicle');
        }

        $expiryDays = (int) config('app.vehicle_registration_expiry_days', 365);

        // If already expired, renew from today; if still valid, extend from current expiry
        $baseDate = ($vehicle->isExpired() || !$vehicle->registration_expiry)
            ? now()
            : $vehicle->registration_expiry->startOfDay();

        $vehicle->update([
            'registration_expiry' => $baseDate->addDays($expiryDays)->toDateString(),
            'last_renewed_at'     => now()->toDateString(),
            'last_updated'        => now(),
        ]);

        $this->clearUserCache($user->applicant_id);

        return $vehicle->fresh();
    }

    /**
     * Toggle vehicle status (activate/deactivate)
     *
     * @param Vehicle $vehicle
     * @param string $newStatus
     * @return Vehicle
     * @throws ValidationException
     */
    public function toggleStatus(Vehicle $vehicle, string $newStatus): Vehicle
    {
        if (!in_array($newStatus, ['active', 'inactive', 'pending'])) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status provided.'
            ]);
        }

        // If activating, deactivate other vehicles for students
        if ($newStatus === 'active' && $vehicle->applicant->isStudent()) {
            Vehicle::where('applicant_id', $vehicle->applicant_id)
                ->where('vehicle_id', '!=', $vehicle->vehicle_id)
                ->update(['status' => 'inactive', 'last_updated' => now()]);
        }

        $vehicle->update([
            'status' => $newStatus,
            'last_updated' => now()
        ]);

        return $vehicle;
    }
}
