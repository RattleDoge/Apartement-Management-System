<x-layouts.admin>
    <x-slot name="header">Pengaturan</x-slot>

    <div class="max-w-2xl space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-700 dark:text-gray-200">Pengaturan Umum</h2>
            </div>
            <div class="px-5 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Aplikasi</label>
                    <input type="text" value="{{ config('app.name') }}"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm
                               bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200
                               focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL Aplikasi</label>
                    <input type="text" value="{{ config('app.url') }}"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm
                               bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200
                               focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timezone</label>
                    <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm
                                  bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option>Asia/Jakarta</option>
                        <option>Asia/Makassar</option>
                        <option>Asia/Jayapura</option>
                    </select>
                </div>
                <div class="pt-2">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-5 py-2 rounded-lg transition-colors">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
