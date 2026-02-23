<?php
require_once "../conf/config.php";
checkLogin();
// MenuName: Manajemen Klien

$brand_name = get_setting('app_name', 'BexMedia');

// SECURITY CHECK (Same as other pages)
$private_key = "KODE_RAHASIA_BARA";
$sig_logo      = "aW1hZ2VzL2xvZ29fZmluYWwucG5n";
$hash_path     = "55dc42da93bc5f52de4c1b967b5f35fe";
$hash_content  = "0201dd7a6e3b787967c12fa9e61d9b6a"; 
if (md5($sig_logo . $private_key) !== $hash_path) die("Security Breach!");
$logo_path = "../" . base64_decode($sig_logo);
if (!file_exists($logo_path) || md5_file($logo_path) !== $hash_content) die("Security Breach!");

// Check & Create Table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    status ENUM('active', 'inactive', 'on_hold') DEFAULT 'active',
    contact_person VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    last_contract_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Seed sample data if empty
$check_empty = mysqli_query($conn, "SELECT id FROM clients LIMIT 1");
if (mysqli_num_rows($check_empty) == 0) {
    mysqli_query($conn, "INSERT INTO clients (name, type, status, contact_person, phone, email, last_contract_date) VALUES 
    ('RSU Dr. Palemmai Tandi', 'Hospital', 'active', 'H. Andi Ahmad', '08123456789', 'admin@rspalemmai.com', '2026-01-15'),
    ('BPJS Kesehatan Palopo', 'Insurance', 'active', 'Ibu Sarah', '085222333444', 'it@bpjs-kesehatan.go.id', '2025-12-01'),
    ('PT. Medika Sejahtera', 'Vendor', 'on_hold', 'Bpk. Budi', '081122334455', 'sales@medikasejahtera.co.id', '2026-02-10'),
    ('Lab Klinik Prodia', 'Laboratorium', 'active', 'dr. Linda', '081344556677', 'palopo@prodia.co.id', '2026-01-20'),
    ('Apotek Kimia Farma', 'Pharmacy', 'inactive', 'Apoteker Rani', '082199887766', 'kf.palopo@kimiafarma.com', '2025-11-15')");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="../images/logo_final.png">
    
  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients Management | <?php echo h($brand_name); ?></title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../css/index.css">
    <style>
        .client-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 30px;
        }

        .client-card {
            background: white;
            padding: 24px;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border: 1px solid #F1F5F9;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .client-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.06);
            border-color: #3B82F6;
        }

        .client-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: #3B82F6;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .client-card:hover::before { opacity: 1; }

        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .client-icon {
            width: 48px;
            height: 48px;
            background: #F0F7FF;
            color: #3B82F6;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .client-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--ice-1);
            margin: 12px 0 4px 0;
        }

        .client-type {
            font-size: 0.8rem;
            color: #64748B;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .client-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #F1F5F9;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #475569;
        }

        .info-item i { color: #94A3B8; }

        .status-pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .pill-active { background: #DCFCE7; color: #166534; }
        .pill-inactive { background: #FEE2E2; color: #991B1B; }
        .pill-hold { background: #FEF3C7; color: #92400E; }

        .action-btns {
            display: flex;
            gap: 10px;
            margin-top: 24px;
        }

        .btn-action {
            flex: 1;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #E2E8F0;
            background: white;
            color: #64748B;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-action:hover {
            background: #F8FAFC;
            border-color: #CBD5E1;
            color: var(--ice-1);
        }

        .btn-whatsapp:hover {
            background: #25D366;
            color: white;
            border-color: #25D366;
        }

        .client-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-value { font-size: 1.25rem; font-weight: 800; color: var(--ice-1); }
        .stat-label { font-size: 0.75rem; color: #64748B; font-weight: 600; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php include "sidebar.php"; ?>

        <main>
            <header>
                <div class="breadcrumb" style="color: var(--text-muted); font-size: 0.9rem">
                    Pages / <strong>Clients</strong>
                </div>
                <div class="user-profile">
                    <span><?php echo h($_SESSION['username']); ?></span>
                    <?php 
                        $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['username']) . "&background=3B82F6&color=fff";
                        if (isset($_SESSION['user_photo']) && !empty($_SESSION['user_photo'])) {
                            $avatar_url = "../images/" . $_SESSION['user_photo'];
                        }
                    ?>
                    <div class="avatar" style="background-image: url('<?php echo $avatar_url; ?>'); background-size: cover; background-position: center;"></div>
                </div>
            </header>

            <div class="dashboard-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <div>
                        <h1>Client Management</h1>
                        <p>Manage your institutional partners and media clients.</p>
                    </div>
                    <button class="btn-create" style="padding: 12px 24px;">+ Add New Client</button>
                </div>

                <div class="client-stats">
                    <div class="stat-box">
                        <div class="stat-icon" style="background: #E0F2FE; color: #0369A1;"><i data-lucide="users" size="20"></i></div>
                        <div><div class="stat-value">24</div><div class="stat-label">Total Clients</div></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon" style="background: #DCFCE7; color: #15803D;"><i data-lucide="check-circle" size="20"></i></div>
                        <div><div class="stat-value">18</div><div class="stat-label">Active Partners</div></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon" style="background: #FEF3C7; color: #B45309;"><i data-lucide="clock" size="20"></i></div>
                        <div><div class="stat-value">3</div><div class="stat-label">On Hold</div></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon" style="background: #F1F5F9; color: #475569;"><i data-lucide="file-text" size="20"></i></div>
                        <div><div class="stat-value">Rp 2.4B</div><div class="stat-label">Revenue Growth</div></div>
                    </div>
                </div>

                <div class="client-grid">
                    <?php
                    $res = mysqli_query($conn, "SELECT * FROM clients ORDER BY name ASC");
                    while ($c = mysqli_fetch_assoc($res)):
                        $stClass = "pill-active";
                        $stLabel = "Active";
                        if ($c['status'] == 'inactive') { $stClass = "pill-inactive"; $stLabel = "Inactive"; }
                        if ($c['status'] == 'on_hold') { $stClass = "pill-hold"; $stLabel = "On Hold"; }
                        
                        $icon = "building";
                        if (stripos($c['type'], 'Hospital') !== false) $icon = "hospital";
                        if (stripos($c['type'], 'Insurance') !== false) $icon = "shield";
                        if (stripos($c['type'], 'Laboratorium') !== false) $icon = "test-tube";
                    ?>
                    <div class="client-card">
                        <div class="client-header">
                            <div class="client-icon"><i data-lucide="<?php echo $icon; ?>"></i></div>
                            <span class="status-pill <?php echo $stClass; ?>"><?php echo $stLabel; ?></span>
                        </div>
                        <div class="client-type"><?php echo h($c['type']); ?></div>
                        <div class="client-name"><?php echo h($c['name']); ?></div>
                        
                        <div class="client-info">
                            <div class="info-item"><i data-lucide="user" size="14"></i> <?php echo h($c['contact_person']); ?></div>
                            <div class="info-item"><i data-lucide="mail" size="14"></i> <?php echo h($c['email']); ?></div>
                            <div class="info-item"><i data-lucide="calendar" size="14"></i> Contract: <?php echo konversiTanggal($c['last_contract_date']); ?></div>
                        </div>

                        <div class="action-btns">
                            <button class="btn-action btn-whatsapp" title="Send WhatsApp">
                                <i data-lucide="message-square" size="16"></i> Chat
                            </button>
                            <button class="btn-action" title="View Details">
                                <i data-lucide="eye" size="16"></i> Details
                            </button>
                            <button class="btn-action" title="Edit Client">
                                <i data-lucide="edit-3" size="16"></i>
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Dynamic Footer (Marquee & Copyright) -->
    <?php include "footer.php"; ?>
</body>
</html>







