<?php
require_once "../conf/config.php";
checkLogin();
// MenuName: Dashboard Admin

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit;
}

// === KODE VERIFIKASI KEAMANAN PROJEK ===
$private_key = "KODE_RAHASIA_BARA";

// 1. Verifikasi Hak Cipta
$sig_copyright  = "QDIwMjYgYmFyYS5uLmZhaHJ1bi0wODUxMTc0NzYwMDE=";
$hash_copyright = "3e07d2217d54524233697deb8b497061";
if (md5($sig_copyright . $private_key) !== $hash_copyright) die("Security Breach: Copyright modified!");
$copyright_text = base64_decode($sig_copyright);

// 2. Verifikasi Nama Brand
$sig_brand  = "QmV4TWVkaWE=";
$hash_brand = "1d45b0cc7a28442c082bd43bd312ac88";
if (md5($sig_brand . $private_key) !== $hash_brand) die("Security Breach: Brand name modified!");
$brand_name = base64_decode($sig_brand);

// 3. Verifikasi Path & Konten Logo (Double Layer Security)
$sig_logo      = "aW1hZ2VzL2xvZ29fZmluYWwucG5n";
$hash_path     = "55dc42da93bc5f52de4c1b967b5f35fe";
$hash_content  = "0201dd7a6e3b787967c12fa9e61d9b6a"; // Hash fisik file

if (md5($sig_logo . $private_key) !== $hash_path) die("Security Breach: Logo path modified!");
$logo_path = "../" . base64_decode($sig_logo);

if (!file_exists($logo_path) || md5_file($logo_path) !== $hash_content) {
    die("Security Breach: Logo file content compromised or missing! Hubungi hak cipta: bara.n.fahrun (085117476001)");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BexMedia Dashboard | Swiss Minimalist</title>
    <!-- Lucide Icons (Professional Swiss Vibe) -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../css/index.css">
</head>

<body>
    <div class="app-container">
        <!-- Sidebar (Left) -->
        <?php include "sidebar.php"; ?>

        <!-- Main Content Area -->
        <main>
            <header>
                <div class="search-bar">
                    <i data-lucide="search" size="18" style="color: var(--text-muted)"></i>
                    <input type="text" placeholder="Search...">
                </div>

                <div class="header-actions">
                    <button class="btn-create">+ Create New Campaign</button>
                    <div style="position: relative; color: var(--text-muted)">
                        <i data-lucide="bell"></i>
                        <span
                            style="position: absolute; top: 0; right: 0; width: 8px; height: 8px; background: red; border-radius: 50%; border: 2px solid white"></span>
                    </div>
                    <div class="user-profile">
                        <span style="font-size: 0.85rem; font-weight: 600"><?php echo h($_SESSION['username']); ?></span>
                        <?php 
                            $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['username']) . "&background=3B82F6&color=fff";
                            if (isset($_SESSION['user_photo']) && !empty($_SESSION['user_photo'])) {
                                $avatar_url = "../images/" . $_SESSION['user_photo'];
                            }
                        ?>
                        <div class="avatar"
                             style="background-image: url('<?php echo $avatar_url; ?>'); background-size: cover; background-position: center;"></div>
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Dashboard</h1>
                    <p>High-end professional dashboard for a large studio monitor</p>
                </div>

                <div class="dashboard-grid">
                    <!-- Analytics Overview -->
                    <div class="card">
                        <div class="card-title">
                            Analytics Overview
                            <i data-lucide="more-horizontal" style="color: var(--text-muted)"></i>
                        </div>
                        <div style="display: flex; gap: 40px; margin-bottom: 20px;">
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted)">Total Analytics</span>
                                <div style="font-size: 1.5rem; font-weight: 700">33.8M</div>
                            </div>
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted)">Recent Users</span>
                                <div style="font-size: 1.5rem; font-weight: 700">38.5K <span
                                        style="color: #48BB78; font-size: 0.8rem">+3.5%</span></div>
                            </div>
                        </div>
                        <div class="stats-bars">
                            <div class="bar">
                                <div class="bar-fill" style="height: 60%"></div><span class="bar-label">Jan</span>
                            </div>
                            <div class="bar">
                                <div class="bar-fill" style="height: 40%"></div><span class="bar-label">Feb</span>
                            </div>
                            <div class="bar">
                                <div class="bar-fill" style="height: 80%"></div><span class="bar-label">Mar</span>
                            </div>
                            <div class="bar">
                                <div class="bar-fill" style="height: 55%"></div><span class="bar-label">Apr</span>
                            </div>
                            <div class="bar">
                                <div class="bar-fill" style="height: 90%"></div><span class="bar-label">May</span>
                            </div>
                            <div class="bar">
                                <div class="bar-fill" style="height: 70%"></div><span class="bar-label">Jun</span>
                            </div>
                            <div class="bar">
                                <div class="bar-fill" style="height: 45%"></div><span class="bar-label">Jul</span>
                            </div>
                            <div class="bar">
                                <div class="bar-fill" style="height: 65%"></div><span class="bar-label">Aug</span>
                            </div>
                        </div>
                    </div>

                    <!-- Media Assets -->
                    <div class="card">
                        <div class="card-title">
                            Media Assets
                            <button class="btn-secondary">
                                <i data-lucide="download" size="14"></i>
                                Export Report
                            </button>
                        </div>
                        <div class="media-assets">
                            <div class="media-item"><img src="https://picsum.photos/400/225?random=1"
                                    class="media-placeholder"></div>
                            <div class="media-item"><img src="https://picsum.photos/400/225?random=2"
                                    class="media-placeholder"></div>
                            <div class="media-item"><img src="https://picsum.photos/400/225?random=3"
                                    class="media-placeholder"></div>
                            <div class="media-item"><img src="https://picsum.photos/400/225?random=4"
                                    class="media-placeholder"></div>
                            <div class="media-item"><img src="https://picsum.photos/400/225?random=5"
                                    class="media-placeholder"></div>
                            <div class="media-item"><img src="https://picsum.photos/400/225?random=6"
                                    class="media-placeholder"></div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="card">
                        <div class="card-title">Performance Metrics</div>
                        <div style="display: flex; gap: 40px; margin-bottom: 20px;">
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted)">Engagements</span>
                                <div style="font-size: 1.25rem; font-weight: 700">7827 <span
                                        style="color: #48BB78; font-size: 0.7rem">+6.8%</span></div>
                            </div>
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted)">Performance</span>
                                <div style="font-size: 1.25rem; font-weight: 700">78.2% <span
                                        style="color: #48BB78; font-size: 0.7rem">+3.3%</span></div>
                            </div>
                        </div>
                        <div class="line-chart-placeholder">
                            <div class="chart-line-avg"></div>
                            <div class="chart-fill"></div>
                        </div>
                    </div>

                    <!-- Client Data -->
                    <div class="card">
                        <div class="card-title">Client Data</div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Growth</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Jameson Sarez</td>
                                    <td><span class="status-tag tag-success">Active</span></td>
                                    <td>+5.8%</td>
                                </tr>
                                <tr>
                                    <td>Aaron Flarey</td>
                                    <td><span class="status-tag tag-success">Active</span></td>
                                    <td>+4.2%</td>
                                </tr>
                                <tr>
                                    <td>Malvik Serey</td>
                                    <td><span class="status-tag tag-success">Active</span></td>
                                    <td>+6.2%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>


    <div class="running-text-container">
        <div class="marquee-content">
            <div class="running-text">
                <span>BexMedia News: Campaign and Analytics are performing at peak efficiency this month! • Welcome to the premium studio monitor dashboard • New Media Assets have been uploaded for the client review • Stay tuned for more updates! • </span>
                <span>BexMedia News: Campaign and Analytics are performing at peak efficiency this month! • Welcome to the premium studio monitor dashboard • New Media Assets have been uploaded for the client review • Stay tuned for more updates! • </span>
            </div>
        </div>
        <div class="fixed-copyright">
            <?php echo $copyright_text; ?>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>

</html>