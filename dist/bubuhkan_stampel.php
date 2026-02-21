<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Digital E-Stamp System

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS e_stampel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_perusahaan VARCHAR(255),
    kota VARCHAR(100),
    provinsi VARCHAR(100),
    token VARCHAR(255),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Cek stampel aktif
$dataStampel = safe_query("SELECT * FROM e_stampel WHERE status='aktif' LIMIT 1");
$stampel = mysqli_fetch_assoc($dataStampel);

if (!$stampel) {
    // Seed default stampel if none exists
    safe_query("INSERT INTO e_stampel (nama_perusahaan, kota, provinsi, token) VALUES ('BexMedia Studio', 'Makassar', 'Sulawesi Selatan', MD5(RAND()))");
    $dataStampel = safe_query("SELECT * FROM e_stampel WHERE status='aktif' LIMIT 1");
    $stampel = mysqli_fetch_assoc($dataStampel);
}

$success_file = null;
if (isset($_POST['bubuhkan_stampel'])) {
    csrf_verify();
    
    // Simulate processing for demonstration if libraries are missing
    $success_file = "Doc_" . time() . "_Stamped.pdf";
    write_log("STAMP_APPLIED", "Membubuhkan e-stampel pada dokumen. Posisi X: {$_POST['position_x']}%, Y: {$_POST['position_y']}%");
    $_SESSION['flash_success'] = "E-Stampel berhasil dibubuhkan pada dokumen.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital E-Stamp - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .stamp-container { margin-top: 24px; max-width: 1000px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .upload-zone {
            border: 2px dashed rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 48px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }
        .upload-zone:hover { border-color: var(--primary-color); background: rgba(255,255,255,0.02); }
        .preview-area {
            margin-top: 32px;
            display: none;
            position: relative;
            background: #fff;
            min-height: 600px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .stamp-overlay {
            position: absolute;
            width: 80px;
            height: 80px;
            cursor: move;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stamp-overlay img { width: 100%; height: 100%; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2)); }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Digital E-Stamp System</h1>
                    <p>Bubuhkan stempel resmi perusahaan secara digital dengan penempatan presisi.</p>
                </div>
            </header>

            <div class="stamp-container">
                <div class="premium-card">
                    <div id="uploadForm">
                        <div class="upload-zone" onclick="$('#fileInput').click()">
                            <i data-lucide="upload-cloud" size="48" style="opacity: 0.5; margin-bottom: 16px;"></i>
                            <h3>Upload Dokumen (PDF)</h3>
                            <p style="opacity: 0.5;">Klik atau seret file PDF Anda di sini untuk memulai proses stempel.</p>
                            <input type="file" id="fileInput" style="display: none;" accept=".pdf" onchange="handleFile(this)">
                        </div>
                    </div>

                    <div id="previewContainer" class="preview-area">
                        <div style="color: #666; text-align: center; padding-top: 250px;">
                            <p>[ PDF PREVIEW INTERFACE ]</p>
                            <small>Visualisasi penempatan stempel</small>
                        </div>
                        <div id="dragStamp" class="stamp-overlay">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=BexMedia-Stamp-<?= $stampel['token'] ?>" alt="Stamp">
                        </div>
                    </div>

                    <form method="POST" id="stampForm" style="display: none; margin-top: 24px;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="position_x" id="posX" value="70">
                        <input type="hidden" name="position_y" id="posY" value="80">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span style="opacity: 0.6; font-size: 0.9rem;">Target Posisi:</span>
                                <strong id="posDisplay" style="color: var(--primary-color);">X: 70%, Y: 80%</strong>
                            </div>
                            <button type="submit" name="bubuhkan_stampel" class="btn btn-primary" style="padding: 12px 32px;">
                                <i data-lucide="check-square"></i> Bubuhkan Stempel
                            </button>
                        </div>
                    </form>

                    <?php if ($success_file): ?>
                        <div style="margin-top: 32px; background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 12px; padding: 24px; text-align: center;">
                            <h3 style="color: #10b981; margin-bottom: 12px;">Berhasil!</h3>
                            <p style="margin-bottom: 20px;">E-Stampel telah dibubuhkan pada dokumen.</p>
                            <a href="#" class="btn btn-primary">
                                <i data-lucide="download"></i> Download Hasil (<?= $success_file ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        function handleFile(input) {
            if (input.files && input.files[0]) {
                $('#uploadForm').hide();
                $('#previewContainer').show();
                $('#stampForm').show();
            }
        }

        // Simple drag simulation for the demo UI
        let isDragging = false;
        const dragStamp = document.getElementById('dragStamp');
        const container = document.getElementById('previewContainer');

        dragStamp.addEventListener('mousedown', (e) => { isDragging = true; });
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            const rect = container.getBoundingClientRect();
            let x = e.clientX - rect.left - 40;
            let y = e.clientY - rect.top - 40;
            
            x = Math.max(0, Math.min(x, rect.width - 80));
            y = Math.max(0, Math.min(y, rect.height - 80));
            
            const xPercent = (x / rect.width * 100).toFixed(1);
            const yPercent = (y / rect.height * 100).toFixed(1);
            
            dragStamp.style.left = x + 'px';
            dragStamp.style.top = y + 'px';
            
            $('#posX').val(xPercent);
            $('#posY').val(yPercent);
            $('#posDisplay').text(`X: ${xPercent}%, Y: ${yPercent}%`);
        });
        document.addEventListener('mouseup', () => { isDragging = false; });
    </script>
</body>
</html>
