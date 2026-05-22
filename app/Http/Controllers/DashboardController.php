<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Vehicle;
use App\Models\AuthorizedDriver;
use App\Models\Notification;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function userDashboard()
    {
        $userId = session('user_id');
        $user = Applicant::find($userId);

        if (!$user) {
            return redirect()->route('auth.login')->with('error', 'Please log in to continue.');
        }

        $needsSetup = false;
        if ($user->isStudent()) {
            $needsSetup = !preg_match('/^\d{6}$/', $user->studentRegNo ?? '');
        }

        if ($needsSetup) {
            return redirect()
                ->route('auth.login')
                ->with('info', 'Please complete your profile setup.')
                ->with('requires_type_selection', true);
        }

        // Single query for all vehicle counts
        $vehicleCounts = $user->vehicles()
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'active')  as active,
                SUM(status = 'pending') as pending,
                SUM(registration_expiry < NOW()) as expired,
                SUM(registration_expiry BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)) as expiring_soon
            ")
            ->first();

        $activeVehicleCount   = (int) ($vehicleCounts->active ?? 0);
        $vehicleCount         = (int) ($vehicleCounts->total ?? 0);
        $pendingVehicleCount  = (int) ($vehicleCounts->pending ?? 0);
        $expiredVehicleCount  = (int) ($vehicleCounts->expired ?? 0);
        $expiringSoonCount    = (int) ($vehicleCounts->expiring_soon ?? 0);

        $vehicles = $user->vehicles()
            ->orderBy('status', 'desc')
            ->orderBy('last_updated', 'desc')
            ->get();

        $drivers = AuthorizedDriver::where(function ($q) use ($userId) {
            $q->whereHas('vehicle', function ($q2) use ($userId) {
                $q2->where('applicant_id', $userId);
            })->orWhere('applicant_id', $userId);
        })->with('vehicle')->orderBy('fullname')->get();

        $notifications = Notification::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.user', compact(
            'user',
            'activeVehicleCount',
            'vehicleCount',
            'pendingVehicleCount',
            'expiredVehicleCount',
            'expiringSoonCount',
            'vehicles',
            'drivers',
            'notifications'
        ));
    }

    public function adminDashboard()
    {
        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('auth.admin.login')->with('error', 'Please log in as admin.');
        }

        $totalVehicles = Vehicle::count();
        $activeVehicles = Vehicle::where('status', 'active')->count();
        $pendingVehicles = Vehicle::where('status', 'pending')->count();
        $totalUsers = Applicant::count();
        $studentCount = Applicant::where('registrantType', 'student')->count();
        $staffCount = Applicant::where('registrantType', 'staff')->count();

        $recentVehicles = Vehicle::with('applicant')
            ->orderBy('registration_date', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.admin', compact(
            'totalVehicles',
            'activeVehicles',
            'pendingVehicles',
            'totalUsers',
            'studentCount',
            'staffCount',
            'recentVehicles'
        ));
    }
}
