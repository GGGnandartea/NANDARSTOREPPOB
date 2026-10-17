import React from 'react';
import { Icons, PPOB_TABS } from './PpobConstants';

export default function PpobTabNav({ activeTab, onSwitchTab }) {
  return (
    <div className="mt-5 sm:mt-8 grid grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-2.5">
      {PPOB_TABS.map((tab) => {
        const active = activeTab === tab.id;
        const TabIcon = Icons[tab.iconKey] || Icons.Pulsa;
        return (
          <button
            key={tab.id}
            type="button"
            onClick={() => onSwitchTab(tab.id)}
            className={`p-2.5 sm:p-4 rounded-xl sm:rounded-2xl border text-center sm:text-left transition-all duration-200 cursor-pointer flex flex-col items-center sm:items-start justify-between group ${
              active
                ? 'bg-emerald-600 text-white border-emerald-500 shadow-md shadow-emerald-600/20 scale-[1.02] dark:bg-emerald-950/50 dark:text-white dark:border-emerald-500/70 dark:shadow-md dark:shadow-emerald-950/40 dark:ring-1 dark:ring-emerald-500/30'
                : 'bg-white dark:bg-[#131b2e] border-gray-200/80 dark:border-white/5 hover:border-emerald-500/40 text-gray-800 dark:text-gray-300 shadow-xs hover:shadow-sm'
            }`}
          >
            <div className="flex items-center justify-between w-full mb-1 sm:mb-3">
              <div
                className={`w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105 mx-auto sm:mx-0 ${
                  active
                    ? 'bg-white/20 text-white shadow-inner dark:bg-emerald-500/20 dark:text-emerald-300 dark:border dark:border-emerald-500/30'
                    : 'bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 group-hover:bg-emerald-500/10'
                }`}
              >
                <TabIcon className="w-4 h-4 sm:w-5 sm:h-5" />
              </div>
              {active && (
                <span className="hidden sm:inline-block text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-white/20 text-white border border-white/20 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/40">
                  Aktif
                </span>
              )}
            </div>
            <div className="w-full">
              <h3 className="font-black text-[11px] sm:text-sm tracking-tight truncate sm:whitespace-normal">{tab.label}</h3>
              <p className={`text-[10px] font-medium mt-0.5 hidden sm:block ${active ? 'text-white/80 dark:text-emerald-200/70' : 'text-gray-400'}`}>
                {tab.desc}
              </p>
            </div>
          </button>
        );
      })}
    </div>
  );
}

