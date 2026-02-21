<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Digital Signature System

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS tte_user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    nama VARCHAR(255),
    nik VARCHAR(50),
    jabatan VARCHAR(255),
    token VARCHAR(255),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Cek TTE user
$dataTte = safe_query("SELECT * FROM tte_user WHERE user_id=? AND status='aktif' LIMIT 1", [$user_id]);
$tte = mysqli_fetch_assoc($dataTte);

if (!$tte) {
    // Info user saat ini
    $u = mysqli_fetch_assoc(safe_query("SELECT * FROM users WHERE id=?", [$user_id]));
    $token = md5($user_id . time() . "BEXMEDIA_SALT");
    safe_query("INSERT INTO tte_user (user_id, nama, nik, jabatan, token) VALUES (?, ?, ?, ?, ?)", 
               [$user_id, $u['nama_lengkap'], $u['nik'], $u['jabatan'], $token]);
    $tte = mysqli_fetch_assoc(safe_query("SELECT * FROM tte_user WHERE user_id=? AND status='aktif' LIMIT 1", [$user_id]));
}

$success_file = null;
if (isset($_POST['bubuhkan_tte'])) {
    csrf_verify();
    
    // Simulating processing
    $success_file = "Signed_" . time() . ".pdf";
    write_log("TTE_APPLIED", "Membubuhkan TTE pada dokumen halaman {$_POST['target_page']} di posisi X:{$_POST['position_x']}%, Y:{$_POST['position_y']}%");
    $_SESSION['flash_success'] = "Dokumen berhasil ditandatangani secara digital.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signature - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        .tte-container { margin-top: 24px; max-width: 1200px; }
        .grid-layout { display: grid; grid-template-columns: 1fr 350px; gap: 32px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 24px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .preview-box {
            background: rgba(0,0,0,0.2);
            border-radius: 16px;
            position: relative;
            min-height: 700px;
            border: 1px dashed rgba(255,255,255,0.1);
            overflow: hidden;
            display: flex;
            justify-content: center;
        }
        .canvas-wrapper { position: relative; display: inline-block; background: white; margin: 40px; box-shadow: 0 30px 60px rgba(0,0,0,0.5); }
        .sign-marker {
            position: absolute;
            width: 80px;
            height: 80px;
            border: 2px solid var(--primary-color);
            background: rgba(255, 255, 255, 0.9);
            cursor: move;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            border-radius: 8px;
        }
        .sign-marker img { width: 100%; border-radius: 4px; }
        .pos-badge {
            position: absolute;
            top: -25px;
            background: var(--primary-color);
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }
        .upload-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(15,15,15,0.8);
            backdrop-filter: blur(10px);
            z-index: 200;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Digital Signature (TTE)</h1>
                    <p>Tanda tangani dokumen resmi secara digital dengan standar keamanan tinggi.</p>
                </div>
            </header>

            <div class="tte-container">
                <div class="grid-layout">
                    <!-- PREVIEW AREA -->
                    <div class="premium-card" style="padding: 0;">
                        <div class="preview-box" id="dropZone">
                            <div id="uploadOverlay" class="upload-overlay">
                                <i data-lucide="file-up" size="64" style="margin-bottom: 24px; opacity: 0.3;"></i>
                                <button class="btn btn-primary" onclick="$('#fileInput').click()">Select PDF Document</button>
                                <p style="margin-top: 16px; opacity: 0.5; font-size: 0.9rem;">Drag & drop PDF here to start signing</p>
                                <input type="file" id="fileInput" style="display: none;" accept=".pdf" onchange="loadPDF(this.files[0])">
                            </div>

                            <div class="canvas-wrapper" id="canvasWrapper" style="display: none;">
                                <canvas id="pdfCanvas"></canvas>
                                <div id="signMarker" class="sign-marker">
                                    <div class="pos-badge" id="posBadge">X: 75% Y: 80%</div>
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=TTE-VERIFY-<?= $tte['token'] ?>" alt="QR">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONTROLS -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        <div class="premium-card">
                            <h3 style="margin-bottom: 20px;">Identity</h3>
                            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
                                <div style="width: 50px; height: 50px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i data-lucide="user" color="white"></i>
                                </div>
                                <div>
                                    <strong style="display: block; font-size: 1.1rem;"><?= h($tte['nama']) ?></strong>
                                    <small style="opacity: 0.6;"><?= h($tte['jabatan']) ?></small>
                                </div>
                            </div>
                            <div style="background: rgba(255,255,255,0.05); padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                                <small style="display: block; opacity: 0.5; margin-bottom: 4px;">Security Token</small>
                                <code style="font-size: 0.75rem; word-break: break-all;"><?= $tte['token'] ?></code>
                            </div>
                        </div>

                        <div class="premium-card">
                            <h3 style="margin-bottom: 20px;">Sign Settings</h3>
                            <form method="POST" id="submitForm">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="position_x" id="posX" value="75">
                                <input type="hidden" name="position_y" id="posY" value="80">
                                <input type="hidden" name="target_page" id="targetPage" value="1">

                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; margin-bottom: 8px; font-size: 0.85rem; opacity: 0.6;">Apply to Page</label>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <button type="button" class="btn btn-outline btn-sm" onclick="changePage(-1)"><i data-lucide="chevron-left"></i></button>
                                        <span id="pageDisplay" style="flex: 1; text-align: center; font-weight: 700;">1 / 1</span>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="changePage(1)"><i data-lucide="chevron-right"></i></button>
                                    </div>
                                </div>

                                <button type="submit" name="bubuhkan_tte" class="btn btn-primary" style="width: 100%; padding: 16px; margin-top: 12px;">
                                    Finalize Signature
                                </button>
                                <button type="button" class="btn btn-outline" style="width: 100%; margin-top: 12px;" onclick="location.reload()">Reset</button>
                            </form>
                        </div>

                        <?php if ($success_file): ?>
                            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; padding: 20px; border-radius: 16px; text-align: center;">
                                <i data-lucide="check-circle" color="#10b981" size="32"></i>
                                <p style="margin-top: 12px; font-size: 0.9rem;">Document Signed!</p>
                                <a href="#" class="btn btn-primary btn-sm" style="margin-top: 12px;">Download</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        // PDF Logic
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        let pdfDoc = null;
        let currentPage = 1;

        async function loadPDF(file) {
            if (!file) return;
            const reader = new FileReader();
            reader.onload = async function() {
                const typedarray = new Uint8Array(this.result);
                pdfDoc = await pdfjsLib.getDocument(typedarray).promise;
                $('#uploadOverlay').fadeOut();
                $('#canvasWrapper').show();
                renderPage(1);
            };
            reader.readAsArrayBuffer(file);
        }

        async function renderPage(num) {
            const page = await pdfDoc.getPage(num);
            const canvas = document.getElementById('pdfCanvas');
            const context = canvas.getContext('2d');
            const viewport = page.getViewport({ scale: 1.5 });
            
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            
            await page.render({ canvasContext: context, viewport: viewport }).promise;
            
            $('#pageDisplay').text(`${num} / ${pdfDoc.numPages}`);
            $('#targetPage').val(num);
        }

        function changePage(delta) {
            if (!pdfDoc) return;
            const newPage = currentPage + delta;
            if (newPage >= 1 && newPage <= pdfDoc.numPages) {
                currentPage = newPage;
                renderPage(currentPage);
            }
        }

        // Drag Logic
        const marker = document.getElementById('signMarker');
        const wrapper = document.getElementById('canvasWrapper');
        let isDragging = false;

        marker.addEventListener('mousedown', () => isDragging = true);
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            const rect = wrapper.getBoundingClientRect();
            let x = e.clientX - rect.left - 40;
            let y = e.clientY - rect.top - 40;
            
            x = Math.max(0, Math.min(x, rect.width - 80));
            y = Math.max(0, Math.min(y, rect.height - 80));
            
            const px = (x / rect.width * 100).toFixed(1);
            const py = (y / rect.height * 100).toFixed(1);
            
            marker.style.left = x + 'px';
            marker.style.top = y + 'px';
            
            $('#posX').val(px);
            $('#posY').val(py);
            $('#posBadge').text(`X: ${px}% Y: ${py}%`);
        });
        document.addEventListener('mouseup', () => isDragging = false);
    </script>
</body>
</html>
