<?php
// footer.php - Centralized Marquee & Security Copyright
// Shared Security Logic for Copyright
$private_key_f = "KODE_RAHASIA_BARA";
$sig_copyright_f  = "QDIwMjYgYmFyYS5uLmZhaHJ1bi0wODUxMTc0NzYwMDE=";
$hash_copyright_f = "3e07d2217d54524233697deb8b497061";
$copyright_text_f = (md5($sig_copyright_f . $private_key_f) === $hash_copyright_f) ? base64_decode($sig_copyright_f) : "Security Breach: Copyright modified!";
?>

<div class="running-text-container">
    <div class="marquee-content">
        <div class="running-text">
            <span>BexMedia News: Application synced with FixPoint Features • Integrated TTE System ready for testing • Monitoring systems active and performing at peak efficiency • Welcome to BexMedia Premium Studio Dashboard • Stay updated with real-time analytics! • </span>
            <span>BexMedia News: Application synced with FixPoint Features • Integrated TTE System ready for testing • Monitoring systems active and performing at peak efficiency • Welcome to BexMedia Premium Studio Dashboard • Stay updated with real-time analytics! • </span>
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







