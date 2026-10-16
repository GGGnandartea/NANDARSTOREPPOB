<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - NSPremium Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 min-h-screen flex">
    <!-- Sidebar sama seperti sebelumnya -->
    <aside class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 hidden md:flex flex-col shadow-sm">
        <div class="h-16 flex items-center px-6 border-b border-gray-200 dark:border-gray-700">
            <a href="{{ route('home') }}" class="text-xl font-bold text-orange-500">AdminPanel</a>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl font-medium">Pengaturan API</a>
            <a href="{{ route('admin.products') }}" class="block px-4 py-3 bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 rounded-xl font-medium">Kelola Produk</a>
        </nav>
    </aside>

    <main class="flex-1 overflow-y-auto p-6 md:p-10">
        <h1 class="text-2xl font-bold mb-8 dark:text-white">Kelola Produk & Margin</h1>

        @if(session('success'))
            <div class="mb-6 bg-green-50 text-green-700 px-4 py-3 rounded-xl">{{ session('success') }}</div>
        @endif

        <!-- 1. SETTING MARGIN PRODUK API -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 mb-10 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-bold dark:text-white">Margin Produk API (Real-Time)</h3>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 text-sm">
                        <th class="p-4">Produk API</th>
                        <th class="p-4">Harga Modal</th>
                        <th class="p-4">Margin (Profit)</th>
                        <th class="p-4">Total Tampil</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($apiProducts as $apiProd)
                        @php $margin = isset($dbMarkups[$apiProd['id']]) ? $dbMarkups[$apiProd['id']]->price : 5000; @endphp
                        <tr>
                            <td class="p-4 text-sm font-medium dark:text-white">{{ $apiProd['name'] }}</td>
                            <td class="p-4 text-sm text-gray-500">Rp {{ number_format($apiProd['price'], 0, ',', '.') }}</td>
                            
                            <form action="{{ route('admin.products.markup', $apiProd['id']) }}" method="POST">
                                @csrf
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span>+ Rp</span>
                                        <input type="number" name="markup_price" value="{{ $margin }}" class="w-24 px-2 py-1 bg-white dark:bg-gray-900 border border-gray-300 rounded text-sm focus:ring-orange-500">
                                    </div>
                                </td>
                                <td class="p-4 text-sm font-bold text-orange-500">Rp {{ number_format($apiProd['price'] + $margin, 0, ',', '.') }}</td>
                                <td class="p-4 text-right">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-bold shadow-sm">Simpan</button>
                                </td>
                            </form>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- 2. TAMBAH PRODUK LOKAL (PUNYA SENDIRI) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-bold dark:text-white">Tambah Produk Lokal (Manual)</h3>
            </div>
            <form action="{{ route('admin.products.local.store') }}" method="POST" class="p-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Nama Produk</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 rounded-lg">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Harga Jual (Rp)</label>
                            <input type="number" name="price" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Stok Awal</label>
                            <input type="number" name="stock" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2">Deskripsi Produk</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 rounded-lg"></textarea>
                </div>
                <button type="submit" class="bg-orange-500 text-white font-bold py-2 px-6 rounded-lg shadow-sm">Tambah Produk Lokal</button>
            </form>
        </div>
    </main>
</body>
</html>