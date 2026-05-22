<?php

namespace App\Repositories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VehicleRepository
{
    /**
     * Get vehicle by ID with relationships
     *
     * @param int $id
     * @param array $with
     * @return Vehicle|null
     */
    public function findById(int $id, array $with = []): ?Vehicle
    {
        return Vehicle::with($with)->find($id);
    }

    /**
     * Find vehicle by registration number
     *
     * @param string $regNumber
     * @param array $with
     * @return Vehicle|null
     */
    public function findByRegNumber(string $regNumber, array $with = []): ?Vehicle
    {
        return Vehicle::with($with)->where('regNumber', $regNumber)->first();
    }

    /**
     * Find vehicle by plate number
     *
     * @param string $plateNumber
     * @param array $with
     * @return Vehicle|null
     */
    public function findByPlateNumber(string $plateNumber, array $with = []): ?Vehicle
    {
        return Vehicle::with($with)->where('PlateNumber', $plateNumber)->first();
    }

    /**
     * Get vehicles for a specific applicant
     *
     * @param int $applicantId
     * @param array $with
     * @return Collection
     */
    public function getByApplicantId(int $applicantId, array $with = []): Collection
    {
        return Vehicle::with($with)
            ->where('applicant_id', $applicantId)
            ->orderBy('status', 'desc')
            ->orderBy('last_updated', 'desc')
            ->get();
    }

    /**
     * Get vehicles with search and filters
     *
     * @param array $filters
     * @param int $perPage
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getFiltered(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = Vehicle::with($with);

        $this->applyFilters($query, $filters);

        return $query->orderBy('last_updated', 'desc')->paginate($perPage);
    }

    /**
     * Get active vehicles
     *
     * @param int|null $applicantId
     * @param array $with
     * @return Collection
     */
    public function getActive(int $applicantId = null, array $with = []): Collection
    {
        $query = Vehicle::with($with)->where('status', 'active');

        if ($applicantId) {
            $query->where('applicant_id', $applicantId);
        }

        return $query->orderBy('last_updated', 'desc')->get();
    }

    /**
     * Get vehicles by status
     *
     * @param string $status
     * @param array $with
     * @return Collection
     */
    public function getByStatus(string $status, array $with = []): Collection
    {
        return Vehicle::with($with)
            ->where('status', $status)
            ->orderBy('last_updated', 'desc')
            ->get();
    }

    /**
     * Count vehicles by status
     *
     * @param string|null $status
     * @param int|null $applicantId
     * @return int
     */
    public function countByStatus(?string $status = null, ?int $applicantId = null): int
    {
        $query = Vehicle::query();

        if ($applicantId) {
            $query->where('applicant_id', $applicantId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    /**
     * Create a new vehicle
     *
     * @param array $data
     * @return Vehicle
     */
    public function create(array $data): Vehicle
    {
        return Vehicle::create($data);
    }

    /**
     * Update a vehicle
     *
     * @param Vehicle $vehicle
     * @param array $data
     * @return bool
     */
    public function update(Vehicle $vehicle, array $data): bool
    {
        return $vehicle->update($data);
    }

    /**
     * Delete a vehicle
     *
     * @param Vehicle $vehicle
     * @return bool
     */
    public function delete(Vehicle $vehicle): bool
    {
        return $vehicle->delete();
    }

    /**
     * Check if registration number exists (excluding specific vehicle)
     *
     * @param string $regNumber
     * @param int|null $excludeId
     * @return bool
     */
    public function regNumberExists(string $regNumber, ?int $excludeId = null): bool
    {
        $query = Vehicle::where('regNumber', $regNumber);

        if ($excludeId) {
            $query->where('vehicle_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Bulk update vehicles
     *
     * @param array $conditions
     * @param array $data
     * @return int
     */
    public function bulkUpdate(array $conditions, array $data): int
    {
        return Vehicle::where($conditions)->update($data);
    }

    /**
     * Apply filters to query
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('regNumber', 'like', "%{$search}%")
                  ->orWhere('PlateNumber', 'like', "%{$search}%")
                  ->orWhereHas('applicant', function ($q2) use ($search) {
                      $q2->where('fullName', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['applicant_id'])) {
            $query->where('applicant_id', $filters['applicant_id']);
        }

        if (!empty($filters['make'])) {
            $query->where('make', 'like', "%{$filters['make']}%");
        }

        if (!empty($filters['date_from'])) {
            $query->where('registration_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('registration_date', '<=', $filters['date_to']);
        }
    }
}











