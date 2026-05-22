<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $status = $request->input('status');

        $query = Applicant::withCount('vehicles');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('fullName', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('studentRegNo', 'like', "%{$search}%")
                  ->orWhere('staffsRegNo', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('registrantType', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson() || $request->is('get_users.php')) {
            return $this->ok([
                'data' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ], 'Users fetched.');
        }

        return view('users.index', compact('users'));
    }

    public function show(Request $request, $id = null)
    {
        $id = $id ?? $request->query('user_id') ?? $request->query('id');
        if (!$id) {
            if ($request->expectsJson() || $request->is('view_user.php')) {
                return $this->fail('Missing user ID.', 400);
            }
            abort(404);
        }

        $user = Applicant::with('vehicles')->findOrFail($id);

        if ($request->expectsJson() || $request->is('view_user.php')) {
            return $this->ok(['data' => $user], 'User fetched.');
        }

        return view('users.show', compact('user'));
    }

    public function update(Request $request, $id = null)
    {
        $id = $id ?? $request->input('user_id') ?? $request->input('id');
        if (!$id) {
            return $this->fail('Missing user ID.', 400);
        }

        $user = Applicant::findOrFail($id);

        $action = $request->input('action');
        if ($action === 'toggle_status') {
            $current = strtolower((string) ($user->status ?? 'active'));
            $newStatus = $current === 'suspended' ? 'active' : 'suspended';
            $user->update(['status' => $newStatus]);

            return $this->ok([
                'message' => 'User status updated successfully.',
                'status' => $newStatus,
            ], 'User status updated.');
        }

        // Profile update mode for legacy admin-users edit form
        if ($request->filled('fullName') || $request->filled('email') || $request->filled('registrantType')) {
            $validator = Validator::make($request->all(), [
                'fullName' => 'required|string|max:255',
                'email' => 'required|email|unique:applicants,email,' . $id . ',applicant_id',
                'phone' => 'nullable|string|max:20',
                'registrantType' => 'required|in:student,staff,guest',
            ]);

            if ($validator->fails()) {
                return $this->fail('Validation failed.', 422, ['errors' => $validator->errors()]);
            }

            $user->update($request->only(['fullName', 'email', 'phone', 'registrantType']));

            return $this->ok([], 'User updated successfully.');
        }

        // Explicit status update mode
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,suspended',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $user->update(['status' => $request->input('status')]);

        return $this->ok([], 'User status updated successfully.');
    }

    public function destroy(Request $request, $id = null)
    {
        $id = $id ?? $request->input('user_id') ?? $request->input('id');
        if (!$id) {
            return $this->fail('Missing user ID.', 400);
        }

        // Prevent an admin from deleting their own admin account
        if ((int) $id === (int) session('admin_id')) {
            return $this->fail('You cannot delete your own account.', 403);
        }

        $user = Applicant::findOrFail($id);
        $user->delete();

        return $this->ok([], 'User deleted successfully.');
    }
}
