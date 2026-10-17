import React from 'react';

export default function TermsModal({ showTerms, setShowTerms, terms }) {
    if (!showTerms) return null;

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
            <div className="bg-white dark:bg-gray-800 rounded-3xl w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl overflow-hidden animate-fade-in-up border border-gray-100 dark:border-gray-700">
                <div className="p-5 sm:p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                    <h3 className="text-lg sm:text-xl font-extrabold dark:text-white">Syarat &amp; Ketentuan</h3>
                    <button onClick={() => setShowTerms(false)} className="p-2 text-gray-400 hover:text-red-500 transition-colors bg-white dark:bg-gray-700 rounded-full shadow-sm cursor-pointer" aria-label="Tutup Syarat Ketentuan">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div className="p-5 sm:p-6 overflow-y-auto flex-1 bg-white dark:bg-gray-800">
                    <div className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-medium" dangerouslySetInnerHTML={{ __html: terms || 'Belum ada syarat dan ketentuan.' }} />
                </div>
            </div>
        </div>
    );
}
