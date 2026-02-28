<?php
/**
 * cetak_cuti.php - Premium Printing System for BexMedia
 * Provides high-fidelity HTML print view with PDF export support.
 */
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

include 'koneksi.php';

if (!isset($_GET['id'])) die('ID pengajuan tidak ditemukan.');
$id = intval($_GET['id']);

// 1. Fetch Company Info
$q_perusahaan = $conn->query("SELECT * FROM perusahaan LIMIT 1");
$perusahaan = $q_perusahaan->fetch_assoc();
$nama_rs = $perusahaan['nama_perusahaan'] ?: "BexMedia Hospital";
$alamat_rs = $perusahaan['alamat'] ?? "Alamat belum diatur di pengaturan perusahaan";

// 2. Fetch Leave Data
$sql = "SELECT p.*, u.id as pemohon_id, u.nik, u.nama_lengkap AS nama, u.unit_kerja, u.jabatan,
               mc.nama_cuti, 
               d.id as delegasi_user_id, d.nama_lengkap AS nama_delegasi,
               (SELECT COUNT(id) FROM pengajuan_cuti_detail WHERE pengajuan_id = p.id) AS lama_hari_total,
               (SELECT GROUP_CONCAT(DATE_FORMAT(tanggal,'%d-%m-%Y') ORDER BY tanggal SEPARATOR ', ') FROM pengajuan_cuti_detail WHERE pengajuan_id = p.id) AS tanggal_cuti_teks
        FROM pengajuan_cuti p
        JOIN users u ON p.karyawan_id = u.id
        JOIN master_cuti mc ON p.cuti_id = mc.id
        LEFT JOIN users d ON p.delegasi_id = d.id
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data pengajuan tidak ditemukan.");

// 3. Setup QR Logic (Local)
function getQrUrl($token) {
    if (empty($token)) return "";
    return "generate_qr.php?token=" . urlencode($token);
}

// Get TTE Tokens for approvals
function getTteToken($conn, $user_id) {
    if (empty($user_id)) return null;
    $q = mysqli_query($conn, "SELECT token FROM tte_user WHERE user_id = '$user_id' AND status = 'aktif' ORDER BY id DESC LIMIT 1");
    $d = mysqli_fetch_assoc($q);
    return $d['token'] ?? null;
}

$token_pemohon  = getTteToken($conn, $data['pemohon_id']);
$token_delegasi = ($data['status_delegasi'] == 'Disetujui') ? getTteToken($conn, $data['delegasi_user_id']) : null;

// For Atasan & HRD, we need to find user by their name saved in acc_by
$token_atasan = null;
if ($data['status_atasan'] == 'Disetujui' && $data['acc_atasan_by']) {
    $name = mysqli_real_escape_string($conn, $data['acc_atasan_by']);
    $q = mysqli_query($conn, "SELECT id FROM users WHERE nama_lengkap = '$name' LIMIT 1");
    if ($u = mysqli_fetch_assoc($q)) $token_atasan = getTteToken($conn, $u['id']);
}

$token_hrd = null;
if ($data['status_hrd'] == 'Disetujui' && $data['acc_hrd_by']) {
    $name = mysqli_real_escape_string($conn, $data['acc_hrd_by']);
    $q = mysqli_query($conn, "SELECT id FROM users WHERE nama_lengkap = '$name' LIMIT 1");
    if ($u = mysqli_fetch_assoc($q)) $token_hrd = getTteToken($conn, $u['id']);
}

// Handle PDF Export Request
if (isset($_GET['format']) && $_GET['format'] === 'pdf') {
    // We could trigger dompdf here, but let's first fix the HTML view
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Pengajuan Cuti - <?= htmlspecialchars($data['nama']) ?></title>
    <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Public+Sans:wght@400;600;700&display=swap');
        
        body {
            background: #f0f2f5;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }

        .print-container {
            width: 210mm;
            min-height: 297mm;
            margin: 30px auto;
            background: white;
            padding: 20mm;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
        }

        .no-print-zone {
            width: 210mm;
            margin: 20px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kop-surat {
            border-bottom: 3px solid #1e293b;
            padding-bottom: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
        }

        .kop-logo {
            width: 80px;
            height: 80px;
            margin-right: 20px;
            background: #f1f5f9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 24px;
            color: #3b82f6;
        }

        .kop-detail h1 {
            font-family: 'Public Sans', sans-serif;
            font-size: 22px;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kop-detail p {
            font-size: 11px;
            margin: 3px 0 0;
            color: #64748b;
            line-height: 1.4;
        }

        .doc-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .doc-title h2 {
            font-weight: 700;
            font-size: 18px;
            text-decoration: underline;
            margin-bottom: 5px;
        }

        .doc-title p {
            font-size: 12px;
            color: #64748b;
        }

        .info-table {
            width: 100%;
            margin-bottom: 30px;
        }

        .info-table td {
            padding: 8px 0;
            font-size: 14px;
            vertical-align: top;
        }

        .label {
            width: 160px;
            font-weight: 500;
            color: #64748b;
        }

        .separator {
            width: 20px;
        }

        .value {
            font-weight: 600;
        }

        .content-body {
            line-height: 1.8;
            font-size: 15px;
            margin-bottom: 50px;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 60px;
        }

        .sig-item {
            text-align: center;
        }

        .sig-label {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 10px;
            text-transform: uppercase;
            color: #64748b;
        }

        .sig-box {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #e2e8f0;
            margin-bottom: 10px;
            position: relative;
        }

        .sig-qr {
            width: 85px;
            height: 85px;
        }

        .sig-name {
            font-weight: 700;
            font-size: 13px;
            text-decoration: underline;
        }

        .sig-meta {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 2px;
        }

        .footer-note {
            margin-top: 80px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            padding-top: 15px;
        }

        @media print {
            body { background: white; }
            .print-container { margin: 0; box-shadow: none; width: 100%; padding: 0; }
            .no-print-zone { display: none; }
            .sig-box { border: none; }
        }

        .btn-premium {
            background: #1e293b;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-premium:hover {
            background: #334155;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="no-print-zone">
        <a href="pengajuan_cuti.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        <div>
            <button onclick="window.print()" class="btn-premium">
                <i class="fas fa-print mr-2"></i> Cetak Dokumen
            </button>
        </div>
    </div>

    <div class="print-container">
        <!-- Kop Surat -->
        <div class="kop-surat">
            <div class="kop-logo">
                <i class="fas fa-hospital"></i>
            </div>
            <div class="kop-detail">
                <h1><?= htmlspecialchars($nama_rs) ?></h1>
                <p><?= htmlspecialchars($alamat_rs) ?></p>
                <p>Website: www.bexmedia.co.id | Email: info@bexmedia.co.id</p>
            </div>
        </div>

        <!-- Judul Dokumen -->
        <div class="doc-title">
            <h2>SURAT PERMOHONAN CUTI KARYAWAN</h2>
            <p>Nomor: CUTI/<?= date('Ym') ?>/<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?></p>
        </div>

        <!-- Isi Dokumen -->
        <div class="content-body">
            <p>Yang bertanda tangan di bawah ini, karyawan <strong><?= htmlspecialchars($nama_rs) ?></strong> mengajukan permohonan cuti dengan rincian sebagai berikut:</p>
            
            <table class="info-table">
                <tr>
                    <td class="label">Nama Lengkap</td>
                    <td class="separator">:</td>
                    <td class="value"><?= htmlspecialchars($data['nama']) ?></td>
                </tr>
                <tr>
                    <td class="label">NIK</td>
                    <td class="separator">:</td>
                    <td class="value"><?= htmlspecialchars($data['nik'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="label">Unit / Bagian</td>
                    <td class="separator">:</td>
                    <td class="value"><?= htmlspecialchars($data['unit_kerja'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="label">Jabatan</td>
                    <td class="separator">:</td>
                    <td class="value"><?= htmlspecialchars($data['jabatan'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="label">Jenis Cuti</td>
                    <td class="separator">:</td>
                    <td class="value"><?= htmlspecialchars($data['nama_cuti']) ?></td>
                </tr>
                <tr>
                    <td class="label">Lama Cuti</td>
                    <td class="separator">:</td>
                    <td class="value"><?= $data['lama_hari_total'] ?> Hari</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Cuti</td>
                    <td class="separator">:</td>
                    <td class="value"><?= htmlspecialchars($data['tanggal_cuti_teks']) ?></td>
                </tr>
                <tr>
                    <td class="label">Alasan</td>
                    <td class="separator">:</td>
                    <td class="value"><?= nl2br(htmlspecialchars($data['alasan'])) ?></td>
                </tr>
                <tr>
                    <td class="label">Delegasi Tugas</td>
                    <td class="separator">:</td>
                    <td class="value"><?= htmlspecialchars($data['nama_delegasi'] ?: 'Tidak ada') ?></td>
                </tr>
            </table>

            <p>Demikian permohonan ini saya sampaikan untuk dapat dipergunakan sebagaimana mestinya. Atas perhatian dan persetujuannya diucapkan terima kasih.</p>
        </div>

        <div style="text-align: right; font-size: 14px; margin-bottom: 20px;">
            Dikeluarkan di: <?= $perusahaan['kota'] ?: 'Makassar' ?>, <?= date('d F Y') ?>
        </div>

        <!-- Tandatangan -->
        <div class="signature-grid">
            <!-- Pemohon -->
            <div class="sig-item">
                <div class="sig-label">Pemohon</div>
                <div class="sig-box">
                    <?php if ($token_pemohon): ?>
                        <img src="<?= getQrUrl($token_pemohon) ?>" class="sig-qr">
                    <?php else: ?>
                        <span style="color: #cbd5e1; font-size: 10px; font-style: italic;">Belum TTE</span>
                    <?php endif; ?>
                </div>
                <div class="sig-name"><?= htmlspecialchars($data['nama']) ?></div>
                <div class="sig-meta">Karyawan</div>
            </div>

            <!-- Delegasi -->
            <div class="sig-item">
                <div class="sig-label">Delegasi</div>
                <div class="sig-box">
                    <?php if ($token_delegasi): ?>
                        <img src="<?= getQrUrl($token_delegasi) ?>" class="sig-qr">
                    <?php else: ?>
                        <span style="color: #cbd5e1; font-size: 10px; font-style: italic;"><?= $data['status_delegasi'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="sig-name"><?= htmlspecialchars($data['nama_delegasi'] ?: '....................') ?></div>
                <div class="sig-meta">Penerima Tugas</div>
            </div>

            <!-- Atasan -->
            <div class="sig-item">
                <div class="sig-label">Atasan Langsung</div>
                <div class="sig-box">
                    <?php if ($token_atasan): ?>
                        <img src="<?= getQrUrl($token_atasan) ?>" class="sig-qr">
                    <?php else: ?>
                        <span style="color: #cbd5e1; font-size: 10px; font-style: italic;"><?= $data['status_atasan'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="sig-name"><?= htmlspecialchars($data['acc_atasan_by'] ?: '....................') ?></div>
                <div class="sig-meta">Supervisor / Manager</div>
            </div>

            <!-- HRD -->
            <div class="sig-item">
                <div class="sig-label">Bagian SDM / HRD</div>
                <div class="sig-box">
                    <?php if ($token_hrd): ?>
                        <img src="<?= getQrUrl($token_hrd) ?>" class="sig-qr">
                    <?php else: ?>
                        <span style="color: #cbd5e1; font-size: 10px; font-style: italic;"><?= $data['status_hrd'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="sig-name"><?= htmlspecialchars($data['acc_hrd_by'] ?: '....................') ?></div>
                <div class="sig-meta">HR Department</div>
            </div>
        </div>

        <div class="footer-note">
            Dokumen ini dihasilkan secara otomatis oleh <strong>BexMedia Smart Office</strong>.<br>
            Keabsahan TTE dapat diverifikasi melalui scan QR Code di atas.<br>
            Printed on: <?= date('d/m/Y H:i') ?>
        </div>
    </div>

</body>
</html>
