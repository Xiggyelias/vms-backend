<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Vehicle;
use App\Services\VehicleService;
use App\Repositories\VehicleRepository;
use App\Http\Requests\VehicleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VehicleController extends Controller
{
    protected VehicleService $vehicleService;
    protected VehicleRepository $vehicleRepository;

    public function __construct(VehicleService $vehicleService, VehicleRepository $vehicleRepository)
    {
        $this->vehicleService    = $vehicleService;
        $this->vehicleRepository = $vehicleRepository;
    }

    public function index(Request $request): View
    {
        $filters = [
            'search'       => $request->input('search'),
            'status'       => $request->input('status'),
            'applicant_id' => session('user_id'),
        ];

        $vehicles = $this->vehicleRepository->getFiltered($filters, 20, ['applicant']);

        return view('vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        $userId = session('user_id');
        $user   = Applicant::find($userId);

        if (!$user || !$this->vehicleService->canRegisterVehicle($user)) {
            abort(403, 'You do not have permission to register vehicles.');
        }

        return view('vehicles.create', compact('user'));
    }

    public function store(VehicleRequest $request): RedirectResponse
    {
        $userId = session('user_id');
        $user   = Applicant::find($userId);

        if (!$user || !$this->vehicleService->canRegisterVehicle($user)) {
            return back()->with('error', 'You do not have permission to register vehicles.');
        }

        try {
            $this->vehicleService->registerVehicle($user, $request->only([
                'make', 'model', 'regNumber', 'PlateNumber', 'owner', 'address',
            ]));

            return redirect()->route('dashboard.user')->with('success', 'Vehicle registered successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to register vehicle. Please try again.')->withInput();
        }
    }

    public function show(Request $request, ?int $id = null): View
    {
        $id = $id ?? (int) $request->query('id', 0);
        if ($id <= 0) {
            abort(404, 'Vehicle not found.');
        }

        $vehicle = $this->vehicleRepository->findById($id, ['applicant', 'authorizedDrivers']);

        if (!$vehicle) {
            abort(404, 'Vehicle not found.');
        }

        $userId  = (int) session('user_id');
        $isAdmin = (bool) session('is_admin');
        if (!$isAdmin && $vehicle->applicant_id !== $userId) {
            abort(403, 'You do not have permission to view this vehicle.');
        }

        return view('vehicles.show', compact('vehicle'));
    }

    public function update(VehicleRequest $request, ?int $id = null): RedirectResponse|JsonResponse
    {
        $id = $id ?? (int) $request->input('id', $request->input('vehicle_id', 0));
        if ($id <= 0) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Invalid vehicle ID.'], 400);
            }
            return back()->with('error', 'Invalid vehicle ID.');
        }

        $userId  = session('user_id');
        $vehicle = $this->vehicleRepository->findById($id);

        if (!$vehicle || $vehicle->applicant_id !== $userId) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to update this vehicle.'], 403);
            }
            abort(403, 'You do not have permission to update this vehicle.');
        }

        try {
            $this->vehicleService->updateVehicle($vehicle, $request->only([
                'make', 'model', 'regNumber', 'PlateNumber', 'owner', 'address',
            ]));

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Vehicle updated successfully.']);
            }
            return back()->with('success', 'Vehicle updated successfully.');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update vehicle. Please try again.'], 500);
            }
            return back()->with('error', 'Failed to update vehicle. Please try again.')->withInput();
        }
    }

    public function destroy(Request $request, ?int $id = null): RedirectResponse|JsonResponse
    {
        $id = $id ?? (int) $request->input('id', $request->input('vehicle_id', 0));
        if ($id <= 0) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Invalid vehicle ID.'], 400);
            }
            return back()->with('error', 'Invalid vehicle ID.');
        }

        $userId  = session('user_id');
        $vehicle = $this->vehicleRepository->findById($id);

        if (!$vehicle || $vehicle->applicant_id !== $userId) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to delete this vehicle.'], 403);
            }
            abort(403, 'You do not have permission to delete this vehicle.');
        }

        try {
            $user = Applicant::find($userId);
            $this->vehicleService->deleteVehicle($vehicle, $user);

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Vehicle deleted successfully.']);
            }
            return redirect()->route('dashboard.user')->with('success', 'Vehicle deleted successfully.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete vehicle. Please try again.'], 500);
            }
            return back()->with('error', 'Failed to delete vehicle. Please try again.');
        }
    }

    public function search(Request $request): JsonResponse
    {
        $plateNumber = trim((string) (
            $request->input('plateNumber')
            ?? $request->input('plate_number')
            ?? $request->input('query')
            ?? $request->input('search_value')
            ?? ''
        ));

        if ($plateNumber === '') {
            return response()->json([
                'success'      => false,
                'isRegistered' => false,
                'message'      => 'Plate number is required.',
            ], 422);
        }

        $userId = session('user_id');
        if (!$userId) {
            return response()->json([
                'success'      => false,
                'isRegistered' => false,
                'message'      => 'Authentication required.',
            ], 401);
        }

        try {
            $result = $this->vehicleService->searchByPlateNumber($plateNumber, $userId);

            if (!($result['found'] ?? false)) {
                return response()->json([
                    'success'      => true,
                    'isRegistered' => false,
                    'message'      => 'No registered vehicle found for this plate.',
                    'plateNumber'  => $plateNumber,
                ]);
            }

            return response()->json([
                'success'      => true,
                'isRegistered' => true,
                'data'         => $this->serializeVehicle($result['vehicle']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success'      => false,
                'isRegistered' => false,
                'message'      => 'Failed to search vehicle.',
            ], 500);
        }
    }

    public function scan(Request $request): JsonResponse
    {
        return $this->search($request);
    }

    public function renew(Request $request, int $id): JsonResponse
    {
        $userId  = (int) session('user_id');
        $vehicle = $this->vehicleRepository->findById($id);

        if (!$vehicle || $vehicle->applicant_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Vehicle not found or access denied.'], 403);
        }

        try {
            $user    = Applicant::find($userId);
            $renewed = $this->vehicleService->renewVehicle($vehicle, $user);

            return response()->json([
                'success'             => true,
                'message'             => 'Registration renewed successfully.',
                'registration_expiry' => $renewed->registration_expiry?->format('M d, Y'),
                'renewal_status'      => $renewed->renewalStatus(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to renew registration.'], 500);
        }
    }

    protected function serializeVehicle(Vehicle $vehicle): array
    {
        return [
            'vehicle_id'         => $vehicle->vehicle_id,
            'applicant_id'       => $vehicle->applicant_id,
            'regNumber'          => $vehicle->regNumber,
            'PlateNumber'        => $vehicle->PlateNumber,
            'make'               => $vehicle->make,
            'model'              => $vehicle->model,
            'owner'              => $vehicle->owner,
            'address'            => $vehicle->address,
            'registration_date'  => $vehicle->registration_date,
            'disk_number'        => $vehicle->disk_number,
            'status'             => $vehicle->status,
            'authorized_drivers' => $vehicle->authorizedDrivers?->toArray() ?? [],
        ];
    }
}
