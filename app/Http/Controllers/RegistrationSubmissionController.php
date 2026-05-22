<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AuthorizedDriver;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegistrationSubmissionController extends Controller
{
    public function submit(Request $request)
    {
        $userId = (int) session('user_id');
        if ($userId <= 0) {
            return $this->fail('Not authenticated.', 401);
        }

        $applicant = Applicant::find($userId);
        if (!$applicant) {
            return $this->fail('Applicant not found.', 404);
        }

        $vehiclesRaw = $request->input('vehicles');
        $vehicles = is_string($vehiclesRaw) ? json_decode($vehiclesRaw, true) : $vehiclesRaw;
        if (!is_array($vehicles) || empty($vehicles)) {
            return $this->fail('At least one vehicle is required.', 422);
        }

        $validator = Validator::make($request->all(), [
            'registrantType' => 'nullable|in:student,staff,guest,pending',
            'studentRegNo' => 'nullable|string|max:50',
            'staffsregno' => 'nullable|string|max:50',
            'college' => 'nullable|string|max:255',
            'idNumber' => 'nullable|string|max:50',
            'licenseNumber' => 'nullable|string|max:50',
            'licenseClass' => 'nullable|string|max:10',
            'licenseDate' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {
            $applicant->update([
                'registrantType' => $request->input('registrantType', $applicant->registrantType),
                'studentRegNo' => $request->input('studentRegNo', $applicant->studentRegNo),
                'staffsRegNo' => $request->input('staffsregno', $applicant->staffsRegNo),
                'college' => $request->input('college', $applicant->college),
                'idNumber' => $request->input('idNumber', $applicant->idNumber),
                'licenseNumber' => $request->input('licenseNumber', $applicant->licenseNumber),
                'licenseClass' => $request->input('licenseClass', $applicant->licenseClass),
                'licenseDate' => $request->input('licenseDate', $applicant->licenseDate),
            ]);

            $createdVehicles = [];
            foreach ($vehicles as $vehicleInput) {
                $v = Validator::make((array) $vehicleInput, [
                    'regNumber' => 'required|string|max:50',
                    'make' => 'required|string|max:255',
                    'owner' => 'nullable|string|max:255',
                    'address' => 'nullable|string|max:500',
                    'PlateNumber' => 'nullable|string|max:20',
                ]);
                if ($v->fails()) {
                    throw new \RuntimeException('Invalid vehicle payload.');
                }

                if (Vehicle::where('regNumber', $vehicleInput['regNumber'])->exists()) {
                    throw new \RuntimeException('Registration number already exists: ' . $vehicleInput['regNumber']);
                }

                $vehicle = Vehicle::create([
                    'applicant_id' => $applicant->applicant_id,
                    'regNumber' => $vehicleInput['regNumber'],
                    'make' => $vehicleInput['make'],
                    'model' => $vehicleInput['model'] ?? null,
                    'owner' => $vehicleInput['owner'] ?? $applicant->fullName,
                    'address' => $vehicleInput['address'] ?? null,
                    'PlateNumber' => $vehicleInput['PlateNumber'] ?? null,
                    'status' => $applicant->registrantType === 'student' ? 'active' : 'pending',
                    'registration_date' => now(),
                    'last_updated' => now(),
                ]);

                foreach ((array) ($vehicleInput['drivers'] ?? []) as $driver) {
                    if (empty($driver['fullName']) || empty($driver['licenseNumber'])) {
                        continue;
                    }
                    AuthorizedDriver::create([
                        'applicant_id' => $applicant->applicant_id,
                        'vehicle_id' => $vehicle->vehicle_id,
                        'fullname' => (string) $driver['fullName'],
                        'licenseNumber' => (string) $driver['licenseNumber'],
                        'contact' => (string) ($driver['contact'] ?? ''),
                    ]);
                }

                $createdVehicles[] = $vehicle->vehicle_id;
            }

            DB::commit();
            return $this->ok([
                'data' => [
                    'vehicles_created' => count($createdVehicles),
                    'vehicle_ids' => $createdVehicles,
                ],
            ], 'Registration completed successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail('Registration failed.', 422, [
                'error' => config('app.debug') ? $e->getMessage() : 'Unable to submit registration.',
            ]);
        }
    }

    public function submitVehicleForm(Request $request)
    {
        $userId = (int) session('user_id');
        if ($userId <= 0) {
            return $request->expectsJson()
                ? $this->fail('Not authenticated.', 401)
                : redirect()->route('auth.login')->with('error', 'Please log in to continue.');
        }

        $applicant = Applicant::find($userId);
        if (!$applicant) {
            return $request->expectsJson()
                ? $this->fail('Applicant not found.', 404)
                : back()->with('error', 'Applicant not found.')->withInput();
        }

        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:255',
            'college' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'idNumber' => 'required|string|max:50',
            'licenseNumber' => 'required|string|max:50',
            'licenseClass' => 'required|string|max:10',
            'licenseDate' => 'required|date',
            'vehicleRegistrationNumber' => 'required|string|max:50',
            'vehicleMake' => 'required|string|max:255',
            'vehicleModel' => 'nullable|string|max:255',
            'registeredOwner' => 'required|string|max:255',
            'vehicleAddress' => 'required|string|max:500',
            'plateNumber' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->fail('Validation failed.', 422, ['errors' => $validator->errors()]);
            }
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $applicant->update([
                'fullName' => $request->input('fullName'),
                'college' => $request->input('college'),
                'phone' => $request->input('phone'),
                'idNumber' => $request->input('idNumber'),
                'licenseNumber' => $request->input('licenseNumber'),
                'licenseClass' => $request->input('licenseClass'),
                'licenseDate' => $request->input('licenseDate'),
            ]);

            $regNumber = $request->input('vehicleRegistrationNumber');
            if (Vehicle::where('regNumber', $regNumber)->exists()) {
                throw new \RuntimeException('This registration number is already registered.');
            }

            $vehicle = Vehicle::create([
                'applicant_id' => $applicant->applicant_id,
                'regNumber' => $regNumber,
                'make' => $request->input('vehicleMake'),
                'model' => $request->input('vehicleModel'),
                'owner' => $request->input('registeredOwner'),
                'address' => $request->input('vehicleAddress'),
                'PlateNumber' => $request->input('plateNumber'),
                'status' => strtolower((string) $applicant->registrantType) === 'student' ? 'active' : 'pending',
                'registration_date' => now(),
                'last_updated' => now(),
            ]);

            foreach ((array) $request->input('drivers', []) as $driverId => $driverData) {
                $fullName = trim((string) ($driverData['fullName'] ?? ''));
                $licenseNumber = trim((string) ($driverData['licenseNumber'] ?? ''));
                $contact = trim((string) ($driverData['contactInfo'] ?? ''));

                if ($fullName === '' || $licenseNumber === '') {
                    continue;
                }

                $idString = (string) $driverId;
                $existingId = ctype_digit($idString) ? (int) $idString : null;
                if ($existingId) {
                    $existing = AuthorizedDriver::where('Id', $existingId)
                        ->where('applicant_id', $applicant->applicant_id)
                        ->first();
                    if ($existing) {
                        $existing->update([
                            'vehicle_id' => $vehicle->vehicle_id,
                            'fullname' => $fullName,
                            'licenseNumber' => $licenseNumber,
                            'contact' => $contact,
                        ]);
                        continue;
                    }
                }

                AuthorizedDriver::create([
                    'applicant_id' => $applicant->applicant_id,
                    'vehicle_id' => $vehicle->vehicle_id,
                    'fullname' => $fullName,
                    'licenseNumber' => $licenseNumber,
                    'contact' => $contact,
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return $this->ok([
                    'data' => [
                        'vehicle_id' => $vehicle->vehicle_id,
                    ],
                ], 'Registration submitted successfully.');
            }

            return redirect('user-dashboard.php')->with('success', 'Registration submitted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return $this->fail('Registration failed.', 422, [
                    'error' => config('app.debug') ? $e->getMessage() : 'Unable to submit registration.',
                ]);
            }
            return back()->with('error', config('app.debug') ? $e->getMessage() : 'Unable to submit registration.')->withInput();
        }
    }
}
