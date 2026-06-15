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

    protected $description = 'Check Work Orders and send escalation notifications.
                              Unassigned — L1 (T+15 min): Supervisor/Chief, L2 (T+30 min): Manager/GM
                              Assigned not started — L1 (T+60 min): Supervisor/Chief, L2 (T+120 min): Manager/GM';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $closed = ['Work Order Close', 'Selesai'];

        // ── FLOW A: WO belum ada assign_staff ────────────────────────────────

        // A-L1: T+15 min, belum assigned, belum notif L1
        $al1Wos = WorkOrder::whereNull('assign_staff')
            ->where('tanggal', '<=', now()->subMinutes(15))
            ->whereNull('notified_l1_at')
            ->whereNotIn('status_comp', $closed)
            ->get();

        if ($al1Wos->isNotEmpty()) {
            $recipients = $this->getLevel1Recipients();
            if ($recipients->isEmpty()) {
                $this->warn('A-L1: Tidak ada Supervisor/Chief untuk notifikasi.');
            }
            foreach ($al1Wos as $wo) {
                $elapsed = (int) $wo->tanggal->diffInMinutes(now());
                $this->info("A-L1 → WO {$wo->no_wo} | {$elapsed} menit | Lot: {$wo->lot_no} [Belum Assigned]");
                if (!$dryRun) {
                    foreach ($recipients as $user) {
                        $user->notify(new WoEscalationNotification($wo, 1, $elapsed, 'unassigned'));
                    }
                    $wo->update(['notified_l1_at' => now()]);
                }
            }
        }

        // A-L2: T+30 min, belum assigned, L1 sudah, belum notif L2
        $al2Wos = WorkOrder::whereNull('assign_staff')
            ->where('tanggal', '<=', now()->subMinutes(30))
            ->whereNotNull('notified_l1_at')
            ->whereNull('notified_l2_at')
            ->whereNotIn('status_comp', $closed)
            ->get();

        if ($al2Wos->isNotEmpty()) {
            $recipients = $this->getLevel2Recipients();
            if ($recipients->isEmpty()) {
                $this->warn('A-L2: Tidak ada Manager/GM untuk eskalasi.');
            }
            foreach ($al2Wos as $wo) {
                $elapsed = (int) $wo->tanggal->diffInMinutes(now());
                $this->info("A-L2 ESKALASI → WO {$wo->no_wo} | {$elapsed} menit | Lot: {$wo->lot_no} [Belum Assigned]");
                if (!$dryRun) {
                    foreach ($recipients as $user) {
                        $user->notify(new WoEscalationNotification($wo, 2, $elapsed, 'unassigned'));
                    }
                    $wo->update(['notified_l2_at' => now()]);
                }
            }
        }

        // ── FLOW B: WO sudah assigned tapi work_started masih NULL ──────────

        // B-L1: T+60 min, sudah assigned, work belum dimulai, belum notif work_l1
        $bl1Wos = WorkOrder::whereNotNull('assign_staff')
            ->whereNull('work_started')
            ->where('tanggal', '<=', now()->subMinutes(60))
            ->whereNull('notified_work_l1_at')
            ->whereNotIn('status_comp', $closed)
            ->get();

        if ($bl1Wos->isNotEmpty()) {
            $recipients = $this->getLevel1Recipients();
            if ($recipients->isEmpty()) {
                $this->warn('B-L1: Tidak ada Supervisor/Chief untuk notifikasi.');
            }
            foreach ($bl1Wos as $wo) {
                $elapsed = (int) $wo->tanggal->diffInMinutes(now());
                $this->info("B-L1 → WO {$wo->no_wo} | {$elapsed} menit | Lot: {$wo->lot_no} | Assign: {$wo->assign_staff} [Belum Dikerjakan]");
                if (!$dryRun) {
                    foreach ($recipients as $user) {
                        $user->notify(new WoEscalationNotification($wo, 1, $elapsed, 'not_started'));
                    }
                    $wo->update(['notified_work_l1_at' => now()]);
                }
            }
        }

        // B-L2: T+120 min, sudah assigned, work belum dimulai, work_l1 sudah, belum notif work_l2
        $bl2Wos = WorkOrder::whereNotNull('assign_staff')
            ->whereNull('work_started')
            ->where('tanggal', '<=', now()->subMinutes(120))
            ->whereNotNull('notified_work_l1_at')
            ->whereNull('notified_work_l2_at')
            ->whereNotIn('status_comp', $closed)
            ->get();

        if ($bl2Wos->isNotEmpty()) {
            $recipients = $this->getLevel2Recipients();
            if ($recipients->isEmpty()) {
                $this->warn('B-L2: Tidak ada Manager/GM untuk eskalasi.');
            }
            foreach ($bl2Wos as $wo) {
                $elapsed = (int) $wo->tanggal->diffInMinutes(now());
                $this->info("B-L2 ESKALASI → WO {$wo->no_wo} | {$elapsed} menit | Lot: {$wo->lot_no} | Assign: {$wo->assign_staff} [Belum Dikerjakan]");
                if (!$dryRun) {
                    foreach ($recipients as $user) {
                        $user->notify(new WoEscalationNotification($wo, 2, $elapsed, 'not_started'));
                    }
                    $wo->update(['notified_work_l2_at' => now()]);
                }
            }
        }

        $total = $al1Wos->count() + $al2Wos->count() + $bl1Wos->count() + $bl2Wos->count();
        if ($total === 0) {
            $this->line('Tidak ada WO yang perlu dinotifikasi saat ini.');
        } else {
            $this->info(sprintf(
                'Selesai: A-L1=%d A-L2=%d B-L1=%d B-L2=%d%s',
                $al1Wos->count(), $al2Wos->count(), $bl1Wos->count(), $bl2Wos->count(),
                $dryRun ? ' [DRY RUN]' : ''
            ));
        }

        return self::SUCCESS;
    }

    private function getLevel1Recipients(): Collection
    {
        return User::whereHas('karyawan', function ($q) {
            $q->where('jabatan', 'like', '%Supervisor%')
              ->orWhere('jabatan', 'like', '%Chief%');
        })->get();
    }

    private function getLevel2Recipients(): Collection
    {
        return User::whereHas('karyawan', function ($q) {
            $q->where('jabatan', 'like', '%Manager%')
              ->orWhere('jabatan', 'like', '%GM%')
              ->orWhere('jabatan', 'like', '%Director%');
        })->get();
    }
}
