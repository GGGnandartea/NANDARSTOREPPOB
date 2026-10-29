import React from 'react';
import { Icons, PPOB_TABS } from './PpobConstants';

export default function PpobTabNav({ activeTab, onSwitchTab }) {
  const isPln = activeTab === 'pln' || activeTab === 'pln_postpaid';
  const isPostpaid = activeTab === 'pln_postpaid';

  return (
    <>
      <div className="mt-5 sm:mt-8 grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-7 gap-2 sm:gap-2.5">
        {PPOB_TABS.map((tab) => {
          const active = activeTab === tab.id || (tab.id === 'pln' && isPln);
          const TabIcon = Icons[tab.iconKey] || Icons.Pulsa;
          return (
            <button
              key={tab.id}
              type="button"
              aria-pressed={active}
              onClick={() => onSwitchTab(tab.id)}
              className={`relative overflow-hidden p-2.5 sm:p-4 rounded-xl sm:rounded-2xl border text-center sm:text-left transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] cursor-pointer flex flex-col items-center sm:items-start justify-between group ${
                active
                  ? 'bg-emerald-600 text-white border-emerald-500 shadow-lg shadow-emerald-600/20 -translate-y-0.5 dark:bg-emerald-950/60 dark:text-white dark:border-emerald-500/80 dark:shadow-lg dark:shadow-emerald-950/50 dark:ring-1 dark:ring-emerald-500/40'
                  : 'bg-white dark:bg-[#131b2e] border-gray-200/80 dark:border-white/5 hover:border-emerald-500/40 hover:-translate-y-0.5 active:translate-y-0 text-gray-800 dark:text-gray-300 shadow-xs hover:shadow-md'
              }`}
            >
              <div className="flex items-center justify-between w-full mb-1 sm:mb-3">
                <div
                  className={`w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-110 mx-auto sm:mx-0 ${
                    active
                      ? 'bg-white/20 text-white shadow-inner scale-105 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border dark:border-emerald-500/30'
                      : 'bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 group-hover:bg-emerald-500/10'
                  }`}
                >
                  <TabIcon className="w-4 h-4 sm:w-5 sm:h-5" />
                </div>
                <span
                  className={`hidden sm:inline-flex items-center gap-1 text-[9px] font-black uppercase px-2 py-0.5 rounded-full transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] ${
                    active
                      ? 'opacity-100 scale-100 bg-white/20 text-white border border-white/20 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/40'
                      : 'opacity-0 scale-75 pointer-events-none'
                  }`}
                >
                  <span className="w-1.5 h-1.5 rounded-full bg-current animate-pulse" />
                  Aktif
                </span>
              </div>
              <div className="w-full">
                <h3 className="font-black text-[11px] sm:text-sm tracking-tight truncate sm:whitespace-normal">{tab.label}</h3>
                <p className={`text-[10px] font-medium mt-0.5 hidden sm:block transition-colors duration-200 ${active ? 'text-white/85 dark:text-emerald-200/80' : 'text-gray-400'}`}>
                  {tab.desc}
                </p>
              </div>
            </button>
          );
        })}
      </div>

      {isPln && (
        <div className="ppob-fade-in mt-4 rounded-2xl border border-gray-200/80 dark:border-white/10 bg-white dark:bg-[#131b2e] p-3 sm:p-4 shadow-xs">
          <div className="flex items-center justify-between mb-2.5">
            <p className="text-xs font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Pilih Layanan PLN
            </p>
            <span className="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
              {isPostpaid ? 'Tagihan Bulanan' : 'Token Listrik'}
            </span>
          </div>

          <div
            role="group"
            aria-label="Jenis layanan PLN"
            className="relative grid grid-cols-2 gap-2 rounded-2xl bg-gray-100/90 dark:bg-[#0b0f19] p-1.5 border border-gray-200/60 dark:border-white/5"
          >
            {/* Animated Sliding Pill Indicator */}
            <div
              aria-hidden="true"
              className={`pointer-events-none absolute top-1.5 bottom-1.5 left-1.5 w-[calc(50%-0.5rem)] rounded-xl bg-white dark:bg-emerald-950/80 border border-emerald-500/60 dark:border-emerald-500/50 shadow-md shadow-emerald-500/10 transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] ${
                isPostpaid ? 'translate-x-[calc(100%+0.5rem)]' : 'translate-x-0'
              }`}
            />

            {[
              { id: 'pln', label: 'Prabayar', description: 'Beli token listrik prabayar' },
              { id: 'pln_postpaid', label: 'Pascabayar', description: 'Cek & bayar tagihan listrik' },
            ].map((service) => {
              const selected = activeTab === service.id;
              return (
                <button
                  key={service.id}
                  type="button"
                  aria-pressed={selected}
                  onClick={() => onSwitchTab(service.id)}
                  className={`relative z-10 rounded-xl px-3.5 py-3 text-left transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] cursor-pointer flex items-center justify-between gap-2 ${
                    selected
                      ? 'text-emerald-700 dark:text-emerald-300'
                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                  }`}
                >
                  <div className="min-w-0">
                    <span className="block text-xs sm:text-sm font-black tracking-tight">{service.label}</span>
                    <span className={`mt-0.5 block text-[11px] font-medium truncate ${selected ? 'text-emerald-600/90 dark:text-emerald-300/80' : 'text-gray-400 dark:text-gray-500'}`}>
                      {service.description}
                    </span>
                  </div>
                  <span
                    aria-hidden="true"
                    className={`w-5 h-5 rounded-full flex items-center justify-center shrink-0 transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] ${
                      selected
                        ? 'bg-emerald-600 text-white scale-100 opacity-100 shadow-xs'
                        : 'bg-gray-200 dark:bg-white/10 text-transparent scale-75 opacity-40'
                    }`}
                  >
                    <Icons.Check className="w-3 h-3" />
                  </span>
                </button>
              );
            })}
          </div>
        </div>
      )}
    </>
  );
}
