<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\WoEscalationNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CheckWoEscalation extends Command
{
    protected $signature = 'wo:check-escalation
                            {--dry-run : Show what would be notified without actually sending}';

    protected $description = 'Check Work Orders without assigned technician and send escalation notifications.
                              L1 (T+15 min): Supervisor / Chief
                              L2 (T+30 min): Manager / GM';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // ── Level 1: T+15 min, no assign_staff, not yet notified ─────────────
        $l1Wos = WorkOrder::whereNull('assign_staff')
            ->where('tanggal', '<=', now()->subMinutes(15))
            ->whereNull('notified_l1_at')
            ->whereNotIn('status_comp', ['Work Order Close', 'Selesai'])
            ->get();

        if ($l1Wos->isNotEmpty()) {
            $recipients = $this->getLevel1Recipients();
            if ($recipients->isEmpty()) {
                $this->warn('L1: Tidak ada Supervisor/Chief ditemukan untuk menerima notifikasi.');
            }
            foreach ($l1Wos as $wo) {
                $elapsed = (int) $wo->tanggal->diffInMinutes(now());
                $this->info("L1 notif → WO {$wo->no_wo} | {$elapsed} menit | Lot: {$wo->lot_no}");

                if (!$dryRun) {
                    foreach ($recipients as $user) {
                        $user->notify(new WoEscalationNotification($wo, 1, $elapsed));
                    }
                    $wo->update(['notified_l1_at' => now()]);
                }
            }
        }

        // ── Level 2: T+30 min, L1 done, not yet L2 ───────────────────────────
        $l2Wos = WorkOrder::whereNull('assign_staff')
            ->where('tanggal', '<=', now()->subMinutes(30))
            ->whereNotNull('notified_l1_at')
            ->whereNull('notified_l2_at')
            ->whereNotIn('status_comp', ['Work Order Close', 'Selesai'])
            ->get();

        if ($l2Wos->isNotEmpty()) {
            $recipients = $this->getLevel2Recipients();
            if ($recipients->isEmpty()) {
                $this->warn('L2: Tidak ada Manager/GM ditemukan untuk eskalasi.');
            }
            foreach ($l2Wos as $wo) {
                $elapsed = (int) $wo->tanggal->diffInMinutes(now());
                $this->info("L2 ESKALASI → WO {$wo->no_wo} | {$elapsed} menit | Lot: {$wo->lot_no}");

                if (!$dryRun) {
                    foreach ($recipients as $user) {
                        $user->notify(new WoEscalationNotification($wo, 2, $elapsed));
                    }
                    $wo->update(['notified_l2_at' => now()]);
                }
            }
        }

        $total = $l1Wos->count() + $l2Wos->count();
        if ($total === 0) {
            $this->line('Tidak ada WO yang perlu dinotifikasi saat ini.');
        } else {
            $this->info("Selesai: {$l1Wos->count()} L1 + {$l2Wos->count()} L2 notifikasi." . ($dryRun ? ' [DRY RUN]' : ''));
        }

        return self::SUCCESS;
    }

    private function getLevel1Recipients(): Collection
    {
        // Supervisor + Chief (jabatan mengandung kata kunci)
        return User::whereHas('karyawan', function ($q) {
            $q->where('jabatan', 'like', '%Supervisor%')
              ->orWhere('jabatan', 'like', '%Chief%');
        })->get();
    }

    private function getLevel2Recipients(): Collection
    {
        // Manager + GM + Director (atasan dari supervisor)
        return User::whereHas('karyawan', function ($q) {
            $q->where('jabatan', 'like', '%Manager%')
              ->orWhere('jabatan', 'like', '%GM%')
              ->orWhere('jabatan', 'like', '%Director%');
        })->get();
    }
}
