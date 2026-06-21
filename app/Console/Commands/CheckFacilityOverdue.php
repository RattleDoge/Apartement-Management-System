<?php

namespace App\Console\Commands;

use App\Models\FacilityReservation;
use App\Models\User;
use App\Notifications\FacilityOvertimeNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CheckFacilityOverdue extends Command
{
    protected $signature = 'facility:check-overdue
                            {--dry-run : Tampilkan yang akan dinotifikasi tanpa benar-benar mengirim}';

    protected $description = 'Cek fasilitas yang belum ditutup Security setelah jam selesai.
                              L1 (T+15 menit): CS — pengingat langsung
                              L2 (T+30 menit): AM — eskalasi jika CS belum merespons';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Hanya reservasi yang sedang berlangsung & belum ditutup Security
        $base = FacilityReservation::where('status', 'Sedang Berlangsung')
            ->whereNull('sec_close_at');

        // ── L1: T+15 menit → notifikasi ke CS ───────────────────────────────

        $l1Reservations = (clone $base)
            ->whereNull('notified_cs_at')
            ->get()
            ->filter(fn($r) => $this->endDateTime($r)->addMinutes(15)->lte(now()));

        if ($l1Reservations->isNotEmpty()) {
            $recipients = $this->getCsRecipients();
            if ($recipients->isEmpty()) {
                $this->warn('L1: Tidak ada CS untuk notifikasi (periksa departemen CS di data karyawan).');
            }
            foreach ($l1Reservations as $reservation) {
                $elapsed = (int) $this->endDateTime($reservation)->diffInMinutes(now());
                $this->info("L1 → {$reservation->nomor} | {$reservation->nama_fasilitas} | {$elapsed} menit lewat jam selesai");
                if (!$dryRun) {
                    foreach ($recipients as $user) {
                        $user->notify(new FacilityOvertimeNotification($reservation, 1, $elapsed));
                    }
                    $reservation->update(['notified_cs_at' => now()]);
                }
            }
        }

        // ── L2: T+30 menit → eskalasi ke AM ─────────────────────────────────

        $l2Reservations = (clone $base)
            ->whereNotNull('notified_cs_at')
            ->whereNull('notified_am_at')
            ->get()
            ->filter(fn($r) => $this->endDateTime($r)->addMinutes(30)->lte(now()));

        if ($l2Reservations->isNotEmpty()) {
            $recipients = $this->getAmRecipients();
            if ($recipients->isEmpty()) {
                $this->warn('L2: Tidak ada AM/Manager untuk eskalasi (periksa jabatan Manager di data karyawan).');
            }
            foreach ($l2Reservations as $reservation) {
                $elapsed = (int) $this->endDateTime($reservation)->diffInMinutes(now());
                $this->info("L2 ESKALASI → {$reservation->nomor} | {$reservation->nama_fasilitas} | {$elapsed} menit lewat jam selesai");
                if (!$dryRun) {
                    foreach ($recipients as $user) {
                        $user->notify(new FacilityOvertimeNotification($reservation, 2, $elapsed));
                    }
                    $reservation->update(['notified_am_at' => now()]);
                }
            }
        }

        $total = $l1Reservations->count() + $l2Reservations->count();
        if ($total === 0) {
            $this->line('Tidak ada fasilitas yang perlu dinotifikasi saat ini.');
        } else {
            $this->info(sprintf(
                'Selesai: L1(CS)=%d L2(AM)=%d%s',
                $l1Reservations->count(),
                $l2Reservations->count(),
                $dryRun ? ' [DRY RUN]' : ''
            ));
        }

        return self::SUCCESS;
    }

    private function endDateTime(FacilityReservation $reservation): Carbon
    {
        return Carbon::parse(
            $reservation->tanggal_reservasi->format('Y-m-d') . ' ' . $reservation->jam_selesai
        );
    }

    /** CS officers — departemen CS atau jabatan mengandung 'CS'/'Customer Service' */
    private function getCsRecipients(): Collection
    {
        return User::whereHas('karyawan', function ($q) {
            $q->where('departemen', 'like', '%CS%')
              ->orWhere('jabatan', 'like', '%CS%')
              ->orWhere('jabatan', 'like', '%Customer Service%');
        })->get();
    }

    /** AM — jabatan Manager atau GM di departemen AM */
    private function getAmRecipients(): Collection
    {
        return User::whereHas('karyawan', function ($q) {
            $q->where('jabatan', 'like', '%Manager%')
              ->orWhere('jabatan', 'like', '%GM%')
              ->orWhere('jabatan', 'like', '%Director%');
        })->get();
    }
}
