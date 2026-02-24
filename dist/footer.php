<?php
// footer.php - Centralized Marquee & Security Copyright
$private_key_f = "KODE_RAHASIA_BARA";
$sig_copyright_f  = "QDIwMjYgYmFyYS5uLmZhaHJ1bi0wODUxMTc0NzYwMDE=";
$hash_copyright_f = "3e07d2217d54524233697deb8b497061";
$copyright_text_f = (md5($sig_copyright_f . $private_key_f) === $hash_copyright_f) ? base64_decode($sig_copyright_f) : "Security Breach: Copyright modified!";

// Fetch Running Text Settings
$rt_text   = get_setting('running_text', 'Selamat Datang di Portal BexMedia - Executive Mission Control Dashboard');
$rt_speed  = get_setting('rt_speed', '10');
$rt_size   = get_setting('rt_font_size', '16');
$rt_family = get_setting('rt_font_family', "'Inter', sans-serif");
$rt_color  = get_setting('rt_color', '#1e3a8a');

// Calculate animation duration based on speed setting (inverted: high speed = low seconds)
// Base duration 100s for speed 10, approx linear mapping
$duration = 1000 / (max(1, intval($rt_speed))); 
?>

<style>
    .running-text {
        animation: scroll-text <?php echo $duration; ?>s linear infinite !important;
        font-size: <?php echo $rt_size; ?>px !important;
        font-family: <?php echo $rt_family; ?> !important;
        color: <?php echo $rt_color; ?> !important;
    }
</style>

<div class="running-text-container">
    <div class="marquee-content">
        <div class="running-text">
            <span><?php echo h($rt_text); ?> • </span>
            <span><?php echo h($rt_text); ?> • </span>
        </div>
    </div>
    <div class="fixed-copyright">
        <?php echo $copyright_text_f; ?>
    </div>
</div>

<script>
    // Initialize Lucide Icons across all pages
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>







