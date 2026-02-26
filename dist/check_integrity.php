<?php
/**
 * BexMedia Integrity Guard & Security Core
 * Managed by Bara N. Fahrun (085117476001)
 */

$files_to_check = [
    'sidebar.php' => '0fc614ab0e85c5431568c1463f98d38f2e1535e3',
    'footer.php' => 'fef4d5f311cb36a6d1a1c820e9b70d83ef1141fa'
];

foreach ($files_to_check as $filename => $expected_hash) {
    $filepath = __DIR__ . '/' . $filename;
    $current_hash = @sha1_file($filepath);

    if (!$current_hash || $current_hash !== $expected_hash) {
        die('
        <div style="max-width:600px;margin:80px auto;font-family:\'Inter\', sans-serif;">
          <div style="border:2px solid #ef4444;border-radius:24px;padding:40px;background:#fef2f2;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);">
            <div style="background:#ef4444;width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
                <svg style="color:white;width:32px;height:32px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h2 style="color:#1e1b4b;margin-bottom:16px;font-weight:800;letter-spacing:-0.025em">Integritas Terganggu</h2>
            <p style="color:#ef4444;font-size:16px;line-height:1.6;margin-bottom:24px;font-weight:500;">
                🙏 Maaf, perubahan pada file <strong>' . $filename . '</strong> tidak diizinkan.<br>
                Sistem <strong>BexMedia Performance Guard</strong> mendeteksi modifikasi pada Sidebar atau Hak Cipta.
            </p>
            <div style="padding:20px;background:white;border-radius:16px;color:#64748b;font-size:14px;border:1px solid #fee2e2;line-height:1.7;">
              Demi menjaga <strong>Hak Cipta & Stabilitas Aplikasi</strong>, akses fitur ini ditutup sementara.<br><br>
              Silakan hubungi pemegang lisensi resmi:<br>
              <a href="https://wa.me/6285117476001" style="text-decoration:none; color:#1e1b4b;">
                <strong style="font-size:16px;">Bara N. Fahrun (085117476001)</strong>
              </a><br>
              untuk otorisasi pemulihan sistem.
            </div>
            <p style="margin-top:24px;color:#94a3b8;font-size:12px;">
              🚀 BexMedia High-Performance Architecture • Fingerprint: ' . ($current_hash ?: 'FILE_MISSING') . '
            </p>
          </div>
        </div>
        ');
    }
}
?>







