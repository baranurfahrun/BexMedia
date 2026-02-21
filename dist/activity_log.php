<?php
include '../conf/config.php';
include 'security.php';
include 'koneksi.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Pagination
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter
$filter_user = isset($_GET['user']) ? mysqli_real_escape_string($conn, $_GET['user']) : '';
$filter_action = isset($_GET['action_type']) ? mysqli_real_escape_string($conn, $_GET['action_type']) : '';
$filter_date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

// Build WHERE
$where = [];
if ($filter_user !== '') $where[] = "u.nama LIKE '%$filter_user%'";
if ($filter_action !== '') $where[] = "a.action = '$filter_action'";
if ($filter_date !== '') $where[] = "DATE(a.created_at) = '$filter_date'";
$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM activity_log a LEFT JOIN users u ON a.user_id = u.id $where_sql";
$count_res = $conn->query($count_query);
$total_records = $count_res ? $count_res->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_records / $limit);

// Get activity data
$sql = "SELECT a.*, u.nama as user_nama 
        FROM activity_log a 
        LEFT JOIN users u ON a.user_id = u.id 
        $where_sql 
        ORDER BY a.created_at DESC 
        LIMIT $limit OFFSET $offset";
$activities = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universal Activity Log - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .log-grid {
            margin-top: 24px;
        }

        .filter-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 24px;
        }

        .table-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 16px;
            background: rgba(255, 255, 255, 0.05);
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px;
        }

        .action-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-login { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-logout { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-create { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-update { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-delete { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

        .ip-text {
            font-family: 'Courier New', Courier, monospace;
            background: rgba(255, 255, 255, 0.05);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .pagination {
            display: flex;
            gap: 8px;
            margin-top: 24px;
            justify-content: center;
        }

        .page-link {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .page-link.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .page-link:hover:not(.active) {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Universal Activity Log</h1>
                    <p>Audit trail transparant untuk seluruh aktivitas sistem.</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-outline" onclick="location.reload()">
                        <i data-lucide="refresh-cw"></i> Refresh
                    </button>
                </div>
            </header>

            <div class="log-grid">
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div class="input-group">
                            <label><small>Nama User</small></label>
                            <input type="text" name="user" value="<?= htmlspecialchars($filter_user) ?>" placeholder="Cari user...">
                        </div>
                        <div class="input-group">
                            <label><small>Jenis Aksi</small></label>
                            <select name="action_type">
                                <option value="">Semua Aksi</option>
                                <option value="login" <?= $filter_action == 'login' ? 'selected' : '' ?>>Login</option>
                                <option value="logout" <?= $filter_action == 'logout' ? 'selected' : '' ?>>Logout</option>
                                <option value="create" <?= $filter_action == 'create' ? 'selected' : '' ?>>Create</option>
                                <option value="update" <?= $filter_action == 'update' ? 'selected' : '' ?>>Update</option>
                                <option value="delete" <?= $filter_action == 'delete' ? 'selected' : '' ?>>Delete</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label><small>Tanggal</small></label>
                            <input type="date" name="date" value="<?= $filter_date ?>">
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i data-lucide="search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="table-card">
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Platform</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($activities && $activities->num_rows > 0): ?>
                                    <?php while ($row = $activities->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                            <td><strong><?= htmlspecialchars($row['user_nama'] ?? 'System/Guest') ?></strong></td>
                                            <td>
                                                <span class="action-badge badge-<?= strtolower($row['action']) ?>">
                                                    <?= $row['action'] ?>
                                                </span>
                                            </td>
                                            <td style="max-width:300px;"><?= htmlspecialchars($row['description']) ?></td>
                                            <td><span class="ip-text"><?= $row['ip_address'] ?></span></td>
                                            <td><small style="opacity:0.6;"><?= substr($row['user_agent'], 0, 30) ?>...</small></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center; padding: 40px; opacity:0.5;">
                                            Tidak ada log aktivitas ditemukan.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>&user=<?= urlencode($filter_user) ?>&action_type=<?= $filter_action ?>&date=<?= $filter_date ?>" 
                                   class="page-link <?= $i == $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
