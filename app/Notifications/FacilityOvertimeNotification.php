<?php

namespace App\Notifications;

use App\Models\FacilityReservation;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FacilityOvertimeNotification extends Notification
{
    public function __construct(
        public readonly FacilityReservation $reservation,
        public readonly int $level,          // 1 = CS, 2 = AM (eskalasi)
        public readonly int $elapsedMinutes, // menit sejak jam_selesai
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $body] = $this->buildMessages();
        $levelLabel = $this->level === 1 ? 'Customer Service' : 'Apartment Manager';

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Yth. {$notifiable->name},")
            ->line($body)
            ->line('')
            ->line("**No Reservasi :** {$this->reservation->nomor}")
            ->line("**Unit / Tenant :** {$this->reservation->unit} / {$this->reservation->tenant_name}")
            ->line("**Fasilitas :** {$this->reservation->nama_fasilitas}")
            ->line("**Tanggal :** {$this->reservation->tanggal_reservasi->format('d/m/Y')}")
            ->line("**Jam :** {$this->reservation->jam_mulai} – {$this->reservation->jam_selesai}")
            ->action('Buka Reservasi Fasilitas', url('/karyawan/cs/facility-reservation'))
            ->line("Notifikasi ini dikirim secara otomatis kepada {$levelLabel}.")
            ->salutation('Salam,  ' . config('app.name') . ' — Madison Park');
    }

    public function toDatabase(object $notifiable): array
    {
        [$subject, , $shortMsg] = $this->buildMessages();

        return [
            'reservation_id' => $this->reservation->id,
            'nomor'          => $this->reservation->nomor,
            'unit'           => $this->reservation->unit,
            'tenant_name'    => $this->reservation->tenant_name,
            'nama_fasilitas' => $this->reservation->nama_fasilitas,
            'level'          => $this->level,
            'elapsed'        => $this->elapsedMinutes,
            'message'        => $shortMsg,
        ];
    }

    /** Returns [subject, body, shortMessage] */
    private function buildMessages(): array
    {
        $nomor    = $this->reservation->nomor;
        $fasilitas = $this->reservation->nama_fasilitas;
        $min      = $this->elapsedMinutes;
        $icon     = $this->level === 1 ? '⚠️' : '🚨';

        $subject = $this->level === 1
            ? "{$icon} Fasilitas Belum Ditutup ({$min} menit): {$nomor}"
            : "🚨 ESKALASI – Fasilitas Belum Ditutup ({$min} menit): {$nomor}";

        $body = $this->level === 1
            ? "Fasilitas **{$fasilitas}** (Reservasi {$nomor}) sudah melewati jam selesai namun belum ditutup oleh Security selama **{$min} menit**. Mohon segera ditindaklanjuti."
            : "🚨 **ESKALASI**: Fasilitas **{$fasilitas}** (Reservasi {$nomor}) belum ditutup selama **{$min} menit** setelah jam selesai. CS belum merespons, mohon tindak lanjut segera.";

        $shortMsg = $this->level === 1
            ? "{$icon} {$nomor} – {$fasilitas} belum ditutup Security — {$min} menit"
            : "🚨 ESKALASI: {$nomor} – {$fasilitas} masih belum ditutup — {$min} menit";

        return [$subject, $body, $shortMsg];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
