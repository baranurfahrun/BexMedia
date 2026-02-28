<?php
/**
 * cetak_ganti_jadwal.php - Premium Printing System for BexMedia
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

// 2. Fetch Data
$sql = "SELECT p.*, u.id as pemohon_id, u.nik, u.nama_lengkap AS nama, u.unit_kerja, u.jabatan,
               d.id as pengganti_user_id, d.nama_lengkap AS nama_pengganti,
               j.nama_jam
        FROM pengajuan_ganti_jadwal p
        JOIN users u ON p.karyawan_id = u.id
        LEFT JOIN users d ON p.pengganti_id = d.id
        LEFT JOIN jam_kerja j ON p.jam_kerja_id = j.id
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data pengajuan tidak ditemukan.");

// 3. Setup QR Logic
function getQrUrl($token) {
    if (empty($token)) return "";
    return "generate_qr.php?token=" . urlencode($token);
}

function getTteToken($conn, $user_id) {
    if (empty($user_id)) return null;
    $q = mysqli_query($conn, "SELECT token FROM tte_user WHERE user_id = '$user_id' AND status = 'aktif' ORDER BY id DESC LIMIT 1");
    $d = mysqli_fetch_assoc($q);
    return $d['token'] ?? null;
}

$token_pemohon   = getTteToken($conn, $data['pemohon_id']);
$token_pengganti = ($data['status_pengganti'] == 'Disetujui') ? getTteToken($conn, $data['pengganti_user_id']) : null;

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Ganti Jadwal - <?= htmlspecialchars($data['nama']) ?></title>
    <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Public+Sans:wght@400;600;700&display=swap');
        
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; color: #1e293b; }
        .print-container { width: 210mm; min-height: 297mm; margin: 30px auto; background: white; padding: 20mm; box-shadow: 0 10px 25px rgba(0,0,0,0.1); position: relative; }
        .no-print-zone { width: 210mm; margin: 20px auto 0; display: flex; justify-content: space-between; align-items: center; }
        .kop-surat { border-bottom: 3px solid #1e293b; padding-bottom: 15px; margin-bottom: 30px; display: flex; align-items: center; }
        .kop-logo { width: 80px; height: 80px; margin-right: 20px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 24px; color: #3b82f6; }
        .kop-detail h1 { font-family: 'Public Sans', sans-serif; font-size: 22px; font-weight: 800; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .kop-detail p { font-size: 11px; margin: 3px 0 0; color: #64748b; line-height: 1.4; }
        
        .doc-title { text-align: center; margin-bottom: 40px; }
        .doc-title h2 { font-weight: 700; font-size: 18px; text-decoration: underline; margin-bottom: 5px; }
        
        .info-table { width: 100%; margin-bottom: 30px; }
        .info-table td { padding: 8px 0; font-size: 14px; vertical-align: top; }
        .label { width: 160px; font-weight: 500; color: #64748b; }
        .value { font-weight: 600; }
        
        .signature-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 60px; }
        .sig-item { text-align: center; }
        .sig-label { font-size: 11px; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; color: #64748b; }
        .sig-box { height: 90px; display: flex; align-items: center; justify-content: center; border: 1px dashed #e2e8f0; margin-bottom: 10px; position: relative; }
        .sig-qr { width: 80px; height: 80px; }
        .sig-name { font-weight: 700; font-size: 12px; text-decoration: underline; }
        .sig-meta { font-size: 10px; color: #94a3b8; }

        @media print {
            body { background: white; }
            .print-container { margin: 0; box-shadow: none; width: 100%; padding: 0; }
            .no-print-zone { display: none; }
            .sig-box { border: none; }
        }
    </style>
</head>
<body>

    <div class="no-print-zone">
        <a href="ganti_jadwal_dinas.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-2"></i> Kembali</a>
        <button onclick="window.print()" class="btn btn-primary font-weight-bold"><i class="fas fa-print mr-2"></i> Cetak Dokumen</button>
    </div>

    <div class="print-container">
        <div class="kop-surat">
            <div class="kop-logo"><i class="fas fa-hospital"></i></div>
            <div class="kop-detail">
                <h1><?= h($nama_rs) ?></h1>
                <p><?= h($alamat_rs) ?></p>
            </div>
        </div>

        <div class="doc-title">
            <h2>SURAT PENGAJUAN GANTI JADWAL DINAS</h2>
            <p style="font-size: 12px;">Nomor: GANTI/<?= date('Ym') ?>/<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?></p>
        </div>

        <div style="line-height: 1.8; font-size: 15px; margin-bottom: 40px;">
            <p>Berhubungan dengan adanya keperluan tertentu, saya yang bertanda tangan di bawah ini:</p>
            
            <table class="info-table">
                <tr><td class="label">Nama Lengkap</td><td width="20">:</td><td class="value"><?= h($data['nama']) ?></td></tr>
                <tr><td class="label">NIK</td><td>:</td><td class="value"><?= h($data['nik'] ?: '-') ?></td></tr>
                <tr><td class="label">Unit Kerja</td><td>:</td><td class="value"><?= h($data['unit_kerja'] ?: '-') ?></td></tr>
            </table>

            <p>Mengajukan permohonan pertukaran/penggantian jadwal dinas pada:</p>
            
            <table class="info-table">
                <tr><td class="label">Hari / Tanggal</td><td width="20">:</td><td class="value"><?= date('l, d F Y', strtotime($data['tanggal'])) ?></td></tr>
                <tr><td class="label">Shift / Jam Kerja</td><td>:</td><td class="value"><?= h($data['nama_jam']) ?></td></tr>
                <tr><td class="label">Alasan</td><td>:</td><td class="value"><?= nl2br(h($data['alasan'])) ?></td></tr>
                <tr><td class="label">Karyawan Pengganti</td><td>:</td><td class="value"><?= h($data['nama_pengganti'] ?: '....................') ?></td></tr>
            </table>

            <p>Demikian permohonan ini saya sampaikan, atas perhatian dan persetujuannya diucapkan terima kasih.</p>
        </div>

        <div style="text-align: right; font-size: 14px; margin-bottom: 20px;">
            <?= $perusahaan['kota'] ?: 'Makassar' ?>, <?= date('d F Y') ?>
        </div>

        <div class="signature-grid">
            <div class="sig-item">
                <div class="sig-label">Pemohon</div>
                <div class="sig-box">
                    <?php if ($token_pemohon): ?> <img src="<?= getQrUrl($token_pemohon) ?>" class="sig-qr"> <?php endif; ?>
                </div>
                <div class="sig-name"><?= h($data['nama']) ?></div>
            </div>
            <div class="sig-item">
                <div class="sig-label">Pengganti</div>
                <div class="sig-box">
                    <?php if ($token_pengganti): ?> <img src="<?= getQrUrl($token_pengganti) ?>" class="sig-qr"> <?php endif; ?>
                </div>
                <div class="sig-name"><?= h($data['nama_pengganti'] ?: '....................') ?></div>
            </div>
            <div class="sig-item">
                <div class="sig-label">Atasan Langsung</div>
                <div class="sig-box">
                    <?php if ($token_atasan): ?> <img src="<?= getQrUrl($token_atasan) ?>" class="sig-qr"> <?php endif; ?>
                </div>
                <div class="sig-name"><?= h($data['acc_atasan_by'] ?: '....................') ?></div>
            </div>
            <div class="sig-item">
                <div class="sig-label">SDM / HRD</div>
                <div class="sig-box">
                    <?php if ($token_hrd): ?> <img src="<?= getQrUrl($token_hrd) ?>" class="sig-qr"> <?php endif; ?>
                </div>
                <div class="sig-name"><?= h($data['acc_hrd_by'] ?: '....................') ?></div>
            </div>
        </div>
    </div>
</body>
</html>
