<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Simulasi cerdas untuk Nomor Pelanggan Uji Coba (Nopel Development) resmi Kiosbank
 * sesuai tabel dokumentasi https://ppob.kiosbank.com:4040/api/webservicesV2/Main/Nopel/*
 * ketika server Development Kiosbank mengembalikan RC 10 / 15 / 61 (sedang tidak aktif).
 */
class KiosbankDevelopmentNopel
{
    /**
     * Daftar Nomor Pelanggan Uji Coba yang menghasilkan Response Code Error pada saat Inquiry.
     * Sumber: Main/Nopel/PDAM, Main/Nopel/LISTRIK, Main/Nopel/BPJS, Main/Nopel/GAS, Main/Nopel/MULTIFINANCE
     */
    protected static array $inquiryErrorNopel = [
        // PDAM (Main/Nopel/PDAM)
        '1300992'          => '14', // PDAM KAB SRAGEN (400181) - RC 14
        '1300991'          => '64', // PDAM KAB SRAGEN (400181) - RC 64
        '1300990'          => '65', // PDAM KAB SRAGEN (400181) - RC 65
        '04010020530'      => '60', // PDAM KAB BANDUNG (400221) - RC 60
        '06020040156'      => '64', // PDAM KAB BANDUNG (400221) - RC 64
        '3050876'          => '80', // PDAM KOTA BANJARMASIN (400231) - RC 80
        '0941020567'       => '64', // PDAM KAB KENDAL (400241) - RC 64
        '0206040020'       => '64', // PDAM KOTA MADIUN (400261) - RC 64
        '08070428'         => '80', // PDAM KAB PURBALINGGA (400271) - RC 80
        '01029480'         => '01', // PDAM KAB BULELENG (400371) - RC 01
        '2018010494~'      => '39', // PDAM GIRI MENANG MATARAM (400381) - RC 39
        '09-01-00748'      => '64', // PDAM GIRI MENANG MATARAM (400381) - RC 64

        // LISTRIK PLN (Main/Nopel/LISTRIK)
        '321200551836'     => '17', // PLN PASCABAYAR (100301) - RC 17
        '520060462215'     => '64', // PLN PASCABAYAR (100301) - RC 64
        '520060463576'     => '64', // PLN PASCABAYAR (100301) - RC 64
        '513570091878'     => '65', // PLN PASCABAYAR (100301) - RC 65
        '520060462121'     => '65', // PLN PASCABAYAR (100301) - RC 65
        '523060849959'     => '70', // PLN PASCABAYAR (100301) - RC 70
        '1412070007467'    => '17', // PLN NON TAGLIS (100311) - RC 17
        '5225512086178'    => '64', // PLN NON TAGLIS (100311) - RC 64
        '5230912052845'    => '64', // PLN NON TAGLIS (100311) - RC 64
        '14260002590'      => '65', // PLN NON TAGLIS (100311) - RC 65
        '515040235043'     => '65', // PLN NON TAGLIS (100311) - RC 65

        // BPJS (Main/Nopel/BPJS)
        '8888801265114064' => '64', // BPJS KESEHATAN (900001) - RC 64
        '8888801463400064' => '64', // BPJS KESEHATAN (900001) - RC 64
        '8888802437858912' => '22', // BPJS KESEHATAN (900001) - RC 22
        '523370420000'     => '65', // BPJS TK PU (999971) - RC 65

        // PGN GAS (Main/Nopel/GAS - kode 100401 / 100411 / 850001)
        '000000501881'     => '65', // PGN (850001) - RC 65
        '000005161029'     => '64', // PGN (850001) - RC 64
        '024778882'        => '17', // PGN (100401) - RC 17
        '016088038'        => '38', // PGN (100401) - RC 38
        '00017270'         => '64', // PGN (100401) - RC 64
        '09128867'         => '65', // PGN (100401) - RC 65
        '9091929390001'    => '17', // PGN PREPAID (100411) - RC 17
        '9091929390065'    => '65', // PGN PREPAID (100411) - RC 65

        // TV BERBAYAR & MULTIFINANCE
        '127200005324'     => '64', // INDOVISION (350001) - RC 64
        '40100181506'      => '65', // K-VISION (350010) - RC 65
        '9950101260'       => '65', // FIF (250001) - RC 65
        '311141026586'     => '64', // WOM (250004) - RC 64
        '011412140114'     => '65', // MCF/MAF (250003) - RC 65
    ];

    /**
     * Daftar Nomor Pelanggan Uji Coba yang menghasilkan RC 71 (Pending / Sedang Diproses) saat Payment.
     * Pada saat Inquiry tetap mengembalikan RC 00 agar bisa lanjut ke tombol Bayar Tagihan.
     */
    protected static array $paymentPendingNopel = [
        // PDAM (Main/Nopel/PDAM)
        '21361152'      => '71', // PDAM KOTA BOGOR (400701) - RC 71 (catatan: 21361152- adalah RC 00)

        // LISTRIK PLN (Main/Nopel/LISTRIK)
        '110000078938'  => '71', // PLN PASCABAYAR (100301) - RC 71
        '110570008867'  => '71', // PLN PASCABAYAR (100301) - RC 71
        '111000514263'  => '71', // PLN PASCABAYAR (100301) - RC 71
        '412600222294'  => '71', // PLN PASCABAYAR (100301) - RC 71
        '5340143009519' => '71', // PLN NON TAGLIS (100311) - RC 71
        '5340143009523' => '71', // PLN NON TAGLIS (100311) - RC 71

        // MULTIFINANCE
        '5390148303'    => '71', // FIF (250001) - RC 71
    ];

    /**
     * Daftar jumlah periode tagihan untuk Nomor Pelanggan PDAM & PLN Pascabayar multi-periode (> 1 periode).
     * Sumber: Kolom "Periode" pada Main/Nopel/PDAM dan Main/Nopel/LISTRIK.
     */
    protected static array $multiPeriodNopel = [
        // PDAM 10 Periode
        '0104095637'   => 10, // PDAM KAB GROBOGAN (400521)

        // PDAM 5 Periode
        '0201050084'   => 5,  // PDAM KAB WONOSOBO (400331)

        // PDAM 3 Periode
        '02011750010'  => 3,  // PDAM KAB KEBUMEN (400021)
        '0601030019'   => 3,  // PDAM KAB PEKALONGAN (400101)
        '0701010227'   => 3,  // PDAM KAB MAGELANG (400151)
        '020108575'    => 3,  // PDAM KAB LOMBOK TENGAH (400311)
        '58765'        => 3,  // PDAM KOTA YOGYAKARTA (400531)
        '39734'        => 3,  // PDAM KAB KLUNGKUNG (400601)
        '03058401'     => 3,  // PDAM KOTA DEPOK (400691)

        // PDAM 2 Periode
        '1134834'      => 2,  // PDAM KAB BANYUMAS (400011)
        '21041266'     => 2,  // PDAM KOTA MAGELANG (400041)
        '36072440'     => 2,  // PDAM KOTA MAGELANG (400041)
        '0107433934'   => 2,  // PDAM KAB CILACAP (400051)
        '13010241'     => 2,  // PDAM KAB BOYOLALI (400081)
        '0310130107'   => 2,  // PDAM KAB JEPARA (400091)
        '0403020075'   => 2,  // PDAM KAB PEKALONGAN (400101)
        '1010900486'   => 2,  // PDAM KAB KARANGANYAR (400121)
        '030804120441' => 2,  // PDAM KAB WONOGIRI (400141)
        '10001113'     => 2,  // PDAM KAB PROBOLINGGO (400171)
        '0807192'      => 2,  // PDAM KAB SRAGEN (400181)
        '01001187'     => 2,  // PDAM KAB BONDOWOSO (400191)
        '113013160'    => 2,  // PDAM KAB SEMARANG (400201)
        '07082400322'  => 2,  // PDAM KAB PURWOREJO (400211)
        '002028'       => 2,  // PDAM KAB BANDUNG (400221)
        '4020290'      => 2,  // PDAM KOTA BANJARMASIN (400231)
        '0721190441'   => 2,  // PDAM KAB KENDAL (400241)
        '00023198'     => 2,  // PDAM KOTA SURAKARTA (400251)
        '0106200012'   => 2,  // PDAM KOTA MADIUN (400261)
        '01010382'     => 2,  // PDAM KAB PURBALINGGA (400271)
        '0701110140'   => 2,  // PDAM KAB BREBES (400341)
        '32522'        => 2,  // PDAM KAB JEMBER (400351)
        '01007339'     => 2,  // PDAM KAB BULELENG (400371)
        '02-05-07592'  => 2,  // PDAM GIRI MENANG MATARAM (400381)
        '0100007870'   => 2,  // PDAM KAB MADIUN (400391)
        '06001454'     => 2,  // PDAM INTAN BANJAR (400401)
        '102041013'    => 2,  // PDAM KAB BLORA (400421)
        '01060589'     => 2,  // PDAM KAB KUDUS (400431)
        '0101470088'   => 2,  // PDAM KAB SUKOHARJO (400511)
        '020260004938' => 2,  // PDAM KOTA DENPASAR (400541)
        '120010003475' => 2,  // PDAM KAB BADUNG (400551)
        '17155'        => 2,  // PDAM KAB KLUNGKUNG (400601)
        '01031783'     => 2,  // PDAM KAB BANYUWANGI (400611)
        '01020050571'  => 2,  // PDAM KAB CIAMIS (400621)
        '03002640'     => 2,  // PDAM KAB CIAMIS (400621)
        '0102020453'   => 2,  // PDAM KAB PONOROGO (400681)
        '03093507'     => 2,  // PDAM KOTA DEPOK (400691)
        '0101029067'   => 2,  // PDAM KOTA TEGAL (400871)
        '0104011063'   => 2,  // PDAM KAB TEGAL (400881)
        '0952404848'   => 2,  // PDAM KOTA TANGERANG (401331)

        // PLN Pascabayar Multi-Periode (Main/Nopel/LISTRIK)
        '511520690546' => 4,
        '521020776478' => 4,
        '521041256674' => 4,
        '524510924259' => 4,
        '543101850819' => 4,
        '546100118916' => 4,
        '321201173578' => 3,
        '521521057379' => 3,
        '522021457644' => 3,
        '522520419118' => 3,
        '538221011220' => 3,
        '321200018159' => 2,
        '511080230037' => 2,
        '521100405609' => 2,
        '523010602488' => 2,
        '525070580172' => 2,
    ];

    /**
     * Daftar Nomor Uji Coba SinglePayment (Pulsa, Uang Elektronik, Voucher Game)
     * yang menghasilkan status Gagal atau Pending.
     */
    protected static array $singlePaymentTestNopel = [
        // PULSA (Main/Nopel/PULSA)
        '081280982287'      => ['status' => 'failed',     'rc' => '67'],
        '081280982297'      => ['status' => 'processing', 'rc' => '71'],

        // UANG ELEKTRONIK (Main/Nopel/UANG ELEKTRONIK)
        '6032982502227794'  => ['status' => 'failed',     'rc' => '62'],
        '6032984049195807'  => ['status' => 'processing', 'rc' => '71'],
        '7512340000002'     => ['status' => 'failed',     'rc' => '62'],
        '7512340000003'     => ['status' => 'processing', 'rc' => '71'],
        '0142880070000002'  => ['status' => 'failed',     'rc' => '62'],
        '0142880070000003'  => ['status' => 'processing', 'rc' => '71'],
        '11111085782221232' => ['status' => 'failed',     'rc' => '62'],
        '11111085782221233' => ['status' => 'processing', 'rc' => '71'],

        // VOUCHER GAME (Main/Nopel/VOUCHER GAME)
        '081234560002'      => ['status' => 'processing', 'rc' => '71'],
    ];

    /**
     * Simulasi Inquiry untuk Developer Mode sesuai tabel Nopel Kiosbank.
     */
    public static function simulateInquiry(
        string $productId,
        string $customerId,
        string $refId,
        array $extraParams,
        KiosbankService $service,
        int $serviceFee,
        int $grandTotal
    ): array {
        // 1. Cek apakah nomor pelanggan termasuk skenario Error RC pada Inquiry
        if (isset(self::$inquiryErrorNopel[$customerId])) {
            $rc = self::$inquiryErrorNopel[$customerId];
            $msg = $service->getResponseCodeMessage($rc);
            return [
                'status'  => 'error',
                'rc'      => $rc,
                'message' => "{$msg} (RC {$rc})",
            ];
        }

        $cleanSuffix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $customerId));
        $customerLabel = 'PELANGGAN ' . (substr($cleanSuffix, -4) ?: $customerId);
        $periods = self::$multiPeriodNopel[$customerId] ?? 1;

        // 2. Simulasi BPJS Kesehatan (900001) / BPJS TK (999971 / 999981)
        if (str_starts_with($productId, '900') || str_starts_with($productId, '999')) {
            $bulan = max(1, min(12, (int) ($extraParams['periode'] ?? 1)));
            $premiPerBulan = 35000;
            $amount = $premiPerBulan * $bulan;
            $admin = 2500;
            $penalty = 0;
            $total = $amount + $admin;
            $fee = max(0, $grandTotal - 52500);

            return [
                'status'        => 'success',
                'rc'            => '00',
                'product_id'    => $productId,
                'customer_id'   => $customerId,
                'customer_name' => $customerLabel,
                'product_name'  => 'BPJS Kesehatan',
                'amount'        => $amount,
                'admin_fee'     => $admin,
                'penalty'       => $penalty,
                'total'         => $total,
                'service_fee'   => $fee,
                'grand_total'   => $total + $fee,
                'reference_id'  => $refId,
                'data'          => [
                    'idPelanggan' => $customerId,
                    'nama'        => $customerLabel,
                    'namaCabang'  => 'KC JAKARTA PUSAT',
                    'periode'     => str_pad((string) $bulan, 2, '0', STR_PAD_LEFT),
                    'peserta'     => '1',
                    'premi'       => str_pad((string) $amount, 12, '0', STR_PAD_LEFT),
                    'admin'       => str_pad((string) $admin, 12, '0', STR_PAD_LEFT),
                    'total'       => str_pad((string) $total, 12, '0', STR_PAD_LEFT),
                    'detail'      => [
                        [
                            'noPeserta' => $customerId,
                            'nama'      => $customerLabel,
                            'premi'     => (string) $amount,
                            'saldo'     => '0',
                        ],
                    ],
                ],
            ];
        }

        // 3. Simulasi PLN Prepaid / Token Listrik (100392 s/d 100362)
        $plnPrepaidMap = [
            '100392' => ['amount' => 5000,     'label' => 'PLN PREPAID 5.000'],
            '1003A2' => ['amount' => 10000,    'label' => 'PLN PREPAID 10.000'],
            '1003B2' => ['amount' => 15000,    'label' => 'PLN PREPAID 15.000'],
            '100302' => ['amount' => 20000,    'label' => 'PLN PREPAID 20.000'],
            '100312' => ['amount' => 20000,    'label' => 'PLN PREPAID 20.000'],
            '1003C2' => ['amount' => 30000,    'label' => 'PLN PREPAID 30.000'],
            '100322' => ['amount' => 50000,    'label' => 'PLN PREPAID 50.000'],
            '100332' => ['amount' => 100000,   'label' => 'PLN PREPAID 100.000'],
            '100342' => ['amount' => 200000,   'label' => 'PLN PREPAID 200.000'],
            '100352' => ['amount' => 500000,   'label' => 'PLN PREPAID 500.000'],
            '100362' => ['amount' => 1000000,  'label' => 'PLN PREPAID 1.000.000'],
        ];
        if (isset($plnPrepaidMap[$productId])) {
            $amount = $plnPrepaidMap[$productId]['amount'];
            $admin = 3000;
            $total = $amount + $admin;
            $fee = max(0, $grandTotal - 52500);
            return [
                'status'        => 'success',
                'rc'            => '00',
                'product_id'    => $productId,
                'customer_id'   => $customerId,
                'customer_name' => $customerLabel,
                'tarif_daya'    => 'R1 / 1300 VA',
                'product_name'  => $plnPrepaidMap[$productId]['label'],
                'amount'        => $amount,
                'admin_fee'     => $admin,
                'penalty'       => 0,
                'total'         => $total,
                'service_fee'   => $fee,
                'grand_total'   => $total + $fee,
                'reference_id'  => $refId,
                'data'          => [
                    'ID' => $customerId,
                    'MN' => $customerId,
                    'NM' => $customerLabel,
                    'TF' => 'R1',
                    'DY' => '000000001300',
                    'TG' => str_pad((string) $amount, 12, '0', STR_PAD_LEFT),
                    'AB' => str_pad((string) $admin, 12, '0', STR_PAD_LEFT),
                    'TT' => str_pad((string) $total, 12, '0', STR_PAD_LEFT),
                ],
            ];
        }

        // 3b. Simulasi Voucher Game (60****, 61****, 63****, 64****, 65****, 67****) sesuai Main/Inquiry/VOUCHER GAME
        if (preg_match('/^(60|61|63|64|65|67)/i', $productId)) {
            $dbProduct = \App\Models\PpobProduct::where('code', $productId)->first();
            $prodName = $dbProduct ? $dbProduct->name : ('VOUCHER GAME ' . $productId);
            $amount = $dbProduct && (int) $dbProduct->base_price > 0 ? (int) $dbProduct->base_price : 15000;
            $fee = max(0, $grandTotal - 52500);
            $additionalId = trim((string) ($extraParams['additional_id'] ?? ''));

            if (str_starts_with(strtoupper($productId), '6707')) {
                $gameCustomerLabel = 'PLAYER MLBB #' . $customerId . ($additionalId !== '' ? ' (Zone: ' . $additionalId . ')' : '');
            } elseif (preg_match('/^67(0[1-9]|10)BK$/i', $productId)) {
                $gameCustomerLabel = 'SURVIVOR FF #' . $customerId;
            } elseif (str_ends_with(strtoupper($productId), 'BV')) {
                $gameCustomerLabel = 'TRAVELER GENSHIN #' . $customerId . ($additionalId !== '' ? ' (' . $additionalId . ')' : '');
            } else {
                $gameCustomerLabel = 'AKUN GAME #' . $customerId . ($additionalId !== '' ? ' (' . $additionalId . ')' : '');
            }

            return [
                'status'        => 'success',
                'rc'            => '00',
                'product_id'    => $productId,
                'customer_id'   => $customerId,
                'additional_id' => $additionalId !== '' ? $additionalId : null,
                'customer_name' => $gameCustomerLabel,
                'product_name'  => $prodName,
                'amount'        => $amount,
                'admin_fee'     => 0,
                'penalty'       => 0,
                'total'         => $amount,
                'service_fee'   => $fee,
                'grand_total'   => $amount + $fee,
                'reference_id'  => $refId,
                'data'          => [
                    'noHandphone' => str_pad($customerId, 20, ' '),
                    'produk'      => str_pad($prodName, 30, ' '),
                    'harga'       => str_pad((string) $amount, 12, '0', STR_PAD_LEFT),
                ],
            ];
        }

        // 3c. Simulasi Uang Elektronik / E-Wallet (75****) sesuai Main/Inquiry/UANG ELEKTRONIK
        if (str_starts_with($productId, '75')) {
            $dbProduct = \App\Models\PpobProduct::where('code', $productId)->first();
            $prodName = $dbProduct ? $dbProduct->name : ('TopUp E-Wallet ' . $productId);
            $operatorName = $dbProduct && $dbProduct->operator ? strtoupper($dbProduct->operator) : 'E-WALLET';
            $amount = $dbProduct && (int) $dbProduct->base_price > 0 ? (int) $dbProduct->base_price : 20000;
            $fee = max(0, $grandTotal - 52500);
            $ewalletCustomerLabel = $operatorName . ' - ' . $customerLabel . ' (' . $customerId . ')';

            return [
                'status'        => 'success',
                'rc'            => '00',
                'product_id'    => $productId,
                'customer_id'   => $customerId,
                'customer_name' => $ewalletCustomerLabel,
                'product_name'  => $prodName,
                'amount'        => $amount,
                'admin_fee'     => 0,
                'penalty'       => 0,
                'total'         => $amount,
                'service_fee'   => $fee,
                'grand_total'   => $amount + $fee,
                'reference_id'  => $refId,
                'data'          => [
                    'noHandphone'   => $customerId,
                    'nama'          => $prodName,
                    'nominalProduk' => str_pad((string) $amount, 12, '0', STR_PAD_LEFT),
                    'harga'         => str_pad((string) $amount, 12, '0', STR_PAD_LEFT),
                ],
            ];
        }

        // 4. Simulasi PLN Pascabayar (100301) & Non-Taglis (100311)
        if ($productId === '100301' || $productId === '100311') {
            $basePerPeriod = 50000;
            $adminPerPeriod = 2500;
            $amount = $basePerPeriod * $periods;
            $admin = $adminPerPeriod * $periods;
            $penalty = 0;
            $total = $amount + $admin;
            $fee = max(0, $grandTotal - 52500);

            $data = [
                'ID' => $customerId,
                'NM' => $customerLabel,
                'TF' => 'R1',
                'DY' => '000001300',
                'LB' => (string) $periods,
                'TG' => str_pad((string) $amount, 12, '0', STR_PAD_LEFT),
                'AB' => str_pad((string) $admin, 12, '0', STR_PAD_LEFT),
                'DD' => str_pad((string) $penalty, 12, '0', STR_PAD_LEFT),
                'TT' => str_pad((string) $total, 12, '0', STR_PAD_LEFT),
            ];

            for ($i = 1; $i <= min(9, $periods); $i++) {
                $monthDate = now()->subMonths($periods - $i);
                $data["P{$i}"] = [
                    'BT' => strtoupper($monthDate->format('My')),
                    'SW' => (string) (1000 + ($i - 1) * 120),
                    'SK' => (string) (1000 + $i * 120),
                    'TG' => str_pad((string) $basePerPeriod, 12, '0', STR_PAD_LEFT),
                    'DD' => '000000000000',
                    'AB' => str_pad((string) $adminPerPeriod, 12, '0', STR_PAD_LEFT),
                ];
            }

            return [
                'status'        => 'success',
                'rc'            => '00',
                'product_id'    => $productId,
                'customer_id'   => $customerId,
                'customer_name' => $customerLabel,
                'product_name'  => $productId === '100301' ? 'PLN Pascabayar' : 'PLN Non-Taglis',
                'amount'        => $amount,
                'admin_fee'     => $admin,
                'penalty'       => $penalty,
                'total'         => $total,
                'service_fee'   => $fee,
                'grand_total'   => $total + $fee,
                'reference_id'  => $refId,
                'data'          => $data,
            ];
        }

        // 4. Simulasi PDAM (400*** / 401***) dan Tagihan Lainnya
        $basePerPeriod = 50000;
        $admin = 2500;
        $amount = $basePerPeriod * $periods;
        $penalty = 0;
        $total = $amount + $admin + $penalty;
        $fee = max(0, $grandTotal - 52500);

        $rincian = [];
        for ($i = 0; $i < $periods; $i++) {
            $periodeStr = now()->subMonths($periods - 1 - $i)->format('Y-m');
            $meterAwal = 120 + ($i * 18);
            $meterAkhir = $meterAwal + 18;
            $rincian[] = [
                'periode'    => $periodeStr,
                'meterAwal'  => (string) $meterAwal,
                'meterAkhir' => (string) $meterAkhir,
                'pemakaian'  => '18 m3',
                'denda'      => '000000000000',
                'admin'      => $i === 0 ? str_pad((string) $admin, 12, '0', STR_PAD_LEFT) : '000000000000',
                'tagihan'    => str_pad((string) $basePerPeriod, 12, '0', STR_PAD_LEFT),
            ];
        }

        return [
            'status'        => 'success',
            'rc'            => '00',
            'product_id'    => $productId,
            'customer_id'   => $customerId,
            'customer_name' => $customerLabel,
            'product_name'  => 'Tagihan PDAM ' . $productId,
            'amount'        => $amount,
            'admin_fee'     => $admin,
            'penalty'       => $penalty,
            'total'         => $total,
            'service_fee'   => $fee,
            'grand_total'   => $total + $fee,
            'reference_id'  => $refId,
            'data'          => [
                'status'     => 'INQUIRY_OK',
                'customerID' => $customerId,
                'nama'       => $customerLabel,
                'alamat'     => 'WILAYAH PELANGGAN ' . $productId,
                'tarif'      => 'R2 / RUMAH TANGGA',
                'jmlTagihan' => (string) $periods,
                'tagihan'    => str_pad((string) $amount, 12, '0', STR_PAD_LEFT),
                'denda'      => str_pad((string) $penalty, 12, '0', STR_PAD_LEFT),
                'admin'      => str_pad((string) $admin, 12, '0', STR_PAD_LEFT),
                'total'      => str_pad((string) $total, 12, '0', STR_PAD_LEFT),
                'rincian'    => $rincian,
            ],
        ];
    }

    /**
     * Simulasi Payment Tagihan (payBill) untuk Developer Mode sesuai tabel Nopel Kiosbank.
     */
    public static function simulatePayBill(
        string $productId,
        string $customerId,
        int $total,
        string $refId,
        KiosbankService $service
    ): array {
        // 1. Cek apakah nomor pelanggan termasuk skenario Pending (RC 71) atau Gagal (RC 62/67) pada saat Payment
        if (isset(self::$paymentPendingNopel[$customerId])) {
            $rc = self::$paymentPendingNopel[$customerId];
            return [
                'status'       => 'processing',
                'rc'           => $rc,
                'reference_id' => $refId,
                'sn'           => null,
                'message'      => $service->getResponseCodeMessage($rc) . " (RC {$rc})",
                'data'         => [
                    'customerID'  => $customerId,
                    'productID'   => $productId,
                    'noReferensi' => $refId,
                    'status'      => 'SEDANG DIPROSES',
                ],
                'is_sandbox'   => true,
            ];
        }

        if (isset(self::$singlePaymentTestNopel[$customerId])) {
            $override = self::$singlePaymentTestNopel[$customerId];
            $rc = $override['rc'] ?? '67';
            if (($override['status'] ?? '') === 'failed') {
                return [
                    'status'     => 'failed',
                    'rc'         => $rc,
                    'message'    => $service->getResponseCodeMessage($rc) . " (RC {$rc})",
                    'is_sandbox' => true,
                ];
            }
            return [
                'status'       => 'processing',
                'rc'           => $rc,
                'reference_id' => $refId,
                'sn'           => null,
                'message'      => $service->getResponseCodeMessage($rc) . " (RC {$rc})",
                'data'         => [
                    'customerID'  => $customerId,
                    'productID'   => $productId,
                    'noReferensi' => $refId,
                    'status'      => 'SEDANG DIPROSES',
                ],
                'is_sandbox'   => true,
            ];
        }

        // 2. Kurangi saldo sandbox jika mencukupi
        $currentSandbox = (int) (Setting::where('key', 'kiosbank_sandbox_deposit')->value('value') ?? 11251850);
        if (!app()->runningUnitTests() && $currentSandbox < $total) {
            $currentSandbox = 50000000;
            Setting::updateOrCreate(['key' => 'kiosbank_sandbox_deposit'], ['value' => (string) $currentSandbox]);
        }
        if ($currentSandbox >= $total && $total > 0) {
            Setting::updateOrCreate(
                ['key' => 'kiosbank_sandbox_deposit'],
                ['value' => (string) max(0, $currentSandbox - $total)]
            );
        }

        $isPlnPrepaid = in_array($productId, ['100392', '1003A2', '1003B2', '100302', '100312', '1003C2', '100322', '100332', '100342', '100352', '100362'], true);
        if ($isPlnPrepaid) {
            $rawToken = str_pad((string) random_int(1000000000, 9999999999) . (string) random_int(1000000000, 9999999999), 20, '0');
            $sn = implode('-', str_split($rawToken, 4));
            return [
                'status'       => 'success',
                'rc'           => '00',
                'reference_id' => $refId,
                'sn'           => $sn,
                'message'      => 'Pembelian Token PLN berhasil.',
                'data'         => [
                    'status'      => 'BERHASIL',
                    'ID'          => $customerId,
                    'MN'          => $customerId,
                    'NM'          => 'PELANGGAN ' . substr($customerId, -4),
                    'TK'          => $rawToken,
                    'RF'          => '0BAG' . strtoupper(bin2hex(random_bytes(8))),
                    'TT'          => str_pad((string) $total, 12, '0', STR_PAD_LEFT),
                ],
                'is_sandbox'   => true,
            ];
        }

        $sn = 'SANDBOX-' . strtoupper(bin2hex(random_bytes(5)));

        return [
            'status'       => 'success',
            'rc'           => '00',
            'reference_id' => $refId,
            'sn'           => $sn,
            'message'      => 'Tagihan berhasil dibayar.',
            'data'         => [
                'status'      => 'BERHASIL',
                'customerID'  => $customerId,
                'productID'   => $productId,
                'noReferensi' => $sn,
            ],
            'is_sandbox'   => true,
        ];
    }

    /**
     * Cek apakah nomor pelanggan SinglePayment termasuk Nopel Gagal / Pending.
     */
    public static function getSinglePaymentTestOverride(string $customerId): ?array
    {
        return self::$singlePaymentTestNopel[$customerId] ?? null;
    }
}

