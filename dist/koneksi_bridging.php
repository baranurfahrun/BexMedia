<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Bridging Service Monitor

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Table
safe_query("CREATE TABLE IF NOT EXISTS master_url (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_koneksi VARCHAR(100),
    base_url VARCHAR(255),
    status_last ENUM('online', 'offline') DEFAULT 'offline',
    last_check DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Mock data if empty
$check_empty = mysqli_fetch_assoc(safe_query("SELECT COUNT(*) as total FROM master_url"));
if($check_empty['total'] == 0) {
    safe_query("INSERT INTO master_url (nama_koneksi, base_url) VALUES 
        ('BPJS V-Claim 2.0', 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/'),
        ('SatuSehat Platform', 'https://api-satusehat.kemkes.go.id/'),
        ('Antrean Online (Mobile JKN)', 'https://apijkn.bpjs-kesehatan.go.id/antreanrs/'),
        ('E-RM Local Gateway', 'http://192.168.1.10:5000/api/'),
        ('SMS/WA Gateway Server', 'http://localhost:8000/status/')");
}

function checkLive($url) {
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? $url;
    $scheme = $parsed['scheme'] ?? 'http';
    $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);
    
    $fp = @fsockopen($host, $port, $errno, $errstr, 2);
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

$urls = safe_query("SELECT * FROM master_url ORDER BY nama_koneksi ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bridging Monitor - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .monitor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px; margin-top: 24px; }
        .service-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: 0.3s;
        }
        .service-card:hover { transform: translateY(-4px); background: rgba(255,255,255,0.05); }
        .service-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .status-dot { width: 12px; height: 12px; border-radius: 50%; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
        .dot-online { background: #10b981; box-shadow: 0 0 15px #10b981; }
        .dot-offline { background: #ef4444; box-shadow: 0 0 15px #ef4444; }
        
        .indicator-box { display: flex; align-items: center; gap: 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 4px 12px; border-radius: 50px; background: rgba(255,255,255,0.05); }
        
        .url-box { background: rgba(0,0,0,0.2); padding: 8px; border-radius: 8px; font-family: monospace; font-size: 0.75rem; color: rgba(255,255,255,0.4); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Integrated Bridging Monitor</h1>
                    <p>Pemantauan real-time stabilitas koneksi API dan integrasi sistem eksternal.</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-secondary" onclick="location.reload()">
                        <i data-lucide="refresh-cw"></i> Refresh Monitor
                    </button>
                </div>
            </header>

            <div class="monitor-grid">
                <?php while($row = mysqli_fetch_assoc($urls)): 
                    $isOnline = checkLive($row['base_url']);
                ?>
                <div class="service-card">
                    <div class="service-header">
                        <div>
                            <h3 style="margin:0; font-size:1.1rem;"><?= h($row['nama_koneksi']) ?></h3>
                            <div class="url-box" style="margin-top:8px;" title="<?= h($row['base_url']) ?>"><?= h($row['base_url']) ?></div>
                        </div>
                        <div class="status-dot <?= $isOnline ? 'dot-online' : 'dot-offline' ?>"></div>
                    </div>
                    
                    <div style="display:flex; justify-content: space-between; align-items:center;">
                        <div class="indicator-box" style="<?= $isOnline ? 'color:#10b981;' : 'color:#ef4444;' ?>">
                            <i data-lucide="<?= $isOnline ? 'zap' : 'zap-off' ?>" size="14"></i>
                            <?= $isOnline ? 'Active' : 'Connection Timeout' ?>
                        </div>
                        <small style="opacity:0.3; font-size:0.7rem;">Checked: <?= date('H:i:s') ?></small>
                    </div>
                </div>
                <?php endwhile; ?>

                <!-- ADD NEW CARD -->
                <div class="service-card" style="border: 2px dashed rgba(255,255,255,0.05); background:none; display:grid; place-items:center; cursor:pointer;" onclick="alert('Module Master URL : Segera Hadir')">
                    <div style="text-align:center; opacity:0.3;">
                        <i data-lucide="plus-circle" style="width:40px; height:40px; margin-bottom:12px;"></i>
                        <p>Tambah Endpoint Baru</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
