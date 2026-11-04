<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Product extends Model {
    protected $fillable = ['name', 'description', 'price', 'reseller_price', 'type', 'process_type', 'api_product_id', 'api_price', 'image', 'stock', 'status', 'category', 'is_promo'];

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
