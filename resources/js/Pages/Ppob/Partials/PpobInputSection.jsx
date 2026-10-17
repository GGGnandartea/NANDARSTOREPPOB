import React from 'react';
import Swal from 'sweetalert2';
import { Icons, OPERATORS, GAMES, EWALLETS, STREAMING_SERVICES, fmt } from './PpobConstants';

export default function PpobInputSection({
  activeTab,
  // Pulsa state
  phoneNumber,
  setPhoneNumber,
  selectedOperator,
  setSelectedOperator,
  // PLN state
  plnType,
  setPlnType,
  plnCustomerId,
  setPlnCustomerId,
  plnInquiryResult,
  setPlnInquiryResult,
  isPlnInquiring,
  handlePlnInquiry,
  setSelectedProduct,
  // Game state
  selectedGame,
  setSelectedGame,
  gameUserId,
  setGameUserId,
  gameZoneId,
  setGameZoneId,
  // E-Wallet state
  selectedEwallet,
  setSelectedEwallet,
  ewalletPhone,
  setEwalletPhone,
  // Streaming state
  selectedStreaming,
  setSelectedStreaming,
  streamingAccount,
  setStreamingAccount,
  streamingSearch,
  setStreamingSearch,
}) {
  return (
    <div className="bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-sm space-y-5">
      {/* TAB 1: PULSA & DATA */}
      {activeTab === 'pulsa' && (
        <>
          <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <Icons.Pulsa className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-black text-sm text-gray-900 dark:text-white">Nomor Tujuan</h3>
                <p className="text-[11px] text-gray-400 font-medium">Operator terdeteksi otomatis</p>
              </div>
            </div>
            <span className="text-xs font-black uppercase px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
              {selectedOperator.name}
            </span>
          </div>

          <div>
            <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
              Nomor Handphone
            </label>
            <div className="relative">
              <input
                type="tel"
                value={phoneNumber}
                onChange={(e) => setPhoneNumber(e.target.value.replace(/[^0-9]/g, ''))}
                placeholder="Contoh: 081234567890"
                className="w-full pl-4 pr-12 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
              />
              {phoneNumber.length >= 4 && (
                <div className="absolute right-3.5 top-3.5 text-xs font-bold px-2.5 py-0.5 rounded-lg bg-emerald-600 text-white dark:bg-emerald-500/20 dark:text-emerald-300 dark:border dark:border-emerald-500/40 shadow-xs">
                  {selectedOperator.name.split(' ')[0]}
                </div>
              )}
            </div>
          </div>

          {/* Manual Operator Switcher */}
          <div>
            <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-2">
              Pilih Operator Manual
            </label>
            <div className="grid grid-cols-3 gap-2">
              {OPERATORS.map((op) => {
                const isSelected = selectedOperator.id === op.id;
                return (
                  <button
                    key={op.id}
                    type="button"
                    onClick={() => setSelectedOperator(op)}
                    className={`p-2.5 rounded-xl border text-center transition cursor-pointer text-xs font-black ${
                      isSelected
                        ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/60 dark:shadow-xs'
                        : 'bg-gray-50 dark:bg-gray-900/60 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:border-emerald-500/40'
                    }`}
                  >
                    {op.name.split(' ')[0]}
                  </button>
                );
              })}
            </div>
          </div>
        </>
      )}

      {/* TAB 2: TOKEN LISTRIK PLN */}
      {activeTab === 'pln' && (
        <>
          <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <Icons.Pln className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-black text-sm text-gray-900 dark:text-white">Layanan Listrik PLN</h3>
                <p className="text-[11px] text-gray-400 font-medium">Token Prabayar &amp; Tagihan Pasca</p>
              </div>
            </div>
          </div>

          {/* Switch Token vs Pasca */}
          <div className="grid grid-cols-2 gap-2 p-1 rounded-2xl bg-gray-100 dark:bg-gray-900 border border-gray-200/60 dark:border-white/5">
            <button
              type="button"
              onClick={() => {
                setPlnType('prepaid');
                setPlnInquiryResult(null);
              }}
              className={`py-2.5 rounded-xl text-xs font-black transition cursor-pointer ${
                plnType === 'prepaid'
                  ? 'bg-white dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 dark:border dark:border-emerald-500/40 shadow-sm'
                  : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
              }`}
            >
              Token Prabayar
            </button>
            <button
              type="button"
              onClick={() => {
                setPlnType('postpaid');
                setSelectedProduct(null);
              }}
              className={`py-2.5 rounded-xl text-xs font-black transition cursor-pointer ${
                plnType === 'postpaid'
                  ? 'bg-white dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 dark:border dark:border-emerald-500/40 shadow-sm'
                  : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
              }`}
            >
              Tagihan Pasca
            </button>
          </div>

          <div>
            <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
              Nomor Meter / ID Pelanggan
            </label>
            <input
              type="text"
              value={plnCustomerId}
              onChange={(e) => setPlnCustomerId(e.target.value.replace(/[^0-9]/g, ''))}
              placeholder="Contoh: 14012345678"
              className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
            />
            <p className="text-[10px] text-gray-400 mt-1">Masukkan 11-12 digit nomor meteran atau ID PLN Anda.</p>
          </div>

          {/* TOMBOL CEK TAGIHAN ELEGAN & JELAS */}
          {plnType === 'postpaid' && (
            <button
              type="button"
              onClick={handlePlnInquiry}
              disabled={isPlnInquiring || !plnCustomerId}
              className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/90 dark:hover:bg-emerald-500 dark:text-white font-black text-xs sm:text-sm shadow-md shadow-emerald-950/30 border border-emerald-500/40 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isPlnInquiring ? (
                <>
                  <svg className="w-5 h-5 animate-spin text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                  </svg>
                  <span>Mengecek Tagihan PLN...</span>
                </>
              ) : (
                <>
                  <Icons.Search className="w-5 h-5" />
                  <span>Cek Tagihan Listrik PLN</span>
                </>
              )}
            </button>
          )}

          {plnInquiryResult && (
            <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40 text-xs space-y-2">
              <div className="flex justify-between">
                <span className="text-gray-500 font-medium">Nama Pelanggan:</span>
                <span className="font-black text-gray-900 dark:text-white">{plnInquiryResult.customer_name}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500 font-medium">Jumlah Tagihan:</span>
                <span className="font-mono font-bold text-gray-900 dark:text-white">{fmt.rp(plnInquiryResult.amount)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500 font-medium">Admin Bank:</span>
                <span className="font-mono font-bold text-gray-900 dark:text-white">{fmt.rp(plnInquiryResult.admin_fee)}</span>
              </div>
              <div className="flex justify-between pt-2 border-t border-emerald-200 dark:border-emerald-800/40">
                <span className="font-black text-emerald-800 dark:text-emerald-300">Total Pembayaran:</span>
                <span className="font-black font-mono text-base text-emerald-600 dark:text-emerald-400">{fmt.rp(plnInquiryResult.total)}</span>
              </div>
            </div>
          )}
        </>
      )}

      {/* TAB 3: GAME */}
      {activeTab === 'game' && (
        <>
          <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <Icons.Game className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-black text-sm text-gray-900 dark:text-white">Pilih Game Favorit</h3>
                <p className="text-[11px] text-gray-400 font-medium">Top up otomatis masuk ke akun</p>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-2">
            {GAMES.map((game) => {
              const isSelected = selectedGame.id === game.id;
              return (
                <button
                  key={game.id}
                  type="button"
                  onClick={() => {
                    setSelectedGame(game);
                    setSelectedProduct(null);
                  }}
                  className={`p-3 rounded-xl border text-left transition cursor-pointer flex items-center gap-2.5 ${
                    isSelected
                      ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/60'
                      : 'bg-gray-50 dark:bg-gray-900/60 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:border-emerald-500/40'
                  }`}
                >
                  <span className={`text-[9px] font-black uppercase px-1.5 py-0.5 rounded-md ${
                    isSelected ? 'bg-white/20 text-white dark:bg-emerald-500/30 dark:text-emerald-300' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                  }`}>
                    {game.badge}
                  </span>
                  <span className="text-xs font-black truncate">{game.name}</span>
                </button>
              );
            })}
          </div>

          <div className="space-y-3 pt-2">
            <div>
              <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                User ID Game
              </label>
              <input
                type="text"
                value={gameUserId}
                onChange={(e) => setGameUserId(e.target.value)}
                placeholder={selectedGame.placeholderId || 'Masukkan User ID'}
                className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
              />
            </div>

            {selectedGame.needZone && (
              <div>
                <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                  {selectedGame.zoneLabel || 'Zone ID / Server'}
                </label>
                {selectedGame.servers ? (
                  <select
                    value={gameZoneId}
                    onChange={(e) => setGameZoneId(e.target.value)}
                    className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
                  >
                    <option value="">Pilih Server</option>
                    {selectedGame.servers.map((s) => (
                      <option key={s} value={s}>{s}</option>
                    ))}
                  </select>
                ) : (
                  <input
                    type="text"
                    value={gameZoneId}
                    onChange={(e) => setGameZoneId(e.target.value)}
                    placeholder={selectedGame.placeholderZone || 'Zone ID'}
                    className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
                  />
                )}
              </div>
            )}
          </div>
        </>
      )}

      {/* TAB 4: E-WALLET */}
      {activeTab === 'ewallet' && (
        <>
          <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <Icons.Ewallet className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-black text-sm text-gray-900 dark:text-white">Top Up E-Wallet</h3>
                <p className="text-[11px] text-gray-400 font-medium">DANA, GoPay, OVO, ShopeePay</p>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-2">
            {EWALLETS.map((ew) => {
              const isSelected = selectedEwallet.id === ew.id;
              return (
                <button
                  key={ew.id}
                  type="button"
                  onClick={() => {
                    setSelectedEwallet(ew);
                    setSelectedProduct(null);
                  }}
                  className={`p-3.5 rounded-xl border text-center transition cursor-pointer flex items-center justify-center gap-2 ${
                    isSelected
                      ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/60'
                      : 'bg-gray-50 dark:bg-gray-900/60 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:border-emerald-500/40'
                  }`}
                >
                  <span className={`text-[10px] font-black px-2 py-0.5 rounded-md ${
                    isSelected ? 'bg-white/20 text-white dark:bg-emerald-500/30 dark:text-emerald-300' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                  }`}>
                    {ew.badge}
                  </span>
                  <span className="text-xs font-black">{ew.name}</span>
                </button>
              );
            })}
          </div>

          <div>
            <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
              Nomor HP Terdaftar di {selectedEwallet.name}
            </label>
            <input
              type="tel"
              value={ewalletPhone}
              onChange={(e) => setEwalletPhone(e.target.value.replace(/[^0-9]/g, ''))}
              placeholder="Contoh: 081234567890"
              className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
            />
          </div>
        </>
      )}

      {/* TAB 5: APLIKASI STREAMING */}
      {activeTab === 'streaming' && (
        <>
          <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <Icons.Streaming className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-black text-sm text-gray-900 dark:text-white">Aplikasi Streaming</h3>
                <p className="text-[11px] text-gray-400 font-medium">Voucher &amp; Langganan Premium Resmi</p>
              </div>
            </div>
            <span className="text-xs font-black uppercase px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
              {selectedStreaming.name}
            </span>
          </div>

          {/* Filter / Search Streaming Service */}
          <div>
            <div className="flex items-center justify-between mb-1.5">
              <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider">
                Pilih Layanan Streaming ({STREAMING_SERVICES.length})
              </label>
              {streamingSearch && (
                <button
                  type="button"
                  onClick={() => setStreamingSearch('')}
                  className="text-[10px] font-bold text-emerald-600 hover:underline"
                >
                  Reset
                </button>
              )}
            </div>
            <input
              type="text"
              value={streamingSearch}
              onChange={(e) => setStreamingSearch(e.target.value)}
              placeholder="Cari: Spotify, Netflix, Vidio..."
              className="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition mb-2.5"
            />

            {/* Scrollable grid for streaming services */}
            <div className="grid grid-cols-2 gap-2 max-h-56 overflow-y-auto pr-1 custom-scrollbar">
              {STREAMING_SERVICES.filter((s) =>
                s.name.toLowerCase().includes(streamingSearch.toLowerCase())
              ).map((stream) => {
                const isSelected = selectedStreaming.id === stream.id;
                return (
                  <button
                    key={stream.id}
                    type="button"
                    onClick={() => {
                      setSelectedStreaming(stream);
                      setSelectedProduct(null);
                    }}
                    className={`p-2.5 rounded-xl border text-left transition cursor-pointer flex items-center gap-2 ${
                      isSelected
                        ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/60'
                        : 'bg-gray-50 dark:bg-gray-900/60 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:border-emerald-500/40'
                    }`}
                  >
                    <span className={`text-[8px] font-black uppercase px-1.5 py-0.5 rounded ${
                      isSelected ? 'bg-white/20 text-white dark:bg-emerald-500/30 dark:text-emerald-300' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                    }`}>
                      {stream.badge}
                    </span>
                    <span className="text-xs font-black truncate">{stream.name}</span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Input Akun / Email / No HP Tujuan */}
          <div>
            <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
              {selectedStreaming.placeholder || 'Email / Nomor HP / Akun Tujuan'}
            </label>
            <input
              type="text"
              value={streamingAccount}
              onChange={(e) => setStreamingAccount(e.target.value)}
              placeholder={selectedStreaming.placeholder || 'Masukkan akun tujuan'}
              className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
            />
            <p className="text-[10px] text-gray-400 mt-1">
              Kode voucher atau konfirmasi aktivasi {selectedStreaming.name} dikirimkan instan via sistem &amp; WhatsApp.
            </p>
          </div>
        </>
      )}

      {/* TAB 6: TAGIHAN PDAM & BPJS */}
      {activeTab === 'tagihan' && (
        <>
          <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <Icons.Tagihan className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-black text-sm text-gray-900 dark:text-white">Tagihan Rutin &amp; PDAM</h3>
                <p className="text-[11px] text-gray-400 font-medium">Air PDAM, BPJS Kesehatan</p>
              </div>
            </div>
          </div>

          <div className="space-y-3">
            <div>
              <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                Jenis Layanan
              </label>
              <select className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                <option value="pdam">PDAM Air Bersih (Kota / Kabupaten)</option>
                <option value="bpjs">BPJS Kesehatan</option>
              </select>
            </div>

            <div>
              <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                Nomor Pelanggan
              </label>
              <input
                type="text"
                placeholder="Contoh: 123456789"
                className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
              />
            </div>

            <button
              type="button"
              onClick={() =>
                Swal.fire({
                  icon: 'info',
                  title: 'Inquiry PDAM',
                  text: 'Silakan masukkan nomor pelanggan PDAM Anda.',
                  confirmButtonColor: '#10b981',
                })
              }
              className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/90 dark:hover:bg-emerald-500 dark:text-white font-black text-xs sm:text-sm shadow-md shadow-emerald-950/30 border border-emerald-500/40 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
              <Icons.Search className="w-4 h-4" />
              <span>Cek Tagihan Air PDAM</span>
            </button>
          </div>
        </>
      )}
    </div>
  );
}

