<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendRenewalReminders extends Command
{
    protected $signature   = 'vehicles:send-renewal-reminders';
    protected $description = 'Notify students whose vehicle registrations are expiring soon (30, 14, 3 days).';

    private function reminderDays(): array
    {
        $raw = config('app.renewal_reminder_days', '30,14,3');
        return array_filter(array_map('intval', explode(',', (string) $raw)));
    }

    public function handle(): int
    {
        $sent = 0;

        foreach ($this->reminderDays() as $days) {
            $vehicles = Vehicle::with('applicant')
                ->whereNotNull('registration_expiry')
                ->whereDate('registration_expiry', now()->addDays($days)->toDateString())
                ->where('status', 'active')
                ->get();

            foreach ($vehicles as $vehicle) {
                $owner = $vehicle->applicant;
                if (!$owner) {
                    continue;
                }

                $this->sendInAppNotification($vehicle, $owner, $days);
                $this->sendEmailReminder($vehicle, $owner, $days);

                $sent++;
                $this->line("  Reminded: {$owner->fullName} — {$vehicle->PlateNumber} ({$days} days left)");
            }
        }

        // Mark expired vehicles as inactive
        $expired = Vehicle::where('status', 'active')
            ->whereNotNull('registration_expiry')
            ->where('registration_expiry', '<', now()->toDateString())
            ->get();

        foreach ($expired as $vehicle) {
            $vehicle->update(['status' => 'inactive', 'last_updated' => now()]);

            $owner = $vehicle->applicant;
            if ($owner) {
                Notification::notifyUser(
                    $owner->applicant_id,
                    'Registration Expired',
                    "Your vehicle ({$vehicle->PlateNumber}) registration expired on {$vehicle->registration_expiry->format('M d, Y')}. Please renew immediately.",
                    'danger',
                    url("/vehicle-details.php?id={$vehicle->vehicle_id}")
                );
            }

            $this->warn("  Expired: {$vehicle->PlateNumber}");
        }

        $this->info("Done. {$sent} reminder(s) sent. {$expired->count()} vehicle(s) marked expired.");

        return Command::SUCCESS;
    }

    private function sendInAppNotification(Vehicle $vehicle, $owner, int $days): void
    {
        $urgency = $days <= 3 ? 'danger' : ($days <= 14 ? 'warning' : 'info');
        $label   = $days === 1 ? 'tomorrow' : "in {$days} days";

        Notification::notifyUser(
            $owner->applicant_id,
            'Registration Expiring Soon',
            "Your vehicle ({$vehicle->PlateNumber} — {$vehicle->make} {$vehicle->model}) registration expires {$label} on {$vehicle->registration_expiry->format('M d, Y')}. Renew now to avoid suspension.",
            $urgency,
            url("/vehicle-details.php?id={$vehicle->vehicle_id}")
        );
    }

    private function sendEmailReminder(Vehicle $vehicle, $owner, int $days): void
    {
        $email = $owner->email ?? null;
        if (!$email) {
            return;
        }

        try {
            Mail::send(
                'emails.renewal-reminder',
                ['vehicle' => $vehicle, 'owner' => $owner, 'daysLeft' => $days],
                fn ($m) => $m->to($email, $owner->fullName)
                              ->subject("Action Required: Vehicle Registration Expires in {$days} Day(s)")
            );
        } catch (\Throwable $e) {
            // Do not let a mail failure abort the command
            $this->warn("  Email failed for {$email}: " . $e->getMessage());
        }
    }
}
