@extends('layouts.admin')

@php
    $activeSection = request('section', 'list');
@endphp

@section('title', $activeSection === 'create' ? 'Upload Banner' : 'Banner Toko')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{
    editModalOpen: false,
    editBanner: {
        id: null,
        title: '',
        subtitle: '',
        badge: '',
        link_url: '',
        bg_gradient: '',
        order: 0,
        image: '',
        image_mobile: '',
        image_url: '',
        image_mobile_url: ''
    },
    openEdit(banner) {
        this.editBanner = {
            ...banner,
            title: banner.title || '',
            subtitle: banner.subtitle || '',
            badge: banner.badge || '',
            link_url: banner.link_url || '',
            image_url: (banner.image && (banner.image.startsWith('http://') || banner.image.startsWith('https://'))) ? banner.image : '',
            image_mobile_url: (banner.image_mobile && (banner.image_mobile.startsWith('http://') || banner.image_mobile.startsWith('https://'))) ? banner.image_mobile : ''
        };
        this.editModalOpen = true;
    }
}">

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black shrink-0">✕</div>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    @if($activeSection === 'create')
        <!-- ======================================================== -->
        <!-- SECTION: UPLOAD BANNER BARU                              -->
        <!-- ======================================================== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>Upload Banner Baru</span>
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-orange text-white shadow-sm shadow-orange-500/20">
                        Slider Beranda
                    </span>
                </h1>
                <p class="text-xs font-semibold text-gray-400 mt-1">
                    Tambahkan gambar banner promosi baru untuk halaman depan desktop maupun mobile.
                </p>
            </div>
            
            <a href="{{ route('admin.banners', ['section' => 'list']) }}" class="px-4 py-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 hover:border-orange-500 transition flex items-center gap-2 self-start md:self-auto">
                <span>Lihat Daftar Banner ({{ count($banners) }})</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>

        <!-- Petunjuk Dimensi Grafis Banner -->
        <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/40 text-blue-900 dark:text-blue-300 text-xs leading-relaxed flex items-start gap-3 max-w-3xl">
            <span class="text-lg">💡</span>
            <div>
                <span class="font-bold">Tips Gambar Banner:</span>
                <ul class="list-disc list-inside mt-1 space-y-0.5 text-[11px] opacity-90">
                    <li><strong>Desktop:</strong> Ukuran ideal <strong>1920x550 px</strong> atau <strong>1600x450 px</strong> (rasio ~3.5:1).</li>
                    <li><strong>Mobile (Opsional):</strong> Ukuran ideal <strong>800x350 px</strong> (rasio 16:7), atau kosongkan untuk memakai gambar desktop.</li>
                    <li>Jika gambar sudah memiliki teks grafis, <em>Judul</em> &amp; <em>Keterangan</em> boleh dikosongkan.</li>
                </ul>
            </div>
        </div>

        <!-- FORM TAMBAH BANNER BARU -->
        <div class="admin-card p-6 sm:p-8 max-w-3xl space-y-5" x-data="{
            previewDesktop: null,
            previewMobile: null,
            handleDesktopFile(e) {
                if (window.validateAdminImageFile && !window.validateAdminImageFile(e.target)) {
                    this.previewDesktop = null;
                    return;
                }
                const file = e.target.files[0];
                if (file) {
                    this.previewDesktop = URL.createObjectURL(file);
                }
            },
            handleMobileFile(e) {
                if (window.validateAdminImageFile && !window.validateAdminImageFile(e.target)) {
                    this.previewMobile = null;
                    return;
                }
                const file = e.target.files[0];
                if (file) {
                    this.previewMobile = URL.createObjectURL(file);
                }
            }
        }">
            <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100 dark:border-white/5">
                <div class="w-8 h-8 rounded-xl gradient-orange text-white flex items-center justify-center font-black text-xs shadow-sm">
                    +
                </div>
                <div>
                    <h3 class="font-black text-base text-gray-900 dark:text-white">Formulir Upload Banner</h3>
                    <p class="text-[10px] text-gray-400">Upload file gambar atau masukkan URL gambar eksternal</p>
                </div>
            </div>

            <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf

                <!-- Upload Gambar Desktop -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-black text-gray-700 dark:text-gray-300">
                            Gambar Banner Desktop (Utama) <span class="text-orange-500">*</span>
                        </label>
                        <span class="px-2 py-0.5 rounded-lg bg-orange-500/10 border border-orange-500/20 text-[10px] font-black font-mono text-orange-600 dark:text-orange-400">
                            Ukuran Ideal: 1920 x 550 px / 1600 x 450 px
                        </span>
                    </div>
                    <div class="space-y-2">
                        <input 
                            type="file" 
                            name="image" 
                            accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/jpeg,image/png,image/webp,image/gif,image/svg+xml" 
                            @change="handleDesktopFile($event)"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-orange-500 file:text-white hover:file:bg-orange-600 file:cursor-pointer bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-white/10"
                        >
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-gray-400 uppercase font-black">Atau URL Eksternal (Opsional):</span>
                            <input 
                                type="text" 
                                name="image_url" 
                                placeholder="Kosongkan jika sudah pilih file dari komputer..." 
                                class="flex-1 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-medium dark:text-white placeholder:text-gray-400/60 dark:placeholder:text-gray-600 placeholder:italic outline-none focus:ring-1 focus:ring-orange-500"
                            >
                        </div>
                    </div>

                    <template x-if="previewDesktop">
                        <div class="mt-2 rounded-xl overflow-hidden border border-orange-300 dark:border-orange-500/30 aspect-[3.5/1] relative bg-gray-900 shadow-md">
                            <img :src="previewDesktop" class="w-full h-full object-cover">
                            <span class="absolute bottom-1 right-2 bg-black/60 text-white text-[9px] px-1.5 py-0.5 rounded font-bold">Preview Desktop</span>
                        </div>
                    </template>
                </div>

                <!-- Upload Gambar Mobile (Opsional) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-black text-gray-700 dark:text-gray-300">
                            Gambar Banner Khusus Mobile (Opsional)
                        </label>
                        <span class="px-2 py-0.5 rounded-lg bg-blue-500/10 border border-blue-500/20 text-[10px] font-black font-mono text-blue-600 dark:text-blue-400">
                            Ukuran Ideal: 800 x 350 px
                        </span>
                    </div>
                    <div class="space-y-2">
                        <input 
                            type="file" 
                            name="image_mobile" 
                            accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/jpeg,image/png,image/webp,image/gif,image/svg+xml" 
                            @change="handleMobileFile($event)"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-gray-700 file:text-white hover:file:bg-gray-800 file:cursor-pointer bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-white/10"
                        >
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-gray-400 uppercase font-black">Atau URL Eksternal (Opsional):</span>
                            <input 
                                type="text" 
                                name="image_mobile_url" 
                                placeholder="Kosongkan jika sudah pilih file dari komputer..." 
                                class="flex-1 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-medium dark:text-white placeholder:text-gray-400/60 dark:placeholder:text-gray-600 placeholder:italic outline-none focus:ring-1 focus:ring-orange-500"
                            >
                        </div>
                    </div>

                    <template x-if="previewMobile">
                        <div class="mt-2 rounded-xl overflow-hidden border border-gray-300 dark:border-gray-700 aspect-[16/7] max-w-[200px] relative bg-gray-900 shadow-md">
                            <img :src="previewMobile" class="w-full h-full object-cover">
                            <span class="absolute bottom-1 right-2 bg-black/60 text-white text-[9px] px-1.5 py-0.5 rounded font-bold">Preview Mobile</span>
                        </div>
                    </template>
                </div>

                <!-- Judul & Badge -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Judul Banner (Opsional)</label>
                        <input type="text" name="title" placeholder="Kosongkan jika gambar sudah ada tulisan" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white placeholder:text-gray-400/50 dark:placeholder:text-gray-600 placeholder:font-normal placeholder:italic outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Badge Teks (Opsional)</label>
                        <input type="text" name="badge" placeholder="Kosongkan jika gambar sudah ada tulisan" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white placeholder:text-gray-400/50 dark:placeholder:text-gray-600 placeholder:font-normal placeholder:italic outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>

                <!-- Subtitle -->
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Keterangan / Deskripsi Singkat (Opsional)</label>
                    <textarea name="subtitle" rows="2" placeholder="Kosongkan jika gambar banner sudah memiliki teks promosi..." class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-medium dark:text-white placeholder:text-gray-400/50 dark:placeholder:text-gray-600 placeholder:font-normal placeholder:italic outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                </div>

                <!-- Link & Urutan -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Link Saat Diklik (Opsional)</label>
                        <input type="text" name="link_url" placeholder="/product/1 atau https://..." class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-medium dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Urutan</label>
                        <input type="number" name="order" value="0" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>

                <!-- Background Gradient Preset -->
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Gradasi Background (Fallback)</label>
                    <select name="bg_gradient" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500 cursor-pointer">
                        <option value="from-orange-600 to-red-600">Orange ke Merah (Energetic)</option>
                        <option value="from-blue-600 to-indigo-700">Biru ke Indigo (Tech/Modern)</option>
                        <option value="from-emerald-500 to-teal-700">Hijau ke Teal (Fresh/Eco)</option>
                        <option value="from-purple-600 to-pink-600">Ungu ke Pink (Gaming/Promo)</option>
                        <option value="from-amber-500 to-orange-600">Amber ke Oranye (Warm/Gold)</option>
                        <option value="from-gray-900 via-purple-950 to-gray-900">Dark Neon Luxury</option>
                    </select>
                </div>

                <!-- Tombol Submit -->
                <button 
                    type="submit" 
                    :disabled="isSubmitting"
                    class="w-full py-3.5 rounded-xl gradient-orange text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-orange-500/20 hover:scale-[1.01] active:scale-[0.99] transition-all cursor-pointer flex items-center justify-center gap-2"
                >
                    <span x-show="!isSubmitting">Simpan &amp; Publikasikan Banner</span>
                    <span x-show="isSubmitting" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        Menyimpan...
                    </span>
                </button>
            </form>
        </div>

    @else
        <!-- ======================================================== -->
        <!-- SECTION: DAFTAR BANNER TOKO                              -->
        <!-- ======================================================== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>Daftar Banner Toko</span>
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-orange text-white shadow-sm shadow-orange-500/20">
                        {{ count($banners) }} Banner
                    </span>
                </h1>
                <p class="text-xs font-semibold text-gray-400 mt-1">
                    Kelola urutan, status aktif, atau ubah gambar slider banner yang tampil di beranda toko.
                </p>
            </div>
            
            <a href="{{ route('admin.banners', ['section' => 'create']) }}" class="px-4 py-2.5 rounded-xl gradient-orange text-white text-xs font-black shadow-md shadow-orange-500/20 hover:opacity-95 transition flex items-center gap-2 self-start md:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                <span>Upload Banner Baru</span>
            </a>
        </div>

        <div class="admin-card p-5">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                <div>
                    <h3 class="font-black text-base text-gray-900 dark:text-white">Daftar Banner Beranda</h3>
                    <p class="text-[10px] text-gray-400">Urutan banner sesuai nomor urut (terkecil tampil pertama)</p>
                </div>
            </div>

            @if($banners->isEmpty())
                <div class="text-center py-12">
                    <span class="text-4xl block mb-2">🖼️</span>
                    <p class="font-bold text-sm text-gray-500">Belum ada banner tersimpan</p>
                    <p class="text-xs text-gray-400 mt-1">Silakan tambahkan banner baru melalui menu Upload Banner.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-white/5 mt-2">
                    @foreach($banners as $b)
                        <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 {{ !$b->is_active ? 'opacity-50 grayscale' : '' }}">
                            <div class="flex items-start gap-3.5 flex-1 min-w-0">
                                
                                <!-- Thumbnail Banner -->
                                <div class="w-24 sm:w-32 aspect-[3.2/1] rounded-xl overflow-hidden relative shrink-0 shadow-sm border border-gray-200 dark:border-white/10 bg-gradient-to-r {{ $b->bg_gradient ?? 'from-gray-800 to-gray-900' }}">
                                    @if($b->image)
                                        <img src="{{ $b->image }}" alt="{{ $b->title }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-white/50 text-[10px] font-bold">
                                            Gradient Only
                                        </div>
                                    @endif

                                    @if($b->image_mobile)
                                        <span class="absolute bottom-1 right-1 bg-black/70 text-[8px] font-black text-white px-1 rounded" title="Memiliki banner mobile khusus">📱+</span>
                                    @endif
                                </div>

                                <!-- Info Banner -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap mb-1">
                                        @if($b->badge)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 uppercase">
                                                {{ $b->badge }}
                                            </span>
                                        @endif
                                        <span class="text-[10px] font-mono text-gray-400">
                                            Urutan: #{{ $b->order }}
                                        </span>
                                        @if($b->is_active)
                                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </div>

                                    <h4 class="font-black text-sm text-gray-900 dark:text-white truncate">
                                        {{ $b->title ?: '(Tanpa Judul / Grafis Penuh)' }}
                                    </h4>
                                    @if($b->subtitle)
                                        <p class="text-xs text-gray-400 line-clamp-1 mt-0.5">
                                            {{ $b->subtitle }}
                                        </p>
                                    @endif
                                    @if($b->link_url)
                                        <p class="text-[10px] text-orange-500 font-mono truncate mt-0.5">
                                            🔗 {{ $b->link_url }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                <form action="{{ route('admin.banners.toggle', $b->id) }}" method="POST">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        title="{{ $b->is_active ? 'Nonaktifkan Banner' : 'Aktifkan Banner' }}"
                                        class="p-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $b->is_active ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 hover:bg-emerald-100' : 'bg-gray-100 dark:bg-gray-800 text-gray-400 hover:bg-gray-200' }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                </form>

                                <button 
                                    type="button" 
                                    @click="openEdit({{ json_encode($b) }})"
                                    class="p-2 rounded-xl bg-orange-50 dark:bg-orange-950/40 text-orange-600 hover:bg-orange-100 dark:hover:bg-orange-900/60 transition cursor-pointer text-xs font-bold"
                                    title="Edit Banner"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>

                                <form action="{{ route('admin.banners.delete', $b->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus banner ini?')">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-900/60 transition cursor-pointer text-xs font-bold"
                                        title="Hapus Banner"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- MODAL EDIT BANNER -->
    <div 
        x-show="editModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overscroll-contain"
        @keydown.escape.window="editModalOpen = false"
    >
        <div 
            class="bg-white dark:bg-gray-900 rounded-3xl max-w-xl w-full shadow-2xl border border-gray-100 dark:border-white/10 max-h-[90vh] flex flex-col overflow-hidden"
            @click.stop
        >
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10 bg-gray-50/50 dark:bg-black/20 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <h3 class="font-black text-base text-gray-900 dark:text-white">Edit Banner</h3>
                </div>
                <button type="button" @click="editModalOpen = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white text-lg cursor-pointer">✕</button>
            </div>

            <form :action="'{{ url('admin/banners') }}/' + editBanner.id + '/update'" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf

                <div class="p-6 space-y-4 overflow-y-auto overscroll-contain custom-scrollbar flex-1 min-h-0">
                    <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-white/10 aspect-[3.5/1] relative bg-gray-900">
                        <template x-if="editBanner.image">
                            <img :src="editBanner.image" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!editBanner.image">
                            <div class="w-full h-full flex items-center justify-center text-white/50 text-xs font-bold">
                                Tanpa Gambar (Hanya Gradasi)
                            </div>
                        </template>
                        <span class="absolute bottom-1 right-2 bg-black/60 text-white text-[9px] px-1.5 py-0.5 rounded font-bold">Gambar Saat Ini</span>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-black text-gray-700 dark:text-gray-300">Ganti Gambar Desktop (Opsional)</label>
                            <span class="px-2 py-0.5 rounded-lg bg-orange-500/10 border border-orange-500/20 text-[10px] font-black font-mono text-orange-600 dark:text-orange-400">
                                1920 x 550 px / 1600 x 450 px
                            </span>
                        </div>
                        <template x-if="editBanner.image && editBanner.image.startsWith('/storage/')">
                            <div class="mb-2 px-2.5 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                <span>✓</span>
                                <span>Gambar lokal sudah tersimpan. Biarkan kosong jika tidak ingin mengganti gambar.</span>
                            </div>
                        </template>
                        <input 
                            type="file" 
                            name="image" 
                            accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/jpeg,image/png,image/webp,image/gif,image/svg+xml" 
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-orange-500 file:text-white hover:file:bg-orange-600 file:cursor-pointer bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-white/10"
                        >
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="text-[10px] text-gray-400 uppercase font-black">Atau URL Eksternal:</span>
                            <input 
                                type="text" 
                                name="image_url" 
                                x-model="editBanner.image_url"
                                placeholder="Kosongkan jika menggunakan upload lokal" 
                                class="flex-1 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-medium dark:text-white placeholder:text-gray-400/50 dark:placeholder:text-gray-600 placeholder:italic outline-none focus:ring-1 focus:ring-orange-500"
                            >
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-black text-gray-700 dark:text-gray-300">Ganti Gambar Mobile (Opsional)</label>
                            <span class="px-2 py-0.5 rounded-lg bg-blue-500/10 border border-blue-500/20 text-[10px] font-black font-mono text-blue-600 dark:text-blue-400">
                                800 x 350 px
                            </span>
                        </div>
                        <input 
                            type="file" 
                            name="image_mobile" 
                            accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/jpeg,image/png,image/webp,image/gif,image/svg+xml" 
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-gray-700 file:text-white hover:file:bg-gray-800 file:cursor-pointer bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-white/10"
                        >
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="text-[10px] text-gray-400 uppercase font-black">Atau URL Eksternal:</span>
                            <input 
                                type="text" 
                                name="image_mobile_url" 
                                x-model="editBanner.image_mobile_url"
                                placeholder="Kosongkan jika menggunakan upload lokal" 
                                class="flex-1 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-medium dark:text-white placeholder:text-gray-400/50 dark:placeholder:text-gray-600 placeholder:italic outline-none focus:ring-1 focus:ring-orange-500"
                            >
                        </div>
                    </div>

                    <div class="space-y-3 pt-1 border-t border-gray-100 dark:border-white/5">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider">Teks Overlay Banner (Opsional)</span>
                            <button 
                                type="button" 
                                @click.prevent="editBanner.title = ''; editBanner.badge = ''; editBanner.subtitle = ''"
                                class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 text-[10px] font-black transition cursor-pointer"
                            >
                                Kosongkan Semua Teks
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Judul Banner (Opsional)</label>
                                <input type="text" name="title" x-model="editBanner.title" placeholder="(Kosong / Tanpa Judul)" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white placeholder:text-gray-400/50 dark:placeholder:text-gray-600 placeholder:font-normal placeholder:italic outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Badge Teks (Opsional)</label>
                                <input type="text" name="badge" x-model="editBanner.badge" placeholder="(Kosong / Tanpa Badge)" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white placeholder:text-gray-400/50 dark:placeholder:text-gray-600 placeholder:font-normal placeholder:italic outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Keterangan / Deskripsi (Opsional)</label>
                            <textarea name="subtitle" rows="2" x-model="editBanner.subtitle" placeholder="(Kosong / Tanpa Deskripsi)" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-medium dark:text-white placeholder:text-gray-400/50 dark:placeholder:text-gray-600 placeholder:font-normal placeholder:italic outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Link Tujuan</label>
                            <input type="text" name="link_url" x-model="editBanner.link_url" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-medium dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Urutan</label>
                            <input type="number" name="order" x-model="editBanner.order" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Gradasi Background</label>
                        <select name="bg_gradient" x-model="editBanner.bg_gradient" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500 cursor-pointer">
                            <option value="from-orange-600 to-red-600">Orange ke Merah (Energetic)</option>
                            <option value="from-blue-600 to-indigo-700">Biru ke Indigo (Tech/Modern)</option>
                            <option value="from-emerald-500 to-teal-700">Hijau ke Teal (Fresh/Eco)</option>
                            <option value="from-purple-600 to-pink-600">Ungu ke Pink (Gaming/Promo)</option>
                            <option value="from-amber-500 to-orange-600">Amber ke Oranye (Warm/Gold)</option>
                            <option value="from-gray-900 via-purple-950 to-gray-900">Dark Neon Luxury</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-white/10 bg-gray-50/50 dark:bg-black/20 shrink-0">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2.5 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl gradient-orange text-white text-xs font-black uppercase tracking-wider shadow-md hover:scale-[1.02] transition cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
