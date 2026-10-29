// Normalisasi respons inquiry Kiosbank yang formatnya berbeda per biller:
// PDAM (rincian), PLN Pascabayar (I1..I4 / P1..P4), PLN Non-Taglis (JT/NR), BPJS (detail).

/** Kiosbank mengirim nominal sebagai string zero-padded: "000000091300" atau "000000000,00". */
export const parseAmount = (value) => {
  if (value === null || value === undefined || value === '') return 0;
  if (typeof value === 'number') return Math.trunc(value);
  // Kiosbank memakai koma sebagai pemisah desimal: "000002440,00".
  const raw = String(value);
  const integerPart = raw.includes(',') ? raw.slice(0, raw.indexOf(',')) : raw;
  const digits = integerPart.replace(/[^0-9-]/g, '');
  return digits === '' ? 0 : parseInt(digits, 10);
};

/** Susun daftar periode tagihan dari struktur respons biller. */
export const extractBillRows = (data) => {
  if (!data || typeof data !== 'object') return [];

  if (Array.isArray(data.rincian)) {
    return data.rincian.map((row) => ({
      periode: row.periode,
      meterAwal: row.meterAwal,
      meterAkhir: row.meterAkhir,
      pemakaian: row.pemakaian,
      denda: parseAmount(row.denda),
      tagihan: parseAmount(row.tagihan),
    }));
  }

  // PLN Pascabayar memakai kunci I1..I9 (dokumentasi lama) atau P1..P9 (dokumentasi baru).
  const rows = [];
  for (let i = 1; i <= 9; i++) {
    const row = data[`I${i}`] || data[`P${i}`];
    if (!row || typeof row !== 'object') continue;
    rows.push({
      periode: row.BT || row.periode,
      meterAwal: row.SW || null,
      meterAkhir: row.SK || null,
      pemakaian: row.pemakaian || null,
      denda: parseAmount(row.DD ?? row.denda),
      tagihan: parseAmount(row.TG ?? row.tagihan),
    });
  }
  return rows;
};

/** Ringkasan tagihan: nominal, admin, denda, dan total yang harus dibayar. */
export const extractBillSummary = (inquiry) => {
  const data = inquiry?.data || {};

  const detail = Array.isArray(data.detail) ? data.detail : [];
  const premiFromDetail = detail.reduce((sum, item) => sum + parseAmount(item.premi), 0);

  const amount = inquiry?.amount ?? parseAmount(data.tagihan ?? data.TG ?? data.premi ?? premiFromDetail);
  const adminFee = inquiry?.admin_fee ?? parseAmount(data.admin ?? data.adminBank ?? data.AB);
  const penalty = inquiry?.penalty ?? parseAmount(data.denda ?? data.DD);
  const totalFromBiller = parseAmount(data.total ?? data.TT);
  const total = inquiry?.total ?? (totalFromBiller > 0 ? totalFromBiller : amount + adminFee + penalty);
  // grand_total = total biller + markup toko; inilah yang dibayar pelanggan.
  const grandTotal = inquiry?.grand_total ?? total;

  return {
    customerId: data.customerID ?? data.ID ?? data.NR ?? data.idPelanggan ?? inquiry?.customer_id ?? '-',
    customerName: (data.nama ?? data.NM ?? inquiry?.customer_name ?? 'Pelanggan').trim(),
    address: (data.alamat ?? '').trim() || null,
    branch: (data.namaCabang ?? '').trim() || null,
    transactionType: (data.JT ?? '').trim() || null,
    registrationNumber: (data.NR ?? '').trim() || null,
    tariff: (data.TF ?? data.tarif ?? '').trim() || null,
    power: parseAmount(data.DY),
    meterNumber: (data.MN ?? '').trim() || null,
    billCount: parseInt(data.jmlTagihan ?? data.LB ?? data.peserta ?? 0, 10) || 0,
    amount,
    adminFee,
    penalty,
    total,
    grandTotal,
    serviceFee: inquiry?.service_fee ?? Math.max(0, grandTotal - total),
    referenceId: inquiry?.reference_id ?? null,
  };
};

/**
 * Jenis layanan tagihan yang didukung Kiosbank beserta kode produknya.
 * PDAM memakai kode wilayah dari PdamProducts.js.
 */
export const BILL_SERVICES = [
  { id: 'pdam', label: 'PDAM Air Bersih (Kota / Kabupaten)', productId: null, placeholder: 'Contoh: 123456789', minLength: 4 },
  { id: 'pln_postpaid', label: 'PLN Pascabayar (Tagihan Listrik)', productId: '100301', placeholder: 'Contoh: 551600530024', minLength: 8 },
  { id: 'bpjs', label: 'BPJS Kesehatan', productId: '900001', placeholder: 'Contoh: 8888800020839792', minLength: 8 },
];
