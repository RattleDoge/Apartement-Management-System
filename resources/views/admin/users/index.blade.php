<x-layouts.admin>
    <x-slot name="header">Manajemen Pengguna</x-slot>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h2 class="font-semibold text-gray-700 dark:text-gray-200">Daftar Pengguna</h2>
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
                + Tambah Pengguna
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Bergabung</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ([
                        ['name' => 'Budi Santoso',  'email' => 'budi@mail.com',  'date' => '12 Jan 2025', 'active' => true],
                        ['name' => 'Siti Rahma',    'email' => 'siti@mail.com',  'date' => '20 Feb 2025', 'active' => true],
                        ['name' => 'Ahmad Fauzi',   'email' => 'ahmad@mail.com', 'date' => '05 Mar 2025', 'active' => false],
                        ['name' => 'Dewi Kusuma',   'email' => 'dewi@mail.com',  'date' => '18 Apr 2025', 'active' => true],
                    ] as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($user['name'], 0, 1)) }}
                                </div>
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $user['name'] }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $user['email'] }}</td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $user['date'] }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $user['active'] ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                {{ $user['active'] ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <button class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">Edit</button>
                                <button class="text-red-500 hover:underline text-xs">Hapus</button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
