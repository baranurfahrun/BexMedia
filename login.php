<?php
// === KODE VERIFIKASI KEAMANAN PROJEK ===
$private_key = "KODE_RAHASIA_BARA";

// Verifikasi Nama Brand (Key for Security Check)
$sig_brand  = "QmV4TWVkaWE=";
$hash_brand = "1d45b0cc7a28442c082bd43bd312ac88";
if (md5($sig_brand . $private_key) !== $hash_brand) die("Security Breach!");

// Verifikasi Path & Konten Logo (Double Layer Security)
$sig_logo      = "aW1hZ2VzL2xvZ29fZmluYWwucG5n";
$hash_path     = "55dc42da93bc5f52de4c1b967b5f35fe";
$hash_content  = "0201dd7a6e3b787967c12fa9e61d9b6a"; // Hash fisik file

if (md5($sig_logo . $private_key) !== $hash_path) die("Security Breach: Logo path modified! Hubungi hak cipta: bara.n.fahrun (085117476001)");
$logo_path = base64_decode($sig_logo);

if (!file_exists($logo_path) || md5_file($logo_path) !== $hash_content) {
    die("Security Breach: Logo file content compromised or missing! Hubungi hak cipta: bara.n.fahrun (085117476001)");
}

require_once "conf/config.php";
$brand_name = h(get_setting('app_name', 'BexMedia'));

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 0. Validasi CSRF
    csrf_verify();

    // 1. Rate Limiting Check
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $check_rate = safe_query("SELECT attempts, last_attempt FROM login_attempts WHERE ip = ?", [$ip]);
    $rate_data = mysqli_fetch_assoc($check_rate);
    
    if ($rate_data) {
        $last_time = strtotime($rate_data['last_attempt']);
        if ($rate_data['attempts'] >= 2 && (time() - $last_time < 300)) {
            $error = "Terlalu banyak percobaan login! Silakan tunggu 5 menit.";
            write_log("LOGIN_LOCKOUT", "IP $ip terkunci karena terlalu banyak percobaan.");
        }
    }

    if (!$error) {
        // 2. Validasi CAPTCHA
        if (!isset($_SESSION['captcha']) || empty($_POST['captcha'])) {
            $error = "Kode CAPTCHA harus diisi!";
        } elseif (strtoupper($_POST['captcha']) !== $_SESSION['captcha']) {
            $error = "Kode CAPTCHA salah!";
            unset($_SESSION['captcha']);
            write_log("LOGIN_FAILED", "Captcha salah dari IP $ip");
        } else {
            unset($_SESSION['captcha']);
            $username = trim(cleanInput($_POST['username']));
            $password = trim($_POST['password']);
            $login_success = false;
            $user_real = "";
            $source = "";

            // --- TAHAP 1: CEK DI DATABASE BEXMEDIA (INTERNAL) ---
            $sql_bex = "SELECT id, AES_DECRYPT(username, 'bex') as user_real, photo FROM users 
                        WHERE username = AES_ENCRYPT(?, 'bex') 
                        AND password = AES_ENCRYPT(?, 'bara')";
            $res_bex = safe_query($sql_bex, [$username, $password], $conn);

            if ($user_data = mysqli_fetch_assoc($res_bex)) {
                $login_success = true;
                $user_real     = $user_data['user_real'];
                $source        = "BEXMEDIA";
                $user_photo    = $user_data['photo'];
                $_SESSION['user_photo'] = $user_photo;
            } 
            
            // --- TAHAP 2: CEK DI DATABASE KHANZA (REMOTE) JIKA TAHAP 1 GAGAL ---
            if (!$login_success && isset($conn_sik)) {
                // Key default Khanza: 'nur' dan 'windi'
                $sql_sik = "SELECT id_user, AES_DECRYPT(id_user, 'nur') as user_real FROM user 
                            WHERE id_user = AES_ENCRYPT(?, 'nur') 
                            AND password = AES_ENCRYPT(?, 'windi')";
                $res_sik = safe_query($sql_sik, [$username, $password], $conn_sik);

                if ($user_data = mysqli_fetch_assoc($res_sik)) {
                    $login_success = true;
                    $user_real     = $user_data['user_real'];
                    $source        = "KHANZA";
                    $_SESSION['user_photo'] = ""; // Khanza doesn't support photo here yet
                }
            }

            if ($login_success) {
                // RESET RATE LIMIT
                safe_query("DELETE FROM login_attempts WHERE ip = ?", [$ip]);
                
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $user_real;
                $_SESSION['login_source'] = $source;
                
                write_log("LOGIN_SUCCESS", "User $user_real berhasil login via $source.");
                
                header("Location: index.php");
                exit;
            } else {
                // UPDATE RATE LIMIT
                if ($rate_data) {
                    safe_query("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP WHERE ip = ?", [$ip]);
                } else {
                    safe_query("INSERT INTO login_attempts (ip, attempts) VALUES (?, 1)", [$ip]);
                }
                
                $error = "ID User atau Password salah!";
                write_log("LOGIN_FAILED", "Percobaan login gagal untuk username: $username dari IP $ip");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo $brand_name; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --grad-ice-5: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 25%, #BAE6FD 50%, #7DD3FC 75%, #38BDF8 100%);
            --ice-1: #0B192E;
            --ice-3: #3B82F6;
            --font-main: 'Inter', -apple-system, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: var(--grad-ice-5);
            font-family: var(--font-main);
            overflow: hidden;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 48px;
            border-radius: 32px;
            box-shadow: 0 20px 60px rgba(0, 50, 150, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-section {
            margin-bottom: 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0; /* Makin rapat */
        }

        .brand-logo {
            height: 65px;
            width: auto;
            object-fit: contain;
            filter: none;
            margin-bottom: 5px;
        }

        .login-card i {
            color: var(--ice-3);
            margin-bottom: 16px;
        }

        h2 {
            font-family: 'Outfit', sans-serif;
            margin: -8px 0 0 0; /* Tarik teks ke atas agar makin rapat */
            color: #1E40AF; /* Royal Blue yang lebih hidup */
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -0.05em;
        }

        p {
            color: #64748B;
            margin-bottom: 32px;
            font-size: 0.95rem;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--ice-1);
            margin-bottom: 8px;
            margin-left: 4px;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            background: rgba(255, 255, 255, 0.5);
            box-sizing: border-box;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--ice-3);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 12px;
            box-shadow: 0 4px 15px rgba(30, 58, 138, 0.2);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 58, 138, 0.3);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
            padding: 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* CAPTCHA Styles */
        .captcha-box {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.5);
            padding: 8px;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .captcha-box img {
            border-radius: 8px;
            height: 44px;
            flex: 1;
        }

        .btn-refresh {
            background: var(--ice-3);
            color: white;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .btn-refresh:hover {
            transform: rotate(180deg);
            background: #2563EB;
        }

        .captcha-input {
            text-align: center;
            letter-spacing: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748B;
            transition: color 0.3s;
            display: flex;
            align-items: center;
        }

        .password-toggle:hover {
            color: var(--ice-3);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-section" style="margin-bottom: 30px;">
            <img src="<?php echo $logo_path; ?>" alt="BexMedia Logo" class="brand-logo" style="margin: 0 auto;">
        </div>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="passwordInput" placeholder="••••••••" required>
                    <span class="password-toggle" onclick="togglePassword()">
                        <i data-lucide="eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <div class="captcha-box">
                <img src="inc/generate_captcha.php" alt="CAPTCHA" id="captcha-img">
                <button type="button" class="btn-refresh" onclick="refreshCaptcha()">
                    <i data-lucide="refresh-cw" size="20"></i>
                </button>
            </div>

            <div class="form-group">
                <label>Masukkan Kode di Atas</label>
                <input type="text" name="captcha" class="captcha-input" placeholder="KODE" maxlength="6" required autocomplete="off">
            </div>

            <button type="submit" class="btn-login">Masuk ke Dashboard</button>
        </form>
    </div>

    <script>
        lucide.createIcons();

        function refreshCaptcha() {
            const img = document.getElementById('captcha-img');
            img.src = 'inc/generate_captcha.php?' + new Date().getTime();
        }

        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>
