import React from 'react';
import { Link } from '@inertiajs/react';

export default function FooterSection({
    store,
    effectiveAdminWa,
    setShowTrackModal,
}) {
    return (
        <footer className="bg-white dark:bg-[#0b1121] border-t border-gray-200 dark:border-gray-800 mt-auto py-10 transition-colors z-20 relative">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-10 lg:gap-16">
                    
                    <div className="flex flex-col gap-4">
                        <div className="flex items-center gap-2">
                            {store?.logo ? (
                                <div className="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 border border-gray-100 dark:border-white/10 p-0.5 flex items-center justify-center shadow-md overflow-hidden">
                                    <img src={store.logo} alt={store?.name || 'NandarStore'} className="w-full h-full object-contain rounded-lg" />
                                </div>
                            ) : (
                                <div className="w-8 h-8 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg text-white flex items-center justify-center font-bold text-sm shadow-md">NS</div>
                            )}
                            <span className="font-black text-gray-900 dark:text-white text-xl tracking-tight">{store?.name || 'NandarStore'}</span>
                        </div>
                        <p className="text-gray-500 dark:text-gray-300 text-sm leading-relaxed font-medium">
                            Platform digital andalan untuk upgrade produktivitas dan hiburanmu tanpa bikin dompet boncos. Nikmati pengalaman belanja akun premium yang 100% aman, diproses otomatis secara instan, dengan penawaran harga spesial.
                        </p>
                        <div className="flex items-center gap-3 mt-2">
                            <a href="https://instagram.com/nandarstore" target="_blank" rel="noreferrer" className="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 flex items-center justify-center hover:bg-orange-500 hover:text-white dark:hover:bg-orange-500 transition-colors shadow-sm" aria-label="Instagram NandarStore">
                                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            </a>
                            <a href={`https://wa.me/${effectiveAdminWa}?text=Halo%20Admin%20${encodeURIComponent(store?.name || 'NandarStore')},`} target="_blank" rel="noreferrer" className="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 flex items-center justify-center hover:bg-orange-500 hover:text-white dark:hover:bg-orange-500 transition-colors shadow-sm" aria-label="WhatsApp NandarStore">
                                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            </a>
                        </div>
                    </div>

                    <div className="flex flex-col gap-4 md:pl-10">
                        <h3 className="font-black text-gray-900 dark:text-white tracking-widest text-sm uppercase mb-1">Layanan</h3>
                        <ul className="space-y-3">
                            <li>
                                <Link href="/" className="text-gray-500 dark:text-gray-300 hover:text-orange-500 dark:hover:text-orange-400 font-medium text-sm flex items-center gap-2 transition-colors">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg> Beranda
                                </Link>
                            </li>
                            <li>
                                <Link href="/ppob" className="text-gray-500 dark:text-gray-300 hover:text-orange-500 dark:hover:text-orange-400 font-medium text-sm flex items-center gap-2 transition-colors">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg> Layanan PPOB
                                </Link>
                            </li>
                            <li>
                                <button onClick={() => setShowTrackModal(true)} className="text-gray-500 dark:text-gray-300 hover:text-orange-500 dark:hover:text-orange-400 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg> Lacak Pesanan
                                </button>
                            </li>
                            <li>
                                <Link href="/faq" className="text-gray-500 dark:text-gray-300 hover:text-orange-500 dark:hover:text-orange-400 font-medium text-sm flex items-center gap-2 transition-colors">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg> Pusat Bantuan &amp; FAQ
                                </Link>
                            </li>
                            <li>
                                <Link href="/syarat-ketentuan" className="text-gray-500 dark:text-gray-300 hover:text-orange-500 dark:hover:text-orange-400 font-medium text-sm flex items-center gap-2 transition-colors">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg> Syarat &amp; Ketentuan
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div className="flex flex-col gap-4">
                        <h3 className="font-black text-gray-900 dark:text-white tracking-widest text-sm uppercase mb-1">Hubungi Kami</h3>
                        <ul className="space-y-4">
                            <li className="flex items-start gap-3">
                                <div className="mt-0.5 text-orange-500">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </div>
                                <span className="text-gray-500 dark:text-gray-300 font-medium text-sm">Jawa Barat, Indonesia.</span>
                            </li>
                            <li className="flex items-start gap-3">
                                <div className="mt-0.5 text-orange-500">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                </div>
                                <span className="text-gray-500 dark:text-gray-300 font-medium text-sm">+62 823-1745-8496</span>
                            </li>
                        </ul>
                    </div>

                </div>
                
                <div className="mt-12 pt-6 border-t border-gray-100 dark:border-gray-800 flex flex-col md:flex-row items-center justify-between gap-4">
                    <p className="text-gray-500 dark:text-gray-300 text-xs font-medium text-center md:text-left">
                        &copy; {new Date().getFullYear()} {store?.name || 'NandarStore'}. Hak Cipta Dilindungi.
                    </p>
                    <p className="text-gray-500 dark:text-gray-300 text-xs font-medium text-center">
                        Made with <span className="text-red-500">❤</span> by <span className="text-orange-500">{store?.name || 'NandarStore'}</span>
                    </p>
                </div>
            </div>
        </footer>
    );
}

