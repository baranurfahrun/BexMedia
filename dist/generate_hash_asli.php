<?php
/**
 * BexMedia Integrity Console (V.2026.02)
 * High-Security Hash Generator for BexMedia Digital Assets
 * Managed by Bara N. Fahrun (085117476001)
 */
include 'koneksi.php';

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ===============================
// KONFIGURASI KEAMANAN BEXMEDIA
// ===============================
$master_password = 'BEXMEDIA2026'; // Silakan ganti sesuai kebutuhan Anda

// Ambil pengaturan email dari database BexMedia
$mail_setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM mail_settings LIMIT 1"));
if (!$mail_setting) {
    die("<h2 style='color:#ef4444;text-align:center;margin-top:80px;font-family:Inter,sans-serif;'>❌ Pengaturan email BexMedia tidak ditemukan.</h2>");
}

// ===============================
// AUTENTIKASI AKSES KONSOL
// ===============================
if (!isset($_SESSION['authorized_bex'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === $master_password) {
            $_SESSION['authorized_bex'] = true;
            header("Location: generate_hash_asli.php");
            exit;
        } else {
            $error_login = "Password keamanan ditolak.";
        }
    } else {
        echo '
        <!DOCTYPE html>
        <html lang="id">
        <head>
    <link rel="icon" href="../images/logo_final.png">
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>BexMedia | Integrity Auth</title>
            
            <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
            <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
            <style>
                body { 
                    background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 50%, #BAE6FD 100%);
                    background-attachment: fixed;
                    font-family: \'Inter\', sans-serif; 
                    height: 100vh; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    margin: 0;
                }
                .auth-card { 
                    background: rgba(255, 255, 255, 0.7); 
                    backdrop-filter: blur(20px);
                    -webkit-backdrop-filter: blur(20px);
                    border: 1px solid rgba(255, 255, 255, 0.5);
                    border-radius: 32px; 
                    padding: 50px; 
                    box-shadow: 0 20px 60px rgba(0, 50, 150, 0.08); 
                    width: 100%; 
                    max-width: 420px; 
                    text-align: center; 
                }
                .auth-icon { 
                    background: linear-gradient(135deg, #3B82F6 0%, #1E40AF 100%);
                    color: white; 
                    width: 70px; 
                    height: 70px; 
                    border-radius: 20px; 
                    display: inline-flex; 
                    align-items: center; 
                    justify-content: center; 
                    font-size: 28px; 
                    margin-bottom: 24px; 
                    box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
                }
                .auth-card h4 {
                    font-family: \'Outfit\', sans-serif;
                    font-weight: 800;
                    color: #0F172A;
                    letter-spacing: -0.02em;
                }
                .btn-auth { 
                    background: #3B82F6; 
                    border: none; 
                    border-radius: 16px; 
                    padding: 14px; 
                    font-weight: 700; 
                    font-family: \'Outfit\', sans-serif;
                    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
                    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
                }
                .btn-auth:hover { 
                    background: #2563EB; 
                    transform: translateY(-3px); 
                    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
                }
                .form-control {
                    border-radius: 14px; 
                    padding: 14px; 
                    background: rgba(241, 245, 249, 0.8) !important; 
                    border: 1px solid transparent;
                    transition: all 0.3s ease;
                }
                .form-control:focus {
                    background: #fff !important;
                    border-color: #3B82F6;
                    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
                }
            </style>
        </head>
        <body>
            <div class="auth-card">
                <div class="auth-icon"><i class="fas fa-shield-alt"></i></div>
                <h4>Integrity Console</h4>
                <p class="text-muted mb-4" style="font-size: 15px;">Administrator verification required for BexMedia Security systems.</p>
                
                <form method="POST">
                    <div class="form-group mb-4">
                        <input type="password" name="password" class="form-control text-center" placeholder="Security Master Password" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-auth">Buka Konsol Keamanan</button>
                    ' . (isset($error_login) ? '<p class="text-danger mt-3 font-weight-bold">' . $error_login . '</p>' : '') . '
                </form>
                
                <p class="mt-5 text-muted" style="font-size: 12px; font-weight: 500;">&copy; 2026 BexMedia • Premium Security Guard</p>
            </div>
        </body>
        </html>';
        exit;
    }
}

// LOGOUT KONSOL
if (isset($_GET['logout'])) {
    unset($_SESSION['authorized_bex']);
    header("Location: generate_hash_asli.php");
    exit;
}

$status_msg = "";
$error_msg = "";

// ===============================
// LOGIK GENERATOR HASH & SYNC
// ===============================
if (isset($_POST['generate_hash'])) {
    $files_to_hash = ['sidebar.php', 'footer.php'];
    $success_count = 0;
    $integrity_file = 'check_integrity.php';
    
    if (!file_exists($integrity_file)) {
        $error_msg = "File check_integrity.php tidak ditemukan!";
    } else {
        $content = file_get_contents($integrity_file);
        $new_hashes = [];

        foreach ($files_to_hash as $file) {
            if (file_exists($file)) {
                $hash = sha1_file($file);
                $new_hashes[$file] = $hash;
                
                // Regex untuk update array di check_integrity.php
                $pattern = "/'$file'\s*=>\s*'[a-f0-9]{40}'/";
                $replacement = "'$file' => '$hash'";
                $content = preg_replace($pattern, $replacement, $content);
                $success_count++;
            }
        }

        // Simpan perubahan ke check_integrity.php
        if (file_put_contents($integrity_file, $content)) {
            $status_msg = "Berhasil mensinkronkan $success_count file keamanan.";
            
            // CATAT KE AUDIT LOG
            $user_id = $_SESSION['nama'] ?? 'Administrator';
            $ip = $_SERVER['REMOTE_ADDR'];
            $desc = "Sinkronisasi Integrity Console: " . implode(", ", array_keys($new_hashes));
            mysqli_query($conn, "INSERT INTO web_dokter_audit_log (user_id, ip_address, action, description) 
                                VALUES ('$user_id', '$ip', 'SECURITY_SYNC', '$desc')");

            // ===============================
            // KIRIM LAPORAN KE EMAIL (PHPMailer)
            // ===============================
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $mail_setting['mail_host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $mail_setting['mail_username'];
                $mail->Password   = $mail_setting['mail_password'];
                $mail->SMTPSecure = 'tls';
                $mail->Port       = $mail_setting['mail_port'];

                $mail->setFrom($mail_setting['mail_from_email'], $mail_setting['mail_from_name']);
                $mail->addAddress($mail_setting['mail_from_email']);

                $mail->isHTML(true);
                $mail->Subject = '🛡️ BexMedia Security Audit: Hash Synchronized';
                
                $hash_list = "";
                foreach($new_hashes as $f => $h) { $hash_list .= "<li><b>$f</b>: <code style='background:#f1f5f9;padding:2px 5px;'>$h</code></li>"; }

                $mail->Body    = "
                <div style='font-family:sans-serif;max-width:600px;border:1px solid #e2e8f0;border-radius:12px;padding:30px;'>
                    <h2 style='color:#0f172a;'>BexMedia Integrity Report</h2>
                    <p>Laporan otomatis: Sistem baru saja melakukan sinkronisasi kode integritas (hash) pada aset digital berikut:</p>
                    <ul style='list-style:none;padding:0;'>$hash_list</ul>
                    <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0;'>
                    <p style='font-size:12px;color:#64748b;'>Waktu: ".date('Y-m-d H:i:s')."<br>IP: $ip<br>User: $user_id</p>
                </div>";

                $mail->send();
                $status_msg .= " Laporan audit telah dikirim ke email " . $mail_setting['mail_from_email'] . ".";
            } catch (Exception $e) {
                $status_msg .= " Sinkronisasi sukses, namun email gagal dikirim (Cek error.log).";
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
            }
        } else {
            $error_msg = "Gagal menulis ke file check_integrity.php. Periksa izin file (CHMOD).";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../images/logo_final.png">
    <meta charset="UTF-8">
    <title>BexMedia | Integrity Console</title>
    
    <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 50%, #BAE6FD 100%);
            background-attachment: fixed;
            font-family: \'Inter\', sans-serif; 
            padding-top: 50px; 
            min-height: 100vh;
        }
        .console-card { 
            background: rgba(255, 255, 255, 0.7); 
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 32px; 
            box-shadow: 0 20px 60px rgba(0, 50, 150, 0.05); 
            border: 1px solid rgba(255, 255, 255, 0.5); 
            overflow: hidden; 
        }
        .console-header { 
            background: #0f172a; 
            color: white; 
            padding: 25px 35px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
        }
        .console-header h5 {
            font-family: \'Outfit\', sans-serif;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .btn-sync { 
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            border: none; 
            border-radius: 18px; 
            padding: 16px 35px; 
            font-weight: 700; 
            font-family: \'Outfit\', sans-serif;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3); 
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        }
        .btn-sync:hover { 
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            transform: translateY(-4px); 
            box-shadow: 0 15px 35px rgba(59, 130, 246, 0.4); 
            color: white;
        }
        code { background: rgba(59, 130, 246, 0.1); color: #2563EB; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 0.9em; }
        .file-item {
            background: white;
            padding: 20px;
            border-radius: 18px;
            margin-bottom: 12px;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: 0.3s;
        }
        .file-item:hover { transform: scale(1.02); box-shadow: 0 10px 20px rgba(0,0,0,0.03); }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card console-card">
                <div class="console-header">
                    <h5 class="mb-0"><i class="fas fa-shield-check mr-2"></i> BexMedia Performance Guard</h5>
                    <a href="?logout=1" class="btn btn-sm btn-outline-light border-0 px-3" style="border-radius: 8px;">
                        <i class="fas fa-power-off mr-1"></i> Logout
                    </a>
                </div>
                <div class="card-body p-5">
                    
                    <?php if ($status_msg): ?>
                        <div class="alert alert-success border-0 rounded-xl py-3 mb-4 shadow-sm" style="background: #ecfdf5; color: #065f46;">
                            <i class="fas fa-check-circle mr-2"></i> <?= $status_msg ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger border-0 rounded-xl py-3 mb-4 shadow-sm">
                            <i class="fas fa-exclamation-triangle mr-2"></i> <?= $error_msg ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-5">
                        <h3 class="font-weight-bold color-dark">Security Synchronization</h3>
                        <p class="text-muted">Generate new security hashes for core assets to maintain high-fidelity integrity within the BexMedia ecosystem.</p>
                    </div>
                    
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="file-item">
                                <div>
                                    <span class="d-block font-weight-bold">sidebar.php</span>
                                    <small class="text-muted">Core Navigation Matrix</small>
                                </div>
                                <span class="badge badge-soft-primary px-3 py-2" style="background:#e0f2fe; color:#0369a1; border-radius:8px;">Protected</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="file-item">
                                <div>
                                    <span class="d-block font-weight-bold">footer.php</span>
                                    <small class="text-muted">Legal & Copyright Entity</small>
                                </div>
                                <span class="badge badge-soft-primary px-3 py-2" style="background:#e0f2fe; color:#0369a1; border-radius:8px;">Protected</span>
                            </div>
                        </div>
                    </div>

                    <form method="POST" class="text-center">
                        <button type="submit" name="generate_hash" class="btn btn-primary btn-sync">
                            <i class="fas fa-sync-alt mr-2"></i> Sync Security Manifest
                        </button>
                    </form>

                </div>
                <div class="card-footer bg-white border-0 py-4 text-center">
                    <p class="mb-0 text-muted small">System Status: <span class="text-success font-weight-bold"><i class="fas fa-circle x-small"></i> Online & Secure</span> | Sync Engine V.2.1</p>
                </div>
            </div>
            
            <p class="text-center mt-5 text-muted" style="font-size: 13px;">Enterprise Security Managed by <a href="https://wa.me/6285117476001" style="color:#64748b; font-weight:bold; text-decoration:none;">Bara N. Fahrun</a></p>
        </div>
    </div>
</div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>
</body>
</html>


