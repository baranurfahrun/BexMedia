<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: My Signed Documents

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Table
safe_query("CREATE TABLE IF NOT EXISTS tte_document_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    tte_token VARCHAR(255),
    document_name VARCHAR(255),
    document_hash VARCHAR(256),
    signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

$docs = safe_query("SELECT dl.*, tu.nama, tu.jabatan 
                    FROM tte_document_log dl
                    JOIN tte_user tu ON dl.tte_token = tu.token
                    WHERE dl.user_id = ? 
                    ORDER BY dl.signed_at DESC", [$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signed Documents - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .doc-vault { margin-top: 24px; background: rgba(255, 255, 255, 0.03); border-radius: 24px; padding: 32px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .vault-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        
        .hash-tag { font-family: monospace; font-size: 0.65rem; color: rgba(255,255,255,0.3); background: rgba(0,0,0,0.2); padding: 4px 8px; border-radius: 4px; display: inline-block; margin-top: 8px; }
        .action-row { display: flex; gap: 8px; }
        .icon-btn { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.6); border: 1px solid transparent; transition: 0.3s; cursor: pointer; }
        .icon-btn:hover { background: rgba(255,255,255,0.1); color: var(--primary-color); border-color: var(--primary-color); }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Signed Documents Archive</h1>
                    <p>Log aktivitas penandatanganan elektronik dan manajemen berkas terverifikasi.</p>
                </div>
            </header>

            <div class="doc-vault">
                <div class="vault-header">
                    <div style="display:flex; align-items:center; gap:16px;">
                        <i data-lucide="shield" style="width:32px; height:32px; color:#10b981;"></i>
                        <div>
                            <h3 style="margin:0;">Personal Vault</h3>
                            <span style="font-size:0.75rem; opacity:0.4;">Total <?= mysqli_num_rows($docs) ?> dokumen berhasil ditandatangani.</span>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="window.location='bubuhkan_tte.php'"><i data-lucide="plus"></i> Sign New Document</button>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Document Detail</th>
                            <th>Verification Hash</th>
                            <th>Date Signed</th>
                            <th>Signer</th>
                            <th style="width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($docs) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($docs)): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--primary-color);"><?= h($row['document_name']) ?></div>
                                    <small style="opacity:0.4;">Institutional Document</small>
                                </td>
                                <td>
                                    <div class="hash-tag"><?= substr($row['document_hash'], 0, 32) ?>...</div>
                                </td>
                                <td>
                                    <div style="font-size:0.85rem;"><?= date('d F Y', strtotime($row['signed_at'])) ?></div>
                                    <small style="opacity:0.4;"><?= date('H:i', strtotime($row['signed_at'])) ?> WIB</small>
                                </td>
                                <td>
                                    <div style="font-size:0.85rem; font-weight:600;"><?= h($row['nama']) ?></div>
                                    <small style="opacity:0.4;"><?= h($row['jabatan']) ?></small>
                                </td>
                                <td>
                                    <div class="action-row">
                                        <button class="icon-btn" title="View Document"><i data-lucide="eye" size="16"></i></button>
                                        <button class="icon-btn" title="Download Signed Version"><i data-lucide="download" size="16"></i></button>
                                        <button class="icon-btn" title="Share via Email" style="color:#3b82f6;"><i data-lucide="mail" size="16"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:80px 0; opacity:0.15;">
                                    <i data-lucide="folder-search" style="width:60px; height:60px; margin-bottom:16px;"></i>
                                    <h3>Belum Ada Dokumen TTE</h3>
                                    <p>Anda belum menandatangani berkas apapun melalui sistem ini.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
