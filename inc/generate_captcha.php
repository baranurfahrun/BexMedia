<?php
session_start();

// Fungsi untuk menghasilkan string random (Tanpa angka 0, 1 dan huruf I, O yang membingungkan)
function generateRandomString($length = 6) {
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Generate captcha code
$captchaCode = generateRandomString();
$_SESSION['captcha'] = $captchaCode;

// Create image
$width = 200;
$height = 60;
$image = imagecreatetruecolor($width, $height);

// Warna - Menyesuaikan tema Glassmorphism Dashboard
$background = imagecolorallocate($image, 255, 255, 255); 
$text_color = imagecolorallocate($image, 11, 25, 46);    // Warna ice-1
$line_color = imagecolorallocate($image, 59, 130, 246);  // Warna ice-3 (garis)
$noise_color = imagecolorallocate($image, 148, 163, 184); // Noise dots

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $background);

// Add random noise lines
for ($i = 0; $i < 6; $i++) {
    imageline($image, 0, rand(0, $height), $width, rand(0, $height), $line_color);
}

// Add random noise dots
for ($i = 0; $i < 400; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// Add text
$x = 35;
for ($i = 0; $i < strlen($captchaCode); $i++) {
    $letter = substr($captchaCode, $i, 1);
    // Menggunakan imagestring (built-in) agar tidak butuh file font .ttf eksternal
    imagestring($image, 5, $x, rand(20, 30), $letter, $text_color);
    $x += 25;
}

// Output image
header('Content-Type: image/png');
header('Cache-Control: no-cache, must-revalidate');
imagepng($image);
imagedestroy($image);
?>
