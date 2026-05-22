<?php

namespace App\Http\Controllers;

use App\Models\AuthorizedDriver;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $action = strtolower((string) $request->input('action', ''));
        if ($action === 'update') {
            $id = (int) $request->input('driver_id', $request->input('id', 0));
            return $this->update($request, $id);
        }
        if (in_array($action, ['delete', 'remove'], true)) {
            $id = (int) $request->input('driver_id', $request->input('id', 0));
            return $this->destroy($id);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'fullname' => 'required|string|max:255',
            'licenseNumber' => 'required|string|max:50',
            'contact' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $vehicleId = $request->input('vehicle_id');

        if ($vehicleId) {
            $vehicle = Vehicle::where('vehicle_id', $vehicleId)
                ->where('applicant_id', $userId)
                ->first();

            if (!$vehicle) {
                return response()->json(['success' => false, 'message' => 'Vehicle not found or access denied.'], 403);
            }
        }

        $driver = AuthorizedDriver::create([
            'applicant_id' => $userId,
            'vehicle_id' => $vehicleId,
            'fullname' => $request->input('fullname'),
            'licenseNumber' => $request->input('licenseNumber'),
            'contact' => $request->input('contact'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Driver added successfully.',
            'driver' => $driver,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        $id = (int) $id;
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid driver ID.'], 400);
        }

        try {
            $driver = AuthorizedDriver::where('Id', $id)
                ->where(function ($q) use ($userId) {
                    $q->where('applicant_id', $userId)
                      ->orWhereHas('vehicle', function ($q2) use ($userId) {
                          $q2->where('applicant_id', $userId);
                      });
                })
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Driver not found or not authorized.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'licenseNumber' => 'required|string|max:50',
            'contact' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $driver->update($request->only(['fullname', 'licenseNumber', 'contact']));

        return response()->json([
            'success' => true,
            'message' => 'Driver updated successfully.',
            'driver' => $driver,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        $id = (int) $id;
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid driver ID.'], 400);
        }

        try {
            $driver = AuthorizedDriver::where('Id', $id)
                ->where(function ($q) use ($userId) {
                    $q->where('applicant_id', $userId)
                      ->orWhereHas('vehicle', function ($q2) use ($userId) {
                          $q2->where('applicant_id', $userId);
                      });
                })
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Driver not found or not authorized.'], 404);
        }

        $driver->delete();

        return response()->json([
            'success' => true,
            'message' => 'Driver deleted successfully.',
        ]);
    }
}
