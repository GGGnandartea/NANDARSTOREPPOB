import React from 'react';
import { Icons, OPERATORS, GAMES, EWALLETS, STREAMING_SERVICES, fmt } from './PpobConstants';
import { BILL_SERVICES } from './PpobBillUtils';
import { PDAM_PRODUCTS } from './PdamProducts';

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
  setSelectedProduct,
  onSwitchTab,
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
  // Tagihan state
  tagihanType,
  setTagihanType,
  tagihanCustomerId,
  setTagihanCustomerId,
  tagihanInquiryResult,
  setTagihanInquiryResult,
  isTagihanInquiring,
  handleTagihanInquiry,
  tagihanPdamRegion,
  setTagihanPdamRegion,
  tagihanPeriod,
  setTagihanPeriod,
  tagihanPdamSearch,
  setTagihanPdamSearch,
}) {
  const activeBillService = BILL_SERVICES.find((s) => s.id === tagihanType) || BILL_SERVICES[0];
  const isBillTab = ['pdam', 'pln_postpaid', 'bpjs'].includes(activeTab);
  return (
    <div className="bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/5 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm space-y-4 sm:space-y-5">
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

      {/* TAB 2: TOKEN LISTRIK PLN (PRABAYAR SAJA — tagihan punya tab sendiri) */}
      {activeTab === 'pln' && (
        <>
          <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <Icons.Pln className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-black text-sm text-gray-900 dark:text-white">Token Listrik Prabayar</h3>
                <p className="text-[11px] text-gray-400 font-medium">Token langsung masuk ke meter</p>
              </div>
            </div>
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
            <p className="text-[10px] text-gray-400 mt-1">
              Masukkan 11-12 digit nomor meteran. Tagihan listrik bulanan ada di menu
              <button
                type="button"
                onClick={() => onSwitchTab && onSwitchTab('pln_postpaid')}
                className="ml-1 font-bold text-emerald-600 hover:underline"
              >
                Tagihan PLN
              </button>.
            </p>
          </div>
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

      {/* TAB: TAGIHAN (PDAM, TAGIHAN PLN, BPJS — masing-masing menu terpisah) */}
      {isBillTab && (
        <>
          <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <Icons.Tagihan className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-black text-sm text-gray-900 dark:text-white">
                  {activeTab === 'pdam' && 'Cek & Bayar Tagihan PDAM'}
                  {activeTab === 'pln_postpaid' && 'Cek & Bayar Tagihan PLN'}
                  {activeTab === 'bpjs' && 'Cek & Bayar BPJS Kesehatan'}
                </h3>
                <p className="text-[11px] text-gray-400 font-medium">
                  {activeTab === 'pdam' && 'Air bersih seluruh kota & kabupaten'}
                  {activeTab === 'pln_postpaid' && 'Listrik pascabayar & non-taglis'}
                  {activeTab === 'bpjs' && 'Iuran BPJS Kesehatan'}
                </p>
              </div>
            </div>
          </div>

          <div className="space-y-3">
            {/* PLN punya dua jenis transaksi: tagihan rutin dan penyambungan baru */}
            {activeTab === 'pln_postpaid' && (
              <div>
                <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                  Jenis Tagihan PLN
                </label>
                <select
                  value={tagihanType}
                  onChange={(e) => {
                    setTagihanType(e.target.value);
                    setTagihanCustomerId('');
                    setTagihanInquiryResult(null);
                    setSelectedProduct(null);
                  }}
                  className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition cursor-pointer"
                >
                  {BILL_SERVICES.filter((s) => s.id === 'pln_postpaid' || s.id === 'pln_nontag').map((service) => (
                    <option key={service.id} value={service.id}>{service.label}</option>
                  ))}
                </select>
              </div>
            )}

            {/* Pilih wilayah PDAM karena setiap kota/kabupaten punya kode produk sendiri */}
            {tagihanType === 'pdam' && (
              <div>
                <div className="flex items-center justify-between mb-1.5">
                  <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider">
                    Wilayah PDAM ({PDAM_PRODUCTS.length})
                  </label>
                  {tagihanPdamSearch && (
                    <button
                      type="button"
                      onClick={() => setTagihanPdamSearch('')}
                      className="text-[10px] font-bold text-emerald-600 hover:underline"
                    >
                      Reset
                    </button>
                  )}
                </div>
                <input
                  type="text"
                  value={tagihanPdamSearch}
                  onChange={(e) => setTagihanPdamSearch(e.target.value)}
                  placeholder="Cari kota / kabupaten..."
                  className="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition mb-2"
                />
                <select
                  value={tagihanPdamRegion}
                  onChange={(e) => {
                    setTagihanPdamRegion(e.target.value);
                    setTagihanInquiryResult(null);
                    setSelectedProduct(null);
                  }}
                  size={6}
                  className="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-[11px] font-semibold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition cursor-pointer"
                >
                  {PDAM_PRODUCTS
                    .filter((p) => p.name.toLowerCase().includes(tagihanPdamSearch.toLowerCase()))
                    .map((p) => (
                      <option key={p.id} value={p.id} className="py-1">{p.name}</option>
                    ))}
                </select>
              </div>
            )}

            {/* BPJS memerlukan jumlah bulan tagihan yang akan dibayar */}
            {tagihanType === 'bpjs' && (
              <div>
                <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                  Jumlah Bulan Tagihan
                </label>
                <select
                  value={tagihanPeriod}
                  onChange={(e) => {
                    setTagihanPeriod(e.target.value);
                    setTagihanInquiryResult(null);
                    setSelectedProduct(null);
                  }}
                  className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition cursor-pointer"
                >
                  {Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0')).map((code) => (
                    <option key={code} value={code}>
                      {parseInt(code, 10)} Bulan
                    </option>
                  ))}
                </select>
              </div>
            )}

            <div>
              <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                Nomor Pelanggan
              </label>
              <input
                type="text"
                value={tagihanCustomerId}
                onChange={(e) => {
                  setTagihanCustomerId(e.target.value.replace(/[^0-9]/g, ''));
                  setTagihanInquiryResult(null);
                  setSelectedProduct(null);
                }}
                placeholder={activeBillService.placeholder || 'Contoh: 123456789'}
                className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
              />
            </div>

            <button
              type="button"
              onClick={handleTagihanInquiry}
              disabled={isTagihanInquiring || !tagihanCustomerId || tagihanCustomerId.length < (activeBillService.minLength || 4)}
              className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/90 dark:hover:bg-emerald-500 dark:text-white font-black text-xs sm:text-sm shadow-md shadow-emerald-950/30 border border-emerald-500/40 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isTagihanInquiring ? (
                <>
                  <svg className="w-5 h-5 animate-spin text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                  </svg>
                  <span>Mengecek Tagihan...</span>
                </>
              ) : (
                <>
                  <Icons.Search className="w-5 h-5" />
                  <span>Cek Tagihan</span>
                </>
              )}
            </button>

            {tagihanInquiryResult?.summary && (
              <div className="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40 text-xs space-y-1.5">
                <div className="flex justify-between">
                  <span className="text-gray-500 font-medium">Nama:</span>
                  <span className="font-black text-gray-900 dark:text-white text-right">{tagihanInquiryResult.summary.customerName}</span>
                </div>
                <div className="flex justify-between pt-1.5 border-t border-emerald-200 dark:border-emerald-800/40">
                  <span className="font-black text-emerald-800 dark:text-emerald-300">Total Bayar:</span>
                  <span className="font-black font-mono text-emerald-600 dark:text-emerald-400">{fmt.rp(tagihanInquiryResult.summary.grandTotal)}</span>
                </div>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
}

