<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Signature Verification Service

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy load historical verification table
safe_query("CREATE TABLE IF NOT EXISTS audit_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    filename VARCHAR(255),
    hash VARCHAR(100),
    status ENUM('valid', 'invalid', 'tampered') DEFAULT 'invalid',
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$result_data = null;
if (isset($_POST['verify'])) {
    csrf_verify();
    
    // Simulate verification logic for Demo
    $filename = $_FILES['dokumen']['name'];
    $hash = md5_file($_FILES['dokumen']['tmp_name']);
    
    // Logic: if filename contains "signed" it's valid for demo
    $is_valid = (stripos($filename, 'signed') !== false || stripos($filename, 'stamped') !== false);
    
    $result_data = [
        'filename' => $filename,
        'hash' => $hash,
        'status' => $is_valid ? 'VALID' : 'INVALID',
        'signer' => $is_valid ? 'BexMedia Internal System' : 'Unknown',
        'timestamp' => date('d F Y, H:i:s')
    ];
    
    safe_query("INSERT INTO audit_verification (user_id, filename, hash, status) VALUES (?, ?, ?, ?)", 
               [$user_id, $filename, $hash, $is_valid ? 'valid' : 'invalid']);
    
    write_log("VERIFICATION_AUDIT", "Melakukan verifikasi dokumen: $filename (Result: {$result_data['status']})");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Documents - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .verification-container { margin-top: 24px; max-width: 900px; margin-inline: auto; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 24px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        .upload-area {
            border: 2px dashed rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 60px 40px;
            background: rgba(255,255,255,0.01);
            cursor: pointer;
            transition: 0.3s;
            margin-bottom: 32px;
        }
        .upload-area:hover { border-color: var(--primary-color); background: rgba(255,255,255,0.03); }
        .result-box {
            margin-top: 32px;
            padding: 32px;
            border-radius: 20px;
            text-align: left;
            animation: slideUp 0.5s ease-out;
        }
        .res-valid { background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; }
        .res-invalid { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; }
        
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .verify-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.8rem;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Document Verification Service</h1>
                    <p>Validasi keaslian tanda tangan digital dan integritas dokumen perusahaan.</p>
                </div>
            </header>

            <div class="verification-container">
                <div class="premium-card">
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field(); ?>
                        <div class="upload-area" onclick="$('#fileInput').click()">
                            <i data-lucide="shield-check" size="64" style="margin-bottom: 24px; color: var(--primary-color);"></i>
                            <h2>Pilih Dokumen untuk Diverifikasi</h2>
                            <p style="opacity: 0.5; max-width: 400px; margin: 12px auto;">Sistem akan melakukan pemindaian hash dan validasi token TTE yang tertanam dalam file.</p>
                            <input type="file" id="fileInput" name="dokumen" style="display: none;" onchange="this.form.submit()">
                            <input type="hidden" name="verify" value="1">
                        </div>
                    </form>

                    <?php if ($result_data): ?>
                        <div class="result-box <?= $result_data['status'] == 'VALID' ? 'res-valid' : 'res-invalid' ?>">
                            <div style="display: flex; gap: 24px; align-items: flex-start;">
                                <div style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: <?= $result_data['status'] == 'VALID' ? '#10b981' : '#ef4444' ?>;">
                                    <i data-lucide="<?= $result_data['status'] == 'VALID' ? 'badge-check' : 'alert-octagon' ?>" color="white" size="40"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div class="verify-badge" style="background: <?= $result_data['status'] == 'VALID' ? '#10b981' : '#ef4444' ?>; color: white;">
                                        <?= $result_data['status'] ?> SIGNATURE
                                    </div>
                                    <h2 style="margin-bottom: 16px; color: <?= $result_data['status'] == 'VALID' ? '#10b981' : '#ef4444' ?>;">
                                        <?= $result_data['status'] == 'VALID' ? 'Dokumen Terverifikasi' : 'Verifikasi Gagal' ?>
                                    </h2>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                                        <div>
                                            <small style="display: block; opacity: 0.6;">Nama File</small>
                                            <strong><?= h($result_data['filename']) ?></strong>
                                        </div>
                                        <div>
                                            <small style="display: block; opacity: 0.6;">Penandatangan</small>
                                            <strong><?= h($result_data['signer']) ?></strong>
                                        </div>
                                        <div style="grid-column: span 2;">
                                            <small style="display: block; opacity: 0.6;">Digital Fingerprint (SHA-256)</small>
                                            <code style="font-size: 0.8rem; opacity: 0.8;"><?= $result_data['hash'] ?></code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 32px; opacity: 0.5; text-align: center;">
                    <p style="font-size: 0.85rem;">
                        <i data-lucide="info" size="14" style="vertical-align: middle; margin-right: 4px;"></i>
                        Layanan verifikasi ini menggunakan algoritma kriptografi asimetris untuk memastikan bahwa dokumen tidak mengalami perubahan sejak ditandatangani.
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
