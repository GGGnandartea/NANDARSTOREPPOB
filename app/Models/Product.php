<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Product extends Model {
    protected $fillable = [
        'name',
        'description',
        'price',
        'reseller_price',
        'type',
        'process_type',
        'api_product_id',
        'api_price',
        'api_margin',
        'api_reseller_margin',
        'image',
        'stock',
        'status',
        'category',
        'is_promo',
    ];

    /**
     * Pastikan kolom api_price, api_margin, dan api_reseller_margin tersedia di tabel products.
     */
    public static function ensurePricingColumns(): void
    {
        if (Cache::get('products_pricing_columns_v1')) {
            return;
        }

        try {
            if (Schema::hasTable('products')) {
                Schema::table('products', function (Blueprint $table) {
                    if (!Schema::hasColumn('products', 'api_price')) {
                        $table->integer('api_price')->nullable();
                    }
                    if (!Schema::hasColumn('products', 'api_margin')) {
                        $table->integer('api_margin')->nullable();
                    }
                    if (!Schema::hasColumn('products', 'api_reseller_margin')) {
                        $table->integer('api_reseller_margin')->nullable();
                    }
                });
                Cache::put('products_pricing_columns_v1', true, 86400);
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Ambil pengaturan margin (untung) otomatis realtime untuk produk API.
     */
    public static function getAutoMarginSettings(): array
    {
        try {
            $memberMargin = Setting::where('key', 'api_auto_margin_member')->value('value');
            $resellerMargin = Setting::where('key', 'api_auto_margin_reseller')->value('value');
        } catch (\Throwable $e) {
            $memberMargin = null;
            $resellerMargin = null;
        }

        return [
            'member_margin'   => ($memberMargin !== null && $memberMargin !== '' && (int) $memberMargin >= 500) ? (int) $memberMargin : 10000,
            'reseller_margin' => ($resellerMargin !== null && $resellerMargin !== '' && (int) $resellerMargin >= 500) ? (int) $resellerMargin : 5000,
        ];
    }

    /**
     * Sinkronisasi realtime harga modal API (api_price), margin keuntungan (api_margin),
     * harga jual member (price), harga reseller (reseller_price), stok (stock), dan deskripsi (description).
     *
     * Ketika harga modal atau deskripsi di provider API berubah, sistem otomatis menyesuaikan
     * secara realtime agar keuntungan dan informasi produk selalu akurat.
     */
    public static function syncLiveApiProduct(
        self $product,
        ?int $liveStock = null,
        ?int $liveApiPrice = null,
        bool $persist = true,
        ?array $marginSettings = null,
        ?string $liveDescription = null
    ): bool {
        if ($product->type !== 'api') {
            return false;
        }

        self::ensurePricingColumns();

        if ($product->exists && !array_key_exists('api_margin', $product->getAttributes())) {
            try {
                $dbPricing = self::whereKey($product->id)->first(['api_price', 'api_margin', 'reseller_price', 'api_reseller_margin', 'description']);
                if ($dbPricing) {
                    $product->api_price           = $dbPricing->api_price;
                    $product->api_margin          = $dbPricing->api_margin;
                    $product->reseller_price      = $dbPricing->reseller_price;
                    $product->api_reseller_margin = $dbPricing->api_reseller_margin;
                    if (!array_key_exists('description', $product->getAttributes())) {
                        $product->description = $dbPricing->description;
                    }
                }
            } catch (\Throwable $e) {}
        }

        $updates = [];

        if ($liveStock !== null) {
            $cleanStock = max(0, $liveStock);
            if ((int) $product->getRawOriginal('stock') !== $cleanStock) {
                $updates['stock'] = $cleanStock;
            }
            $product->stock = $cleanStock;
        }

        if ($liveDescription !== null && trim($liveDescription) !== '') {
            $cleanDesc = self::cleanText(trim($liveDescription));
            if ($cleanDesc !== null && $cleanDesc !== '' && trim((string) ($product->description ?? '')) !== trim($cleanDesc)) {
                $updates['description'] = $cleanDesc;
                $product->description = $cleanDesc;
            }
        }

        if ($liveApiPrice !== null && $liveApiPrice > 0) {
            $settings = $marginSettings ?? self::getAutoMarginSettings();
            $defaultMemberMargin = max(500, (int) ($settings['member_margin'] ?? 10000));
            $defaultResellerMargin = max(500, (int) ($settings['reseller_margin'] ?? 5000));

            $oldApiPrice = max(0, (int) ($product->api_price ?? 0));
            $oldSellingPrice = max(0, (int) ($product->price ?? 0));
            $storedMargin = max(0, (int) ($product->api_margin ?? 0));

            // Tentukan margin keuntungan member untuk produk ini
            if ($storedMargin > 0) {
                $effectiveMargin = $storedMargin;
            } elseif ($oldApiPrice > 0 && $oldSellingPrice > $oldApiPrice) {
                $effectiveMargin = $oldSellingPrice - $oldApiPrice;
            } else {
                // Jika api_price belum pernah tercatat (NULL/0) atau harga jual <= harga modal:
                $currentDiff = $oldSellingPrice - $liveApiPrice;
                $effectiveMargin = ($currentDiff >= $defaultMemberMargin) ? $currentDiff : $defaultMemberMargin;
            }

            $newSellingPrice = $liveApiPrice + $effectiveMargin;

            if ($oldApiPrice !== $liveApiPrice) {
                $updates['api_price'] = $liveApiPrice;
                $product->api_price = $liveApiPrice;
            }
            if ($storedMargin !== $effectiveMargin) {
                $updates['api_margin'] = $effectiveMargin;
                $product->api_margin = $effectiveMargin;
            }
            if ($oldSellingPrice !== $newSellingPrice) {
                $updates['price'] = $newSellingPrice;
                $product->price = $newSellingPrice;
            }

            // Sinkronisasi Harga Reseller VIP jika produk memiliki harga reseller
            $oldResellerPrice = ($product->reseller_price !== null && (int) $product->reseller_price > 0)
                ? (int) $product->reseller_price
                : null;

            if ($oldResellerPrice !== null) {
                $storedResellerMargin = max(0, (int) ($product->api_reseller_margin ?? 0));
                if ($storedResellerMargin > 0) {
                    $effectiveResellerMargin = $storedResellerMargin;
                } elseif ($oldApiPrice > 0 && $oldResellerPrice > $oldApiPrice) {
                    $effectiveResellerMargin = $oldResellerPrice - $oldApiPrice;
                } else {
                    $resDiff = $oldResellerPrice - $liveApiPrice;
                    $effectiveResellerMargin = ($resDiff >= $defaultResellerMargin) ? $resDiff : $defaultResellerMargin;
                }

                $newResellerPrice = $liveApiPrice + $effectiveResellerMargin;
                if ($newResellerPrice > $product->price) {
                    $product->price = $newResellerPrice;
                    $updates['price'] = $newResellerPrice;
                }

                if ($storedResellerMargin !== $effectiveResellerMargin) {
                    $updates['api_reseller_margin'] = $effectiveResellerMargin;
                    $product->api_reseller_margin = $effectiveResellerMargin;
                }
                if ($oldResellerPrice !== $newResellerPrice) {
                    $updates['reseller_price'] = $newResellerPrice;
                    $product->reseller_price = $newResellerPrice;
                }
            }
        }

        if (!empty($updates) && $persist && $product->exists) {
            try {
                self::whereKey($product->id)->update($updates);
            } catch (\Throwable $e) {
                // Fallback jika kolom api_margin belum termigrasi di DB tertentu
                try {
                    unset($updates['api_margin'], $updates['api_reseller_margin']);
                    if (!empty($updates)) {
                        self::whereKey($product->id)->update($updates);
                    }
                } catch (\Throwable $e2) {}
            }
        }

        return !empty($updates);
    }

    /**
     * Sinkronkan seluruh produk API di database dengan peta data live (stok & harga modal) dari provider.
     */
    public static function syncAllApiProductsFromLiveMap(array $liveMap, ?array $marginSettings = null): int
    {
        if (empty($liveMap)) {
            return 0;
        }

        self::ensurePricingColumns();
        $settings = $marginSettings ?? self::getAutoMarginSettings();
        $adjustedCount = 0;

        try {
            $apiProducts = self::where('type', 'api')->get();
            foreach ($apiProducts as $product) {
                $apiId = (string) ($product->api_product_id ?? '');
                if ($apiId === '' || !isset($liveMap[$apiId]) || !is_array($liveMap[$apiId])) {
                    continue;
                }

                $item = $liveMap[$apiId];
                // Abaikan item fallback agar estimasi tidak menimpa harga asli
                if (!empty($item['is_fallback'])) {
                    continue;
                }

                $liveStock = isset($item['stock']) ? max(0, (int) $item['stock']) : null;
                $rawLivePrice = $item['price'] ?? ($item['original_price'] ?? null);
                $liveApiPrice = ($rawLivePrice !== null && (int) $rawLivePrice > 0) ? (int) $rawLivePrice : null;
                $rawLiveDesc = $item['description'] ?? ($item['deskripsi'] ?? null);
                $liveDesc = (is_string($rawLiveDesc) && trim($rawLiveDesc) !== '') ? trim($rawLiveDesc) : null;

                if (self::syncLiveApiProduct($product, $liveStock, $liveApiPrice, true, $settings, $liveDesc)) {
                    $adjustedCount++;
                }
            }
        } catch (\Throwable $e) {}

        return $adjustedCount;
    }

    /**
     * Perbaiki teks deskripsi/catatan dari mojibake UTF-8 ganda (CP1252/Latin-1) & entitas HTML.
     */
    public static function cleanText(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        $text = str_replace(['\r\n', '\n', "\r\n", "\r"], "\n", $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $cp1252ToLatin1 = [
            "\xE2\x82\xAC" => "\xC2\x80",
            "\xE2\x80\x9A" => "\xC2\x82",
            "\xC6\x92"     => "\xC2\x83",
            "\xE2\x80\x9E" => "\xC2\x84",
            "\xE2\x80\xA6" => "\xC2\x85",
            "\xE2\x80\xA0" => "\xC2\x86",
            "\xE2\x80\xA1" => "\xC2\x87",
            "\xCB\x86"     => "\xC2\x88",
            "\xE2\x80\xB0" => "\xC2\x89",
            "\xC5\xA0"     => "\xC2\x8A",
            "\xE2\x80\xB9" => "\xC2\x8B",
            "\xC5\x92"     => "\xC2\x8C",
            "\xC5\xBD"     => "\xC2\x8E",
            "\xE2\x80\x98" => "\xC2\x91",
            "\xE2\x80\x99" => "\xC2\x92",
            "\xE2\x80\x9C" => "\xC2\x93",
            "\xE2\x80\x9D" => "\xC2\x94",
            "\xE2\x80\xA2" => "\xC2\x95",
            "\xE2\x80\x93" => "\xC2\x96",
            "\xE2\x80\x94" => "\xC2\x97",
            "\xCB\x9C"     => "\xC2\x98",
            "\xE2\x84\xA2" => "\xC2\x99",
            "\xC5\xA1"     => "\xC2\x9A",
            "\xE2\x80\xBA" => "\xC2\x9B",
            "\xC5\x93"     => "\xC2\x9C",
            "\xC5\xBE"     => "\xC2\x9E",
            "\xC5\xB8"     => "\xC2\x9F",
        ];

        $latin1Prepared = strtr($text, $cp1252ToLatin1);

        // Deteksi pola mojibake UTF-8 yang ter-encode sebagai Windows-1252 / ISO-8859-1 (contoh: âž¡ï¸, â€¢, ðŸ, Ã—)
        if (preg_match('/(?:Ã[\x{0080}-\x{00BF}]|â[\x{0080}-\x{00BF}]|ð[\x{0080}-\x{00BF}]|ï[\x{0080}-\x{00BF}]|Â[\x{0080}-\x{00BF}])/u', $latin1Prepared)) {
            $decoded = @mb_convert_encoding($latin1Prepared, 'ISO-8859-1', 'UTF-8');
            if (is_string($decoded) && $decoded !== '' && mb_check_encoding($decoded, 'UTF-8')) {
                $text = $decoded;
            }
        }

        // Normalisasi karakter fancy mathematical alphanumeric (𝐔𝐧𝐢𝐜𝐨𝐝𝐞 𝐛𝐨𝐥𝐝/𝐢𝐭𝐚𝐥𝐢𝐜) menjadi huruf standar yang mudah dibaca
        if (class_exists(\Normalizer::class) && preg_match('/[\x{1D400}-\x{1D7FF}]/u', $text)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_KD);
            if (is_string($normalized) && $normalized !== '') {
                $text = $normalized;
            }
        }

        // Ganti simbol langka yang tidak ter-render di font Windows/HP (contoh: 𖥻, ⌯) menjadi bullet/ikon rapi
        $text = str_replace(["\xF0\x96\xA5\xBB", '⌯'], ['📌', '•'], $text);

        return $text;
    }

    public function getDescriptionAttribute($value): ?string
    {
        return self::cleanText($value);
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = self::cleanText($value);
    }

    // Automatic local products can only sell account rows that are still unsold.
    // The legacy numeric stock column is not proof that deliverable stock exists.
    public function getStockAttribute($value): int
    {
        if ($this->type !== 'local' || $this->process_type === 'manual') {
            return max(0, (int) $value);
        }

        return $this->availableAccountStock();
    }

    public function availableAccountStock(): int
    {
        if (array_key_exists('available_accounts_count', $this->attributes)) {
            return (int) $this->attributes['available_accounts_count'];
        }
        if ($this->relationLoaded('accounts')) {
            return $this->accounts->where('is_sold', false)->count();
        }
        return $this->exists ? $this->accounts()->where('is_sold', false)->count() : 0;
    }

    public function scopeWithAvailableAccountStock($query)
    {
        return $query->withCount(['accounts as available_accounts_count' => fn ($accounts) => $accounts->where('is_sold', false)]);
    }

    public function accounts() {
        return $this->hasMany(LocalAccount::class);
    }
}
