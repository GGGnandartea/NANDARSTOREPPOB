@extends('layouts.admin')
@section('title', 'Produk Lokal Saya')

@section('content')
<!-- DATALIST UNTUK SUGGESTI KATEGORI OTOMATIS -->
<datalist id="kategoriList">
    <option value="Aplikasi Premium">
    <option value="Akun Streaming">
    <option value="E-Book">
    <option value="Novel">
    <option value="Rumus Excel">
    <option value="Template">
    <option value="Desain & Kreatif">
</datalist>

<div class="max-w-7xl mx-auto space-y-6" x-data="{ openEdit: false, editData: {} }">
    
    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Header & Search Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>Katalog Produk Lokal & Stok Manual</span>
                <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-orange text-white shadow-sm shadow-orange-500/20">
                    Produk Mandiri
                </span>
            </h1>
            <p class="text-xs font-semibold text-gray-400 mt-1">Kelola barang digital, template, akun pribadi, serta isi stok akun otomatis pembeli.</p>
        </div>

        <form action="{{ route('admin.products.local') }}" method="GET" class="w-full md:w-80 relative">
            <input 
                type="text" 
                name="search" 
                value="{{ request('search') }}" 
                placeholder="Cari nama produk..." 
                class="w-full pl-11 pr-4 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs sm:text-sm font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500 shadow-sm"
            >
            <svg class="w-4 h-4 absolute left-4 top-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Form Tambah Produk Baru (Kiri) -->
        <div class="lg:col-span-1 admin-card p-6 h-fit space-y-5">
            <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100 dark:border-white/5">
                <div class="w-8 h-8 rounded-xl gradient-orange text-white flex items-center justify-center font-black text-sm shadow-sm">
                    +
                </div>
                <div>
                    <h3 class="font-black text-base text-gray-900 dark:text-white">Tambah Produk Baru</h3>
                    <p class="text-[11px] font-semibold text-gray-400">Buat katalog lokal baru</p>
                </div>
            </div>

            <form action="{{ route('admin.products.local.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf
                <div>
                    <label class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama Produk</label>
                    <input type="text" name="name" required placeholder="Contoh: Canva Pro 1 Tahun" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Harga Jual (Rp)</label>
                        <input type="number" name="price" required placeholder="25000" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-amber-500 uppercase tracking-wider mb-1.5">👑 Reseller (Rp)</label>
                        <input type="number" name="reseller_price" placeholder="Opsional" class="w-full px-4 py-3 rounded-xl bg-amber-50/50 dark:bg-gray-900 border border-amber-200 dark:border-amber-800/40 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-black text-orange-500 uppercase tracking-wider mb-1.5">Kategori / Tipe</label>
                    <input list="kategoriList" type="text" name="category" placeholder="Pilih atau ketik kategori..." class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Metode Proses Akun</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-900 cursor-pointer hover:border-orange-500 transition">
                            <input type="radio" name="process_type" value="auto" checked class="text-orange-500 focus:ring-orange-500">
                            <div>
                                <span class="block text-xs font-black text-gray-900 dark:text-white">⚡ Otomatis</span>
                                <span class="block text-[10px] text-gray-400 font-medium">Dari stok akun</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-900 cursor-pointer hover:border-orange-500 transition">
                            <input type="radio" name="process_type" value="manual" class="text-orange-500 focus:ring-orange-500">
                            <div>
                                <span class="block text-xs font-black text-gray-900 dark:text-white">✋ Manual Admin</span>
                                <span class="block text-[10px] text-gray-400 font-medium">Diproses admin</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Gambar / Logo Produk</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs text-gray-600 dark:text-gray-300 cursor-pointer file:cursor-pointer file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-black file:bg-orange-100 file:text-orange-700">
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Deskripsi & Catatan Garansi</label>
                    <textarea name="description" rows="3" placeholder="Tuliskan detail spesifikasi dan garansi produk..." class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-medium dark:text-white outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                </div>

                <button type="submit" x-bind:disabled="isSubmitting" class="w-full gradient-orange text-white py-3.5 rounded-xl font-black text-xs transition-all shadow-lg shadow-orange-500/25 hover:opacity-90 cursor-pointer disabled:opacity-50">
                    <span x-show="!isSubmitting">✓ Simpan Produk Baru</span>
                    <span x-show="isSubmitting" style="display: none;">Menyimpan...</span>
                </button>
            </form>
        </div>

        <!-- Tabel Daftar Produk (Kanan) -->
        <!-- Tabel Daftar Produk (Kanan) -->
        <div class="lg:col-span-2 admin-card overflow-hidden">
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left admin-table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Katalog Produk</th>
                            <th>Stok & Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($localProducts as $p)
                        <tr>
                            <!-- Kolom 1: Gambar & Nama -->
                            <td>
                                <div class="flex items-center gap-3.5">
                                    @if($p->image)
                                        <img src="{{ $p->image }}" class="w-12 h-12 rounded-xl object-cover border border-gray-200 dark:border-white/10 shadow-sm shrink-0">
                                    @else
                                        <div class="w-12 h-12 rounded-xl gradient-orange text-white flex items-center justify-center font-black text-sm shrink-0 shadow-sm">
                                            NS
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-black text-xs sm:text-sm text-gray-900 dark:text-white">{{ $p->name }}</p>
                                        <div class="flex flex-wrap items-center gap-2 mt-1">
                                            <span class="text-xs font-extrabold text-orange-500">Rp {{ number_format($p->price, 0, ',', '.') }}</span>
                                            @if($p->reseller_price)
                                                <span class="text-[9px] font-black text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-800/40">
                                                    👑 VIP: Rp {{ number_format($p->reseller_price, 0, ',', '.') }}
                                                </span>
                                            @endif
                                            @if($p->category)
                                                <span class="bg-cyan-50 dark:bg-cyan-950/40 text-cyan-600 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-800/40 text-[9px] font-extrabold px-1.5 py-0.5 rounded uppercase">
                                                    {{ $p->category }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom 2: Stok & Status -->
                            <td>
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    @if(($p->process_type ?? 'auto') === 'manual')
                                        <span class="bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800/50 px-2 py-0.5 rounded-full text-[10px] font-black inline-flex items-center gap-1">
                                            ✋ Manual Admin
                                        </span>
                                    @else
                                        <span class="bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-300 dark:border-blue-800/50 px-2 py-0.5 rounded-full text-[10px] font-black inline-flex items-center gap-1">
                                            ⚡ Stok Otomatis
                                        </span>
                                    @endif
                                </div>
                                <div class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">
                                    <span class="w-2 h-2 rounded-full {{ $p->stock > 0 ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                    <span>Stok: {{ $p->stock }} Akun</span>
                                </div>
                                <div>
                                    @if($p->status == 'available')
                                        <span class="bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/50 px-2 py-0.5 rounded-full text-[10px] font-black">
                                            Aktif Dijual
                                        </span>
                                    @else
                                        <span class="bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-300 dark:border-gray-700 px-2 py-0.5 rounded-full text-[10px] font-black">
                                            Nonaktif
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom 3: Aksi -->
                            <td class="text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Tombol Isi Stok -->
                                    <a href="{{ route('admin.products.local.accounts', $p->id) }}" class="gradient-blue text-white px-3 py-1.5 rounded-xl text-xs font-black transition shadow-sm hover:opacity-90 cursor-pointer">
                                        ⚡ Isi Stok
                                    </a>

                                    <!-- Tombol Edit -->
                                    <button 
                                        type="button"
                                        @click="editData = JSON.parse($el.dataset.info); openEdit = true;" 
                                        data-info="{{ json_encode(['id' => $p->id, 'name' => $p->name, 'price' => $p->price, 'reseller_price' => $p->reseller_price, 'category' => $p->category, 'desc' => $p->description, 'process_type' => $p->process_type ?? 'auto']) }}" 
                                        class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                                    >
                                        Edit
                                    </button>

                                    <!-- Toggle Status Aktif/Nonaktif -->
                                    <form action="{{ route('admin.products.local.toggle', $p->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/30 text-amber-600 border border-amber-200 dark:border-amber-800/40 hover:bg-amber-100 transition cursor-pointer" title="Aktif / Nonaktifkan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16a2 2 0 002 2h2a2 2 0 002-2v-4M14 9v4m0 0h4m-4 0l4-4"></path></svg>
                                        </button>
                                    </form>

                                    <!-- Hapus -->
                                    <form action="{{ route('admin.products.local.delete', $p->id) }}" method="POST" onsubmit="return confirm('Hapus permanen produk ini beserta seluruh stok akunnya?');" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-xl bg-red-50 dark:bg-red-950/30 text-red-600 border border-red-200 dark:border-red-800/40 hover:bg-red-100 transition cursor-pointer" title="Hapus Produk">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="p-12 text-center text-gray-400 font-semibold text-xs">
                                @if(request('search')) Pencarian tidak ditemukan. @else Katalog lokal Anda masih kosong. Tambahkan produk pertama di sebelah kiri! @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards List -->
            <div class="block md:hidden divide-y divide-gray-100 dark:divide-white/5">
                @forelse($localProducts as $p)
                    <div class="p-4 space-y-3">
                        <div class="flex items-start gap-3">
                            @if($p->image)
                                <img src="{{ $p->image }}" class="w-12 h-12 rounded-xl object-cover border border-gray-200 dark:border-white/10 shadow-sm shrink-0">
                            @else
                                <div class="w-12 h-12 rounded-xl gradient-orange text-white flex items-center justify-center font-black text-xs shrink-0 shadow-sm">
                                    NS
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <p class="font-black text-xs sm:text-sm text-gray-900 dark:text-white truncate">{{ $p->name }}</p>
                                    @if($p->status == 'available')
                                        <span class="bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/50 px-2 py-0.5 rounded-full text-[9px] font-black shrink-0">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-300 dark:border-gray-700 px-2 py-0.5 rounded-full text-[9px] font-black shrink-0">
                                            Nonaktif
                                        </span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs font-black text-orange-500">Rp {{ number_format($p->price, 0, ',', '.') }}</span>
                                    @if($p->reseller_price)
                                        <span class="text-[9px] font-black text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded-full border border-amber-200 dark:border-amber-800/40">
                                            👑 Rp {{ number_format($p->reseller_price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-2 rounded-xl bg-gray-50 dark:bg-black/20 text-xs">
                            <div class="flex items-center gap-2">
                                @if(($p->process_type ?? 'auto') === 'manual')
                                    <span class="bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded-full text-[9px] font-black">
                                        ✋ Manual Admin
                                    </span>
                                @else
                                    <span class="bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 px-2 py-0.5 rounded-full text-[9px] font-black">
                                        ⚡ Otomatis
                                    </span>
                                @endif
                                <div class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-600 dark:text-gray-400">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $p->stock > 0 ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                    <span>{{ $p->stock }} Akun</span>
                                </div>
                            </div>
                            @if($p->category)
                                <span class="bg-cyan-50 dark:bg-cyan-950/40 text-cyan-600 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-800/40 text-[9px] font-extrabold px-1.5 py-0.5 rounded uppercase">
                                    {{ $p->category }}
                                </span>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-100 dark:border-white/5">
                            <a href="{{ route('admin.products.local.accounts', $p->id) }}" class="gradient-blue text-white px-3 py-1.5 rounded-xl text-xs font-black transition shadow-sm hover:opacity-90 cursor-pointer">
                                ⚡ Isi Stok
                            </a>
                            <button 
                                type="button"
                                @click="editData = JSON.parse($el.dataset.info); openEdit = true;" 
                                data-info="{{ json_encode(['id' => $p->id, 'name' => $p->name, 'price' => $p->price, 'reseller_price' => $p->reseller_price, 'category' => $p->category, 'desc' => $p->description, 'process_type' => $p->process_type ?? 'auto']) }}" 
                                class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                            >
                                Edit
                            </button>
                            <form action="{{ route('admin.products.local.toggle', $p->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/30 text-amber-600 border border-amber-200 dark:border-amber-800/40 hover:bg-amber-100 transition cursor-pointer" title="Aktif / Nonaktifkan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16a2 2 0 002 2h2a2 2 0 002-2v-4M14 9v4m0 0h4m-4 0l4-4"></path></svg>
                                </button>
                            </form>
                            <form action="{{ route('admin.products.local.delete', $p->id) }}" method="POST" onsubmit="return confirm('Hapus permanen produk ini beserta seluruh stok akunnya?');" class="inline">
                                @csrf
                                <button type="submit" class="p-1.5 rounded-xl bg-red-50 dark:bg-red-950/30 text-red-600 border border-red-200 dark:border-red-800/40 hover:bg-red-100 transition cursor-pointer" title="Hapus Produk">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-gray-400 font-semibold text-xs">
                        @if(request('search')) Pencarian tidak ditemukan. @else Katalog lokal Anda masih kosong. Tambahkan produk pertama di atas! @endif
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- MODAL EDIT PRODUK LOKAL -->
    <div x-show="openEdit" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
        <div @click.away="openEdit = false" class="bg-white dark:bg-[#131b2e] rounded-3xl w-full max-w-lg border border-gray-100 dark:border-white/10 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" x-transition.scale>
            <div class="px-6 py-4 border-b border-gray-100 dark:border-white/10 flex justify-between items-center bg-gray-50/50 dark:bg-black/20 shrink-0">
                <h3 class="font-black text-base text-gray-900 dark:text-white">Edit Detail Produk Lokal</h3>
                <button type="button" @click="openEdit = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer">✕</button>
            </div>
            <form :action="'/admin/products/local/' + editData.id + '/update'" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto no-scrollbar flex-1" x-data="{ isUpdating: false }" @submit="isUpdating = true">
                @csrf
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Nama Produk</label>
                    <input type="text" name="name" x-model="editData.name" required class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Harga Jual (Rp)</label>
                        <input type="number" name="price" x-model="editData.price" required class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-amber-500 uppercase tracking-wider mb-1">👑 Harga Reseller</label>
                        <input type="number" name="reseller_price" x-model="editData.reseller_price" placeholder="Kosongkan jika sama..." class="w-full px-4 py-2.5 bg-amber-50/30 dark:bg-gray-900 border border-amber-200 dark:border-amber-800/40 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black text-orange-500 uppercase tracking-wider mb-1">Kategori / Tipe</label>
                    <input list="kategoriList" type="text" name="category" x-model="editData.category" placeholder="Pilih atau Ketik..." class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Metode Pemrosesan Akun</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-900 cursor-pointer hover:border-orange-500 transition">
                            <input type="radio" name="process_type" value="auto" x-model="editData.process_type" class="text-orange-500 focus:ring-orange-500">
                            <div>
                                <span class="block text-xs font-black text-gray-900 dark:text-white">⚡ Otomatis</span>
                                <span class="block text-[10px] text-gray-400 font-medium">Dari stok akun</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-900 cursor-pointer hover:border-orange-500 transition">
                            <input type="radio" name="process_type" value="manual" x-model="editData.process_type" class="text-orange-500 focus:ring-orange-500">
                            <div>
                                <span class="block text-xs font-black text-gray-900 dark:text-white">✋ Manual Admin</span>
                                <span class="block text-[10px] text-gray-400 font-medium">Diproses admin</span>
                            </div>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Ganti Gambar Produk</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Deskripsi</label>
                    <textarea name="description" x-model="editData.desc" rows="3" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs dark:text-white outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <div class="mt-6 flex justify-end gap-2.5 pt-3 border-t border-gray-100 dark:border-white/10">
                    <button type="button" @click="openEdit = false" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold cursor-pointer">Batal</button>
                    <button type="submit" x-bind:disabled="isUpdating" class="px-5 py-2.5 gradient-orange text-white rounded-xl text-xs font-black shadow-md transition cursor-pointer disabled:opacity-50">
                        <span x-show="!isUpdating">Simpan Perubahan</span>
                        <span x-show="isUpdating" style="display: none;">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection