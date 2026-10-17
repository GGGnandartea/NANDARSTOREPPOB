import React from 'react';

export default function CheckoutProductSummary({ product }) {
  if (!product) return null;

  return (
    <div className="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl mb-6 flex items-center gap-3">
      {product.image && (
        <img
          src={product.image}
          alt={product.name}
          className="w-12 h-12 object-contain rounded-xl bg-white border border-gray-100 p-1 shrink-0"
        />
      )}
      <div className="min-w-0">
        <p className="text-[10px] text-gray-400 font-black uppercase tracking-widest">Layanan Dipilih</p>
        <h3 className="font-black text-gray-900 dark:text-white text-sm leading-tight truncate">{product.name}</h3>
      </div>
    </div>
  );
}

