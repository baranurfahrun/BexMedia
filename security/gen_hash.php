<?php
// === KONFIGURASI PRIBADI ANDA ===
$private_key = "KODE_RAHASIA_BARA";

// Data yang ingin dilindungi
$data = [
    'copyright' => "@2026 bara.n.fahrun-085117476001",
    'brand'     => "BexMedia",
    'logo'      => "images/bm.png"
];

echo "========================================\n";
echo "GENERATOR KUNCI KEAMANAN PROJEK RAHASIA\n";
echo "========================================\n\n";

foreach ($data as $key => $val) {
    $encoded = base64_encode($val);
    $hash    = md5($encoded . $private_key);
    
    echo "[$key] -> $val\n";
    echo '$sig_' . $key . '  = "' . $encoded . '";' . "\n";
    echo '$hash_' . $key . ' = "' . $hash . '";' . "\n\n";
}

echo "PRIVATE_KEY: $private_key\n";
echo "========================================\n";
?>
