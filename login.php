<?php
// === KODE VERIFIKASI KEAMANAN PROJEK ===
$private_key = "KODE_RAHASIA_BARA";

// Verifikasi Nama Brand (Key for Security Check)
$sig_brand  = "QmV4TWVkaWE=";
$hash_brand = "1d45b0cc7a28442c082bd43bd312ac88";
if (md5($sig_brand . $private_key) !== $hash_brand) die("Security Breach!");

// --- TAHANAN LOGO / SECURITY BREACH DESIGN (FIXPOINT STYLE) ---
function show_breach($title, $msg) {
    die('
    <div style="max-width:600px;margin:80px auto;font-family:\'Inter\',sans-serif;">
      <div style="border:2px solid #ef4444;border-radius:24px;padding:40px;background:#fef2f2;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);">
        <div style="background:#ef4444;width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
            <svg style="color:white;width:32px;height:32px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <h2 style="color:#1e1b4b;margin-bottom:16px;font-weight:800;letter-spacing:-0.025em">' . $title . '</h2>
        <p style="color:#ef4444;font-size:16px;line-height:1.6;margin-bottom:24px;font-weight:500">' . $msg . '</p>
        <div style="padding:16px;background:white;border-radius:12px;color:#64748b;font-size:13px;border:1px solid #fee2e2">
          🔒 Sistem mendeteksi anomali pada integritas file utama. Akses ditangguhkan demi keamanan data.
        </div>
      </div>
    </div>');
}

// Verifikasi Path & Konten Logo (Double Layer Security)
$sig_logo      = "aW1hZ2VzL2xvZ29fZmluYWwucG5n";
$hash_path     = "55dc42da93bc5f52de4c1b967b5f35fe";
$hash_content  = "0201dd7a6e3b787967c12fa9e61d9b6a"; // Hash fisik file

if (md5($sig_logo . $private_key) !== $hash_path) {
    show_breach("Integritas Path Terganggu", "Security Breach: Logo path modified! Hubungi <a href='https://wa.me/6285117476001' style='color:#ef4444;font-weight:bold;text-decoration:underline;'>Bara N. Fahrun (085117476001)</a> untuk bantuan.");
}
$logo_path = base64_decode($sig_logo);

if (!file_exists($logo_path) || md5_file($logo_path) !== $hash_content) {
    show_breach("Modifikasi Tidak Sah", "Security Breach: Logo file compromised! Hubungi <a href='https://wa.me/6285117476001' style='color:#ef4444;font-weight:bold;text-decoration:underline;'>Bara N. Fahrun (085117476001)</a> untuk bantuan.");
}

require_once "conf/config.php";
$brand_name = h(get_setting('app_name', 'BexMedia'));
$app_version = h(get_setting('app_version', 'V. 1. - .20.02.2026'));

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: dist/index.php");
    exit;
}

// --- GET ERROR FROM SESSION (PRG PATTERN) ---
$error = $_SESSION['login_error'] ?? "";
unset($_SESSION['login_error']);

$reg_success = $_SESSION['register_success'] ?? "";
unset($_SESSION['register_success']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_verify();

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $check_rate = safe_query("SELECT attempts, last_attempt FROM login_attempts WHERE ip = ?", [$ip]);
    $rate_data = mysqli_fetch_assoc($check_rate);
    
    if ($rate_data) {
        $last_time = strtotime($rate_data['last_attempt']);
        if ($rate_data['attempts'] >= 5 && (time() - $last_time < 300)) {
            $_SESSION['login_error'] = "Terlalu banyak percobaan! Silakan tunggu 5 menit.";
            write_log("LOGIN_LOCKOUT", "IP $ip terkunci.");
            header("Location: login.php"); exit;
        }
    }

    if (!isset($_SESSION['captcha']) || empty($_POST['captcha']) || strtoupper($_POST['captcha']) !== $_SESSION['captcha']) {
        $_SESSION['login_error'] = "Kode Keamanan (CAPTCHA) Salah!";
        unset($_SESSION['captcha']);
        write_log("LOGIN_FAILED", "Captcha salah dari $ip");
        header("Location: login.php"); exit;
    }

    unset($_SESSION['captcha']);
    $username = trim(cleanInput($_POST['username']));
    $password = trim($_POST['password']);
    $login_success = false;
    $user_real = "";
    $source = "";

    // 1. CEK INTERNAL BEXMEDIA
    $res_bex = safe_query("SELECT id, AES_DECRYPT(username, 'bex') as user_real, photo, status FROM users 
                           WHERE username = AES_ENCRYPT(?, 'bex') AND password = AES_ENCRYPT(?, 'bara')", [$username, $password]);

    if ($user_data = mysqli_fetch_assoc($res_bex)) {
        if ($user_data['status'] === 'pending') {
            $_SESSION['login_error'] = "Akun Anda sedang menunggu aktivasi oleh administrator.";
            header("Location: login.php"); exit;
        } elseif ($user_data['status'] === 'blocked') {
            $_SESSION['login_error'] = "Akun Anda dinonaktifkan. Silakan hubungi bagian IT.";
            header("Location: login.php"); exit;
        }
        
        $login_success = true;
        $user_real     = $user_data['user_real'];
        $source        = "BEXMEDIA";
        $_SESSION['user_photo'] = $user_data['photo'];
    } 
    
    // 2. CEK MASTER KHANZA
    if (!$login_success && isset($conn_sik)) {
        $res_sik = safe_query("SELECT id_user, AES_DECRYPT(id_user, 'nur') as user_real FROM user 
                               WHERE id_user = AES_ENCRYPT(?, 'nur') AND password = AES_ENCRYPT(?, 'windi')", [$username, $password], $conn_sik);
        if ($user_data = mysqli_fetch_assoc($res_sik)) {
            $login_success = true;
            $user_real     = $user_data['user_real'];
            $source        = "KHANZA";
            $_SESSION['user_photo'] = "";
        }
    }

    if ($login_success) {
        safe_query("DELETE FROM login_attempts WHERE ip = ?", [$ip]);
        session_regenerate_id(true); 
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $user_real;
        $_SESSION['login_source'] = $source;
        write_log("LOGIN_SUCCESS", "User $user_real masuk via $source.");
        header("Location: dist/index.php"); exit;
    } else {
        if ($rate_data) {
            safe_query("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP WHERE ip = ?", [$ip]);
        } else {
            safe_query("INSERT INTO login_attempts (ip, attempts) VALUES (?, 1)", [$ip]);
        }
        $_SESSION['login_error'] = "ID User atau Password Tidak Dikenali!";
        write_log("LOGIN_FAILED", "Login gagal: $username dari $ip");
        header("Location: login.php"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="images/logo_final.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo $brand_name; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --grad-ice-5: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 25%, #BAE6FD 50%, #7DD3FC 75%, #38BDF8 100%);
            --ice-1: #0B192E;
            --ice-3: #3B82F6;
            --font-main: 'Inter', -apple-system, sans-serif;
        }

        body {
            margin: 0; padding: 0; display: flex; justify-content: center; align-items: center;
            min-height: 100vh; background: var(--grad-ice-5); font-family: var(--font-main); overflow: hidden;
        }

        .bg-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.05) 0%, transparent 40%),
                        radial-gradient(circle at 80% 80%, rgba(30, 58, 138, 0.05) 0%, transparent 40%);
            z-index: -1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.5); padding: 48px; border-radius: 32px;
            box-shadow: 0 20px 60px rgba(0, 50, 150, 0.1); width: 100%; max-width: 400px;
            text-align: center; animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .logo-section { margin-bottom: 30px; display: flex; flex-direction: column; align-items: center; }
        .brand-logo { height: 65px; width: auto; object-fit: contain; margin-bottom: 5px; }

        label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--ice-1); margin-bottom: 8px; text-align: left; margin-left: 4px; }
        .form-group { text-align: left; margin-bottom: 20px; }
        input { 
            width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid rgba(0, 0, 0, 0.05);
            background: rgba(255, 255, 255, 0.5); box-sizing: border-box; font-size: 1rem; transition: all 0.3s ease;
        }
        input:focus { outline: none; border-color: var(--ice-3); background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }

        .btn-login {
            width: 100%; padding: 16px; background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
            color: white; border: none; border-radius: 14px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease; margin-top: 12px; box-shadow: 0 4px 15px rgba(30, 58, 138, 0.2);
        }
        .btn-login:disabled { opacity: 0.8 !important; cursor: not-allowed; background: #64748B !important; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(30, 58, 138, 0.3); }

        .captcha-box { 
            display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.5); padding: 8px; border-radius: 12px; border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .captcha-box img { border-radius: 8px; height: 44px; flex: 1; }
        .btn-refresh { 
            background: var(--ice-3); color: white; border: none; width: 44px; height: 44px;
            border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;
        }
        .btn-refresh:hover { transform: rotate(180deg); background: #2563EB; }

        .password-container { position: relative; }
        .password-toggle { 
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #64748B; transition: color 0.3s; display: flex; align-items: center;
        }
        .bg-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.05) 0%, transparent 40%),
                        radial-gradient(circle at 80% 80%, rgba(30, 58, 138, 0.05) 0%, transparent 40%);
            z-index: -1;
        }

        /* MODAL STYLES (PREMIUM GLASS) */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.3); backdrop-filter: blur(12px);
            display: flex; justify-content: center; align-items: center;
            z-index: 1000; opacity: 0; visibility: hidden; transition: all 0.4s ease;
        }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-card {
            background: rgba(255, 255, 255, 0.95); width: 90%; max-width: 800px;
            border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 32px; transform: scale(0.9); transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .modal-overlay.active .modal-card { transform: scale(1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn-close { background: none; border: none; cursor: pointer; color: #94A3B8; transition: color 0.3s; }
        .btn-close:hover { color: #EF4444; }
        
        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 600px) { .modal-grid { grid-template-columns: 1fr; } }

        select {
            width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.1);
            background: rgba(255, 255, 255, 0.8); font-size: 0.95rem; appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;
        }

        .btn-register {
            width: auto; padding: 14px 32px; background: #3B82F6; color: white; border: none;
            border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }
        .btn-register:hover { background: #2563EB; transform: translateY(-2px); }
        .modal-footer { display: flex; justify-content: flex-end; margin-top: 32px; }
        .spin { animation: rotate 1s linear infinite; display: inline-block; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="login-card">
        <div class="logo-section">
            <img src="<?php echo $logo_path; ?>" alt="Logo" class="brand-logo">
        </div>

        <?php if ($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error', title: 'Akses Ditolak', text: '<?php echo $error; ?>',
                        confirmButtonColor: '#3B82F6', background: 'rgba(255, 255, 255, 0.95)', backdrop: 'rgba(0,0,0,0.1)'
                    });
                });
            </script>
        <?php endif; ?>

        <?php if ($reg_success): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success', title: 'Registrasi Berhasil', text: '<?php echo $reg_success; ?>',
                        confirmButtonColor: '#3B82F6', background: 'rgba(255, 255, 255, 0.95)'
                    });
                });
            </script>
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
                <label>Kode Keamanan</label>
                <input type="text" name="captcha" style="text-align:center; letter-spacing:4px; font-weight:700; text-transform:uppercase;" placeholder="KODE" maxlength="6" required autocomplete="off">
            </div>

            <button type="submit" class="btn-login" id="loginBtn">Masuk ke Dashboard</button>
        </form>

        <div class="footer-links" style="margin-top: 24px;">
            <a href="javascript:void(0)" onclick="Swal.fire({title:'Lupa Password', text:'Sistem pemulihan password otomatis sedang dikonfigurasi. Hubungi Admin IT.', icon:'info', confirmButtonColor:'#3B82F6'})" style="color: #3B82F6; text-decoration: none; font-size: 0.9rem;">
                Lupa Password? <i data-lucide="help-circle" style="width: 16px; height: 16px; color: #EF4444; vertical-align: middle; margin-top: -2px;"></i>
            </a>
            <p style="margin-top: 12px; color: #64748B; font-size: 0.85rem;">
                Belum punya akun? <a href="javascript:void(0)" onclick="openRegister()" style="color: #3B82F6; text-decoration: none;">Daftar di sini</a>
            </p>
        </div>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(0,0,0,0.05); font-size: 0.8rem; color: #94A3B8; letter-spacing: 0.02em;">
            © 2026 <?php echo $brand_name; ?>, <?php echo $app_version; ?>
        </div>
    </div>

    <!-- MODAL REGISTER (FIXPOINT STYLE) -->
    <div id="modalRegister" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="user-plus" style="color: #3B82F6;"></i>
                    <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-weight: 700; color: #1E293B;">Daftar Akun Baru</h3>
                </div>
                <button type="button" class="btn-close" onclick="closeRegister()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <form action="proses_register.php" method="POST" id="regForm">
                <?php echo csrf_field(); ?>
                <div class="modal-grid">
                    <div class="form-group">
                        <label>NIK / NIP Karyawan <span style="color: #94A3B8; font-weight: 400;">(Bukan NIK KTP)</span></label>
                        <input type="text" name="nik" placeholder="Masukkan NIK/NIP" required maxlength="30">
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" placeholder="Nama sesuai SK" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <select name="jabatan" required>
                            <option value="">Pilih Jabatan</option>
                            <?php 
                            $jabs = mysqli_query($conn, "SELECT nama_jabatan FROM jabatan ORDER BY nama_jabatan");
                            while($j = mysqli_fetch_assoc($jabs)) echo "<option value='{$j['nama_jabatan']}'>{$j['nama_jabatan']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unit Kerja</label>
                        <select name="unit_kerja" required>
                            <option value="">Pilih Unit</option>
                            <?php 
                            $units = mysqli_query($conn, "SELECT nama_unit FROM unit_kerja ORDER BY nama_unit");
                            while($u = mysqli_fetch_assoc($units)) echo "<option value='{$u['nama_unit']}'>{$u['nama_unit']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="example@mail.com" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-container">
                            <input type="password" name="password" id="regPass" placeholder="Minimal 8 karakter" required minlength="8">
                            <span class="password-toggle" onclick="togglePass('regPass', 'eyeReg')">
                                <i data-lucide="eye" id="eyeReg"></i>
                            </span>
                        </div>
                        <small style="color: #94A3B8; font-size: 0.75rem; margin-top: 4px; display: block;">Minimal 8 karakter</small>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password</label>
                        <div class="password-container">
                            <input type="password" name="konfirmasi_password" id="regConf" placeholder="Ulangi password" required>
                            <span class="password-toggle" onclick="togglePass('regConf', 'eyeConf')">
                                <i data-lucide="eye" id="eyeConf"></i>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Atasan Langsung</label>
                        <select name="atasan_id">
                            <option value="">Pilih Atasan</option>
                            <?php 
                            $atasans = safe_query("SELECT id, AES_DECRYPT(username, 'bex') as name FROM users WHERE status = 'active' ORDER BY name");
                            while($a = mysqli_fetch_assoc($atasans)) echo "<option value='{$a['id']}'>{$a['name']}</option>";
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-register" id="regBtn">
                        <i data-lucide="user-plus" style="width: 18px; margin-right: 8px;"></i> Daftar Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openRegister() { document.getElementById('modalRegister').classList.add('active'); }
        function closeRegister() { document.getElementById('modalRegister').classList.remove('active'); }
        
        function togglePass(id, iconId) {
            const input = document.getElementById(id);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        document.getElementById('regForm').addEventListener('submit', function(e) {
            const p = document.getElementById('regPass').value;
            const c = document.getElementById('regConf').value;
            if(p !== c) {
                e.preventDefault();
                Swal.fire({icon:'error', title:'Error', text:'Konfirmasi password tidak cocok!', confirmButtonColor:'#3B82F6'});
                return;
            }
            const btn = document.getElementById('regBtn');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="spin" style="width:18px; margin-right:8px;"></i> Memproses...';
            lucide.createIcons();
        });
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="spin" style="margin-right:8px; width:18px; height:18px"></i> Menghubungkan...';
            lucide.createIcons();
        });

        function refreshCaptcha() {
            document.getElementById('captcha-img').src = 'inc/generate_captcha.php?' + new Date().getTime();
        }

        function togglePassword() {
            const pi = document.getElementById('passwordInput');
            const ei = document.getElementById('eyeIcon');
            if (pi.type === 'password') {
                pi.type = 'text';
                ei.setAttribute('data-lucide', 'eye-off');
            } else {
                pi.type = 'password';
                ei.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>
