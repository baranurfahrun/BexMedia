<?php
// Script untuk membuat Official Logo BexMedia (Icon + Text) menjadi satu PNG
$icon_source = 'images/bm.png';
$font_path = 'C:\Windows\Fonts\arialbd.ttf'; // Font standar Windows Bold
$target_file = 'images/bexmedia_official_logo.png';

if (!file_exists($icon_source)) {
    die("File $icon_source tidak ditemukan!\n");
}

// Load Icon
$icon = imagecreatefrompng($icon_source);
imagealphablending($icon, false);
imagesavealpha($icon, true);

// Colorize Icon to #3B82F6 (R:59, G:130, B:246)
imagefilter($icon, IMG_FILTER_COLORIZE, 59, 130, 246);

$icon_w = imagesx($icon);
$icon_h = imagesy($icon);

// Tentukan ukuran canvas baru (Icon + Text)
// Estimasi lebar: Icon width + estimasi lebar teks "BexMedia" - overlap
$canvas_w = 400; 
$canvas_h = 100;

$canvas = imagecreatetruecolor($canvas_w, $canvas_h);
imagealphablending($canvas, false);
$transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
imagefill($canvas, 0, 0, $transparent);
imagesavealpha($canvas, true);
imagealphablending($canvas, true);

// Warna Biru (#3B82F6)
$blue = imagecolorallocate($canvas, 59, 130, 246);

// Paste Icon
$target_icon_h = 60; // Tinggi logo di canvas
$target_icon_w = ($icon_w / $icon_h) * $target_icon_h;
imagecopyresampled($canvas, $icon, 10, ($canvas_h - $target_icon_h)/2, 0, 0, $target_icon_w, $target_icon_h, $icon_w, $icon_h);

// Draw Text "BexMedia"
$overlap = 15; // Jarak overlap antar icon dan teks
$text_x = 10 + $target_icon_w - $overlap;
$text_y = ($canvas_h / 2) + 12; // Posisi baseline

if (file_exists($font_path)) {
    imagettftext($canvas, 32, 0, $text_x, $text_y, $blue, $font_path, "BexMedia");
} else {
    // Fallback jika font tidak ada (biasanya di server linux)
    imagestring($canvas, 5, $text_x, $text_y - 10, "BexMedia", $blue);
}

// Save result
if (imagepng($canvas, $target_file)) {
    echo "Sukses! Logo lengkap disimpan di: $target_file\n";
} else {
    echo "Gagal menyimpan logo!\n";
}

imagedestroy($icon);
imagedestroy($canvas);
?>
