<?php
// Ganti dengan ID dan KEY Anda dari aplikasi Preflix
$id_user = 'UipA'; 
$key_user = 'b00128976c9ea5ade9e9';

$ch = curl_init("https://h2h.preflix.my.id/app/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Kita uji coba memanggil produk spesifik dari kategori 89 (Adobe)
// Sesuai dengan data yang muncul di screenshot Anda sebelumnya
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'koneksi'     => 'get_produk',
    'id_kategori' => '89', 
    'id_user'     => $id_user,
    'key_user'    => $key_user
]));

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "Terjadi Error cURL: " . $error;
    exit;
}

$data = json_decode($response, true);

echo "<h2>Hasil Respon Asli (Endpoint: get_produk - Kategori Adobe):</h2>";
echo "<pre style='background: #1e1e1e; color: #a6e22e; padding: 20px; border-radius: 10px; overflow-x: auto; font-family: monospace;'>";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "</pre>";
?>