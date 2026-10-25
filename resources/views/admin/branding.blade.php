@extends('layouts.admin')

@section('title', 'Kelola Branding & Logo Toko')

@section('content')
<div class="space-y-6 max-w-6xl mx-auto pb-12">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-black uppercase tracking-wider text-orange-500 bg-orange-100 dark:bg-orange-950/60 px-2.5 py-0.5 rounded-full border border-orange-200 dark:border-orange-800/40">
                    Kustomisasi Visual
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-bold text-gray-400">Identitas Brand Toko</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Branding &amp; Identitas Toko</h1>
            <p class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-gray-400 mt-1">
                Kelola logo utama toko, favicon tab browser, animasi logo loading awal (preloader), serta nama dan tagline brand Anda
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/" target="_blank" class="px-4 py-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-200 hover:text-orange-500 hover:border-orange-500/50 transition flex items-center gap-1.5 shadow-xs">
                <span>Lihat Website</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-2.5 animate-fade-in shadow-xs">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Alert Error Validasi Gambar -->
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-300 font-bold text-xs flex items-center gap-3 shadow-sm">
            <div class="w-7 h-7 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black shrink-0">✕</div>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <form action="{{ route('admin.branding.save') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- CARD UTAMA BRANDING -->
        <div class="admin-card p-6 sm:p-8 space-y-7">
            <!-- Header Card -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-white/5">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl gradient-orange text-white flex items-center justify-center font-black text-xl shadow-lg shadow-orange-500/20">
                        🏪
                    </div>
                    <div>
                        <h3 class="font-black text-base sm:text-lg text-gray-900 dark:text-white">Identitas, Logo &amp; Favicon Toko</h3>
                        <p class="text-[11px] sm:text-xs font-medium text-gray-400">Atur logo toko utama, favicon tab browser, logo animasi loading awal, dan nama brand Anda</p>
                    </div>
                </div>
                <span class="text-[10px] font-black px-3 py-1 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 text-white uppercase shadow-xs">
                    BRANDING
                </span>
            </div>

            <!-- 3 Kolom Upload Box (Logo Utama, Favicon, Logo Loading) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
                <!-- 1. Logo Toko Utama -->
                <div class="p-5 sm:p-6 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-white/10 flex flex-col items-center text-center justify-between space-y-4">
                    <div class="w-full">
                        <label class="block text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-1">1. LOGO TOKO UTAMA</label>
                        <p class="text-[11px] text-gray-400 mb-4">Tampil di Navbar, Header, Footer, &amp; Invoice</p>
                        
                        <div class="relative w-28 h-28 mx-auto rounded-2xl bg-white dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden shadow-inner group">
                            <img id="store_logo_preview" src="{{ !empty($storeLogo) ? $storeLogo : '' }}" alt="Logo Toko" class="w-full h-full object-contain p-2 {{ !empty($storeLogo) ? '' : 'hidden' }}" onerror="this.classList.add('hidden'); document.getElementById('store_logo_fallback').classList.remove('hidden');">
                            <div id="store_logo_fallback" class="w-full h-full gradient-orange text-white flex items-center justify-center font-black text-3xl shadow-sm {{ !empty($storeLogo) ? 'hidden' : '' }}">
                                PK
                            </div>
                        </div>
                    </div>

                    <div class="w-full space-y-2.5">
                        <input type="file" name="store_logo" id="store_logo_input" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/jpeg,image/png,image/webp,image/gif,image/svg+xml" class="w-full text-[11px] text-gray-500 file:mr-2 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-[11px] file:font-black file:bg-orange-100 file:text-orange-700 hover:file:bg-orange-200 dark:file:bg-orange-950/80 dark:file:text-orange-300 cursor-pointer" onchange="previewStoreLogo(this)">
                        <div class="p-2 rounded-xl bg-orange-500/10 border border-orange-500/20 text-[10px] font-bold text-orange-600 dark:text-orange-400">
                            Ukuran Rekomendasi: <span class="font-mono font-black">512 x 512 px</span> (Rasio 1:1 • PNG/WEBP/SVG • Maks 5MB)
                        </div>
                        
                        <input type="text" name="store_logo_url" value="{{ $storeLogo && !str_starts_with($storeLogo, '/storage/') ? $storeLogo : '' }}" placeholder="Atau URL gambar..." class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                    </div>

                    @if(!empty($storeLogo))
                        <label class="inline-flex items-center gap-1.5 pt-1 text-xs text-rose-500 hover:text-rose-600 font-bold cursor-pointer">
                            <input type="checkbox" name="remove_store_logo" value="1" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                            <span>Hapus Logo (Reset ke NS)</span>
                        </label>
                    @endif
                </div>

                <!-- 2. Favicon Tab Browser -->
                <div class="p-5 sm:p-6 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-white/10 flex flex-col items-center text-center justify-between space-y-4">
                    <div class="w-full">
                        <label class="block text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-1">2. FAVICON TAB BROWSER</label>
                        <p class="text-[11px] text-gray-400 mb-4">Icon di tab browser untuk semua halaman website</p>
                        
                        @php
                            $initialFavicon = !empty($storeFavicon) ? $storeFavicon : (!empty($storeLogo) ? $storeLogo : '');
                        @endphp
                        <div class="relative w-28 h-28 mx-auto rounded-2xl bg-white dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden shadow-inner group">
                            <img id="store_favicon_preview" src="{{ $initialFavicon }}" alt="Favicon" class="w-14 h-14 object-contain p-1 {{ !empty($initialFavicon) ? '' : 'hidden' }}" onerror="this.classList.add('hidden'); document.getElementById('store_favicon_fallback').classList.remove('hidden');">
                            <div id="store_favicon_fallback" class="w-14 h-14 rounded-2xl gradient-orange text-white flex items-center justify-center font-black text-xl shadow-sm {{ !empty($initialFavicon) ? 'hidden' : '' }}">
                                PK
                            </div>
                        </div>
                    </div>

                    <div class="w-full space-y-2.5">
                        <input type="file" name="store_favicon" id="store_favicon_input" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,.ico,image/jpeg,image/png,image/webp,image/gif,image/svg+xml,image/x-icon" class="w-full text-[11px] text-gray-500 file:mr-2 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-[11px] file:font-black file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 dark:file:bg-blue-950/80 dark:file:text-blue-300 cursor-pointer" onchange="previewStoreFavicon(this)">
                        <div class="p-2 rounded-xl bg-blue-500/10 border border-blue-500/20 text-[10px] font-bold text-blue-600 dark:text-blue-400">
                            Ukuran Rekomendasi: <span class="font-mono font-black">64 x 64 px</span> atau <span class="font-mono font-black">128 x 128 px</span> (Rasio 1:1 • ICO/PNG)
                        </div>
                        
                        <input type="text" name="store_favicon_url" value="{{ $storeFavicon && !str_starts_with($storeFavicon, '/storage/') ? $storeFavicon : '' }}" placeholder="Atau URL favicon (.ico, .png)..." class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    @if(!empty($storeFavicon))
                        <label class="inline-flex items-center gap-1.5 pt-1 text-xs text-rose-500 hover:text-rose-600 font-bold cursor-pointer">
                            <input type="checkbox" name="remove_store_favicon" value="1" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                            <span>Hapus Favicon Kustom</span>
                        </label>
                    @endif
                </div>

                <!-- 3. Logo Loading / Preloader -->
                <div class="p-5 sm:p-6 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-white/10 flex flex-col items-center text-center justify-between space-y-4">
                    <div class="w-full">
                        <label class="block text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-1">3. LOGO LOADING / PRELOADER</label>
                        <p class="text-[11px] text-gray-400 mb-4">Logo animasi saat website pertama kali dimuat</p>
                        
                        @php
                            $initialLoading = !empty($loadingLogo) ? $loadingLogo : (!empty($storeLogo) ? $storeLogo : '');
                        @endphp
                        <div class="relative w-28 h-28 mx-auto rounded-2xl bg-white dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden shadow-inner group">
                            <img id="loading_logo_preview" src="{{ $initialLoading }}" alt="Logo Loading" class="w-full h-full object-contain p-2 {{ !empty($initialLoading) ? '' : 'hidden' }}" onerror="this.classList.add('hidden'); document.getElementById('loading_logo_fallback').classList.remove('hidden');">
                            <div id="loading_logo_fallback" class="w-full h-full gradient-orange text-white flex items-center justify-center font-black text-3xl shadow-sm {{ !empty($initialLoading) ? 'hidden' : '' }}">
                                PK
                            </div>
                        </div>
                    </div>

                    <div class="w-full space-y-2.5">
                        <input type="file" name="loading_logo" id="loading_logo_input" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/jpeg,image/png,image/webp,image/gif,image/svg+xml" class="w-full text-[11px] text-gray-500 file:mr-2 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-[11px] file:font-black file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200 dark:file:bg-amber-950/80 dark:file:text-amber-300 cursor-pointer" onchange="previewLoadingLogo(this)">
                        <div class="p-2 rounded-xl bg-amber-500/10 border border-amber-500/20 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                            Ukuran Rekomendasi: <span class="font-mono font-black">512 x 512 px</span> (Rasio 1:1 • PNG/WEBP/SVG • Maks 5MB)
                        </div>
                        
                        <input type="text" name="loading_logo_url" value="{{ $loadingLogo && !str_starts_with($loadingLogo, '/storage/') ? $loadingLogo : '' }}" placeholder="Atau URL logo preloader..." class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                    </div>

                    @if(!empty($loadingLogo))
                        <label class="inline-flex items-center gap-1.5 pt-1 text-xs text-rose-500 hover:text-rose-600 font-bold cursor-pointer">
                            <input type="checkbox" name="remove_loading_logo" value="1" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                            <span>Hapus Logo Loading Khusus</span>
                        </label>
                    @endif
                </div>
            </div>

            <!-- Nama Toko & Slogan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4 border-t border-gray-100 dark:border-white/5">
                <div>
                    <label class="block text-xs font-black text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-2">Nama Toko / Brand Utama</label>
                    <input type="text" name="store_name" value="{{ $storeName ?? 'PremiumKita' }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-sm font-black dark:text-white outline-none focus:ring-2 focus:ring-orange-500 transition" placeholder="Contoh: PremiumKita">
                    <p class="text-[11px] text-gray-400 mt-1.5 leading-relaxed">
                        *Nama ini akan muncul di judul navbar, invoice, footer, dan seluruh halaman toko.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-2">Slogan / Tagline Toko</label>
                    <input type="text" name="store_tagline" value="{{ $storeTagline ?? 'Platform Digital & Akun Premium Otomatis' }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500 transition" placeholder="Contoh: Platform Digital & Akun Premium Otomatis">
                    <p class="text-[11px] text-gray-400 mt-1.5 leading-relaxed">
                        *Deskripsi singkat identitas toko Anda yang muncul pada preloader dan metadata.
                    </p>
                </div>
            </div>

            <!-- Tips Banner -->
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/25 text-amber-800 dark:text-amber-300 text-xs font-medium leading-relaxed flex items-start gap-3">
                <span class="text-xl shrink-0">💡</span>
                <div>
                    <strong class="font-bold">Tips Branding Optimal:</strong>
                    Gunakan file gambar berformat PNG transparan atau SVG dengan aspek rasio persegi (1:1) untuk hasil tampilan yang paling tajam dan presisi di semua perangkat (HP, Tablet, maupun PC).
                </div>
            </div>

            <!-- Tombol Simpan -->
            <div class="pt-4 border-t border-gray-100 dark:border-white/5 flex justify-end">
                <button type="submit" class="px-6 py-3.5 rounded-xl gradient-orange text-white font-black text-sm shadow-lg shadow-orange-500/25 hover:brightness-110 active:scale-95 transition cursor-pointer flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span>Simpan Pengaturan Branding</span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function previewStoreLogo(input) {
    if (!window.validateAdminImageFile(input, { allowIco: false, maxMb: 5 })) return;
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('store_logo_preview');
            var fallback = document.getElementById('store_logo_fallback');
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            if (fallback) {
                fallback.classList.add('hidden');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewStoreFavicon(input) {
    if (!window.validateAdminImageFile(input, { allowIco: true, maxMb: 5 })) return;
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('store_favicon_preview');
            var fallback = document.getElementById('store_favicon_fallback');
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            if (fallback) {
                fallback.classList.add('hidden');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewLoadingLogo(input) {
    if (!window.validateAdminImageFile(input, { allowIco: false, maxMb: 5 })) return;
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('loading_logo_preview');
            var fallback = document.getElementById('loading_logo_fallback');
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            if (fallback) {
                fallback.classList.add('hidden');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
