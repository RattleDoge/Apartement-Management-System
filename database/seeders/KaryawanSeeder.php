<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            // CS Officers — akan dipakai untuk Round Robin penugasan reservasi
            ['name' => 'Rina Wijaya',     'email' => 'rina.cs@map.com',   'dept' => 'CS',  'jabatan' => 'CS Officer'],
            ['name' => 'Dodi Santoso',    'email' => 'dodi.cs@map.com',   'dept' => 'CS',  'jabatan' => 'CS Officer'],
            ['name' => 'Maya Putri',      'email' => 'maya.cs@map.com',   'dept' => 'CS',  'jabatan' => 'CS Supervisor'],
            // Finance
            ['name' => 'Hendra Finance',  'email' => 'hendra.fin@map.com','dept' => 'FIN', 'jabatan' => 'Finance Officer'],
            // Housekeeping
            ['name' => 'Wati HK',         'email' => 'wati.hk@map.com',   'dept' => 'HK',  'jabatan' => 'HK Supervisor'],
            // Engineering
            ['name' => 'Rudi ENG',        'email' => 'rudi.eng@map.com',  'dept' => 'ENG', 'jabatan' => 'Engineer'],
            ['name' => 'Slamet ENG',      'email' => 'slamet.eng@map.com','dept' => 'ENG', 'jabatan' => 'Technician'],
            // Security
            ['name' => 'Agus Security',   'email' => 'agus.sec@map.com',  'dept' => 'SEC', 'jabatan' => 'Security Officer'],
            ['name' => 'Wahyu Security',  'email' => 'wahyu.sec@map.com', 'dept' => 'SEC', 'jabatan' => 'Security Officer'],
            // Management — L2 escalation recipients
            ['name' => 'Budi Manager',    'email' => 'budi.mgr@map.com',  'dept' => 'MGT', 'jabatan' => 'Building Manager'],
            ['name' => 'Siti GM',         'email' => 'siti.gm@map.com',   'dept' => 'MGT', 'jabatan' => 'General Manager'],
        ];

        foreach ($staff as $s) {
            // Skip if user already exists
            if (User::where('email', $s['email'])->exists()) continue;

            $user = User::create([
                'name'     => $s['name'],
                'email'    => $s['email'],
                'role'     => 'karyawan',
                'password' => Hash::make('password'),
            ]);

            Karyawan::create([
                'user_id'    => $user->id,
                'departemen' => $s['dept'],
                'jabatan'    => $s['jabatan'],
            ]);
        }
    }
}
