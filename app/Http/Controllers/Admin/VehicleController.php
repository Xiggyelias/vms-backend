<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\VehicleController as BaseVehicleController;
use App\Models\Notification;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends BaseVehicleController
{
    public function adminStatusUpdate(Request $request): JsonResponse
    {
        $vehicleId = (int) $request->input('vehicle_id', 0);
        $newStatus = strtolower(trim((string) $request->input('new_status', '')));

        if ($vehicleId <= 0) {
            return $this->fail('Invalid vehicle ID.', 400);
        }
        if (!in_array($newStatus, ['active', 'inactive'], true)) {
            return $this->fail('Invalid status.', 422);
        }

        $vehicle = $this->vehicleRepository->findById($vehicleId, ['applicant']);
        if (!$vehicle) {
            return $this->fail('Vehicle not found.', 404);
        }

        try {
            $this->vehicleService->toggleStatus($vehicle, $newStatus);
            return $this->ok([], 'Vehicle status updated successfully.');
        } catch (\Throwable $e) {
            return $this->fail('Failed to update vehicle status.', 500);
        }
    }

    public function bulkApprove(Request $request): JsonResponse
    {
        $ids = $request->input('vehicle_ids', []);

        if (!is_array($ids) || empty($ids)) {
            return $this->fail('No vehicle IDs provided.', 422);
        }

        $ids = array_values(array_filter(array_map('intval', $ids), fn ($id) => $id > 0));

        if (empty($ids)) {
            return $this->fail('No valid vehicle IDs provided.', 422);
        }

        $updated = Vehicle::whereIn('vehicle_id', $ids)
            ->where('status', 'pending')
            ->update(['status' => 'active', 'last_updated' => now()]);

        $approved = Vehicle::with('applicant')->whereIn('vehicle_id', $ids)->get();
        foreach ($approved as $vehicle) {
            if ($vehicle->applicant) {
                Notification::notifyUser(
                    $vehicle->applicant->applicant_id,
                    'Vehicle Approved',
                    "Your vehicle ({$vehicle->PlateNumber} — {$vehicle->make} {$vehicle->model}) has been approved and is now active.",
                    'success',
                    url("/vehicle-details.php?id={$vehicle->vehicle_id}")
                );
            }
        }

        return $this->ok(['updated' => $updated], "{$updated} vehicle(s) approved successfully.");
    }
}
