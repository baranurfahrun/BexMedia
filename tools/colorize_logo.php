<?php
// Script untuk mengubah warna logo bm.png (berbasis perak/grayscale) menjadi Royal Blue (#3B82F6)
$source = 'images/bm.png';
$target = 'images/bm_blue.png';

if (!file_exists($source)) {
    die("File $source tidak ditemukan!\n");
}

$img = imagecreatefrompng($source);
if (!$img) {
    die("Gagal me-load gambar!\n");
}

// Preserve transparency
imagealphablending($img, false);
imagesavealpha($img, true);

// Colorize to Royal Blue (#3B82F6 -> R:59, G:130, B:246)
// Kita gunakan offset karena IMG_FILTER_COLORIZE menambah nilai ke warna dasar
// Karena bm.png itu perak (abu-abu terang), kita bisa mewarnainya.
imagefilter($img, IMG_FILTER_COLORIZE, 59, 130, 246);

// Simpan hasil
if (imagepng($img, $target)) {
    echo "Berhasil! Gambar disimpan di $target\n";
} else {
    echo "Gagal menyimpan gambar!\n";
}

imagedestroy($img);
?>
