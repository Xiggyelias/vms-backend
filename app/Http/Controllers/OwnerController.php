<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OwnerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');

        $query = Applicant::withCount('vehicles');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('fullName', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('idNumber', 'like', "%{$search}%")
                  ->orWhere('studentRegNo', 'like', "%{$search}%")
                  ->orWhere('staffsRegNo', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('registrantType', $type);
        }

        $owners = $query->orderBy('fullName')->paginate(20);

        return view('owners.index', compact('owners'));
    }

    public function show($id)
    {
        $owner = Applicant::with(['vehicles.authorizedDrivers'])->findOrFail($id);

        return view('owners.show', compact('owner'));
    }

    public function edit($id)
    {
        $owner = Applicant::findOrFail($id);

        return view('owners.edit', compact('owner'));
    }

    public function update(Request $request, $id = null)
    {
        $id = $id ?? $request->input('owner_id') ?? $request->input('applicant_id') ?? $request->input('id') ?? session('user_id');
        if (!$id) {
            if ($request->expectsJson() || $request->is('update-owner-info.php')) {
                return $this->fail('Missing owner ID.', 400);
            }
            return back()->with('error', 'Missing owner ID.');
        }

        // Non-admin users may only update their own profile
        $isAdmin     = (bool) session('is_admin');
        $sessionUser = (int) session('user_id');
        if (!$isAdmin && (int) $id !== $sessionUser) {
            if ($request->expectsJson() || $request->is('update-owner-info.php')) {
                return $this->fail('You are not authorised to update this profile.', 403);
            }
            abort(403, 'You are not authorised to update this profile.');
        }

        $owner = Applicant::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|unique:applicants,email,' . $id . ',applicant_id',
            'phone' => 'nullable|string|max:20',
            'idNumber' => 'nullable|string|max:50',
            'college' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->is('update-owner-info.php')) {
                return $this->fail('Validation failed.', 422, [
                    'errors' => $validator->errors(),
                ]);
            }
            return back()->withErrors($validator)->withInput();
        }

        $owner->update($request->only([
            'fullName',
            'email',
            'phone',
            'idNumber',
            'college',
        ]));

        if ($request->expectsJson() || $request->is('update-owner-info.php')) {
            return $this->ok([
                'data' => [
                    'owner_id' => (int) $owner->applicant_id,
                ],
            ], 'Owner information updated successfully.');
        }

        return redirect()->route('owners.show', $id)->with('success', 'Owner information updated successfully.');
    }
}
