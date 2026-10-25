@extends('layouts.admin')
@section('title', 'Kelola Stok - ' . $product->name)

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ openEditAcc: false, accData: {} }">
    
    <!-- Tombol Kembali -->
    <a href="{{ route('admin.products.local') }}" class="inline-flex items-center gap-2 text-xs font-black text-gray-500 dark:text-gray-400 hover:text-orange-500 dark:hover:text-orange-400 transition-colors cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        <span>Kembali ke Daftar Produk Lokal</span>
    </a>

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Header Banner Produk -->
    <div class="admin-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            @if($product->image)
                <img src="{{ $product->image }}" class="w-14 h-14 rounded-2xl object-cover border border-gray-200 dark:border-white/10 shadow-sm shrink-0">
            @else
                <div class="w-14 h-14 rounded-2xl gradient-orange text-white flex items-center justify-center font-black text-base shadow-sm shrink-0">
                    PK
                </div>
            @endif
            <div>
                <h1 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white">{{ $product->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-extrabold text-orange-500">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                    <span class="text-gray-300 dark:text-gray-700">•</span>
                    <span class="text-xs font-bold text-blue-500">Stok Tersedia: {{ $availableStock }} Akun</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="px-3.5 py-1.5 rounded-full {{ $availableStock > 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400 border border-red-300' }} text-xs font-black">
                {{ $availableStock > 0 ? '● Siap Dijual' : '● Stok Kosong' }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Kolom Kiri: Input Stok (Upload TXT & Tambah Manual) -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Upload Massal TXT -->
            <div class="admin-card p-6 space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100 dark:border-white/5">
                    <div class="w-8 h-8 rounded-xl gradient-blue text-white flex items-center justify-center font-black text-xs shadow-sm">
                        📄
                    </div>
                    <div>
                        <h3 class="font-black text-sm text-gray-900 dark:text-white">Upload Massal (.txt)</h3>
                        <p class="text-[10px] text-gray-400">Ekstrak puluhan akun sekaligus</p>
                    </div>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    Format file txt (1 baris = 1 akun):<br>
                    <code class="text-cyan-500 dark:text-cyan-400 font-mono font-bold bg-gray-100 dark:bg-black/40 px-2 py-0.5 rounded text-[11px]">email@gmail.com|password123</code>
                </p>
                
                <form action="{{ route('admin.products.local.accounts.store', $product->id) }}" method="POST" enctype="multipart/form-data" x-data="{ isUploading: false }" @submit="isUploading = true">
                    @csrf
                    <input type="file" name="txt_file" accept=".txt" required class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-black file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 cursor-pointer mb-4">
                    <button type="submit" x-bind:disabled="isUploading" class="w-full gradient-blue text-white font-black py-3 rounded-xl text-xs transition shadow-md hover:opacity-90 cursor-pointer disabled:opacity-50">
                        <span x-show="!isUploading">Upload & Ekstrak Akun</span>
                        <span x-show="isUploading" style="display: none;">Memproses...</span>
                    </button>
                </form>
            </div>

            <!-- Tambah Manual -->
            <div class="admin-card p-6 space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100 dark:border-white/5">
                    <div class="w-8 h-8 rounded-xl gradient-orange text-white flex items-center justify-center font-black text-xs shadow-sm">
                        ✍️
                    </div>
                    <div>
                        <h3 class="font-black text-sm text-gray-900 dark:text-white">Input Manual</h3>
                        <p class="text-[10px] text-gray-400">Tambah 1 atau salinan akun</p>
                    </div>
                </div>

                <form action="{{ route('admin.products.local.accounts.store', $product->id) }}" method="POST" class="space-y-3.5" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                    @csrf
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Email / Username</label>
                        <input type="text" name="email" required placeholder="akun@email.com" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Password / Lisensi</label>
                        <input type="text" name="password" required placeholder="password123" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Jumlah Stok Salinan</label>
                        <input type="number" name="quantity" min="1" value="1" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <button type="submit" x-bind:disabled="isSubmitting" class="w-full gradient-orange text-white font-black py-3 rounded-xl text-xs transition shadow-md hover:opacity-90 cursor-pointer disabled:opacity-50">
                        <span x-show="!isSubmitting">Simpan Stok ke Database</span>
                        <span x-show="isSubmitting" style="display: none;">Menyimpan...</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Tabel Daftar Akun Stok -->
        <div class="lg:col-span-2 admin-card overflow-hidden flex flex-col h-[650px]">
            <div class="p-5 border-b border-gray-100 dark:border-white/5 flex flex-col sm:flex-row justify-between items-center gap-3 bg-gray-50/50 dark:bg-black/20">
                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <h3 class="font-black text-sm text-gray-900 dark:text-white">Daftar Stok Akun</h3>
                    <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 px-2.5 py-0.5 rounded-full text-[10px] font-black border border-emerald-300">
                        {{ $availableStock }} Tersedia
                    </span>
                    
                    @if($accounts->count() > 0)
                        <form action="{{ route('admin.products.local.accounts.clear', $product->id) }}" method="POST" onsubmit="return confirm('PERINGATAN!\n\nAnda yakin ingin MENGHAPUS SEMUA DATA STOK untuk produk ini?\nData yang terhapus tidak dapat dikembalikan.');">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-black text-red-600 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/40 hover:bg-red-500 hover:text-white transition cursor-pointer">
                                🗑️ Kosongkan Semua
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Pencarian Stok Akun -->
                <form action="{{ route('admin.products.local.accounts', $product->id) }}" method="GET" class="relative w-full sm:w-60">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari data akun..." class="w-full pl-9 pr-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                    <svg class="w-3.5 h-3.5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </form>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar">
                <table class="w-full text-left admin-table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Data Kredensial (Email & Password)</th>
                            <th>Status Penjualan</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $acc)
                            <tr>
                                <td>
                                    <p class="font-mono text-xs font-bold text-gray-900 dark:text-white select-all">{{ $acc->email }}</p>
                                    <p class="font-mono text-[11px] text-cyan-600 dark:text-cyan-400 select-all mt-0.5">Key: {{ $acc->password }}</p>
                                </td>
                                <td>
                                    @if($acc->is_sold)
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-300 dark:border-gray-700">
                                            ✓ Terjual
                                        </span>
                                    @else
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300">
                                            Tersedia
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button 
                                            type="button"
                                            @click="accData = JSON.parse($el.dataset.info); openEditAcc = true;" 
                                            data-info="{{ json_encode(['id' => $acc->id, 'email' => $acc->email, 'password' => $acc->password]) }}"
                                            class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 text-xs font-bold transition cursor-pointer"
                                        >
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.products.local.accounts.delete', $acc->id) }}" method="POST" onsubmit="return confirm('Hapus akun ini dari stok?');" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1 rounded-lg text-gray-400 hover:text-red-500 transition cursor-pointer">
                                                ✕
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-12 text-center text-gray-400 font-semibold text-xs">
                                    Belum ada stok akun yang ditambahkan. Gunakan form di sebelah kiri untuk mengisi stok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT DATA AKUN -->
    <div x-show="openEditAcc" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
        <div @click.away="openEditAcc = false" class="bg-white dark:bg-[#131b2e] rounded-3xl w-full max-w-md border border-gray-100 dark:border-white/10 shadow-2xl p-6 space-y-4" x-transition.scale>
            <div class="flex justify-between items-center pb-2 border-b border-gray-100 dark:border-white/10">
                <h3 class="font-black text-sm text-gray-900 dark:text-white">Edit Data Akun Stok</h3>
                <button type="button" @click="openEditAcc = false" class="text-gray-400 hover:text-gray-200">✕</button>
            </div>
            <form :action="'/admin/products/local/accounts/' + accData.id + '/update'" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Email / Username</label>
                    <input type="text" name="email" x-model="accData.email" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Password / Lisensi</label>
                    <input type="text" name="password" x-model="accData.password" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-white/10">
                    <button type="button" @click="openEditAcc = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2 gradient-orange text-white rounded-xl text-xs font-black shadow-md cursor-pointer">Simpan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection