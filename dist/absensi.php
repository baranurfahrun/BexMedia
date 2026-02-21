<?php
include '../conf/config.php';
include 'security.php';
include 'koneksi.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Cek akses menu (BexMedia style)
$current_file = basename(__FILE__);
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = ? AND menu.file_menu = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $current_file);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0 && $_SESSION['role'] !== 'admin') {
    // Porting rule: Jika admin tetap boleh akses meski belum terdaftar di menu
    // echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='index.php';</script>";
    // exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Smart GPS - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .absensi-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 24px;
        }

        @media (max-width: 992px) {
            .absensi-container {
                grid-template-columns: 1fr;
            }
        }

        .camera-box {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        #video {
            width: 100%;
            border-radius: 12px;
            background: #000;
            transform: scaleX(-1); /* Mirror effect */
        }

        #canvas {
            display: none;
        }

        .capture-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            height: 80%;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            pointer-events: none;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
        }

        .info-item i {
            color: var(--primary-color);
        }

        .absen-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-absen {
            padding: 16px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-masuk { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .btn-keluar { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .btn-istirahat { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .btn-kembali { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }

        .btn-absen:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            filter: brightness(1.1);
        }

        .btn-absen:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        #location-status {
            font-size: 14px;
            margin-top: 8px;
        }

        .text-success { color: #10b981; }
        .text-danger { color: #ef4444; }
        .text-warning { color: #f59e0b; }

        .log-table-container {
            margin-top: 24px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: rgba(255, 255, 255, 0.8);
        }

        th {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: 600;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .thumb-photo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Absensi Smart GPS</h1>
                    <p>Konfirmasi kehadiran Anda dengan validasi wajah dan lokasi real-time.</p>
                </div>
                <div class="header-right">
                    <div id="real-time-clock" style="font-size: 1.2rem; font-weight: 700; color: var(--primary-color);"></div>
                </div>
            </header>

            <div class="absensi-container">
                <!-- Camera Unit -->
                <div class="camera-box">
                    <video id="video" autoplay muted playsinline></video>
                    <canvas id="canvas"></canvas>
                    <div class="capture-overlay"></div>
                    <div id="camera-loading" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);">
                        <div class="loading-spinner" style="display:block; width:40px; height:40px;"></div>
                        <p style="margin-top:10px;">Mengaktifkan Kamera...</p>
                    </div>
                </div>

                <!-- Info & Actions -->
                <div class="status-card">
                    <h3>Detail Info Presensi</h3>
                    <div class="info-item">
                        <i data-lucide="user"></i>
                        <div>
                            <small style="display:block; opacity:0.6;">Nama Karyawan</small>
                            <strong><?= $_SESSION['nama'] ?></strong>
                        </div>
                    </div>
                    <div class="info-item">
                        <i data-lucide="map-pin"></i>
                        <div>
                            <small style="display:block; opacity:0.6;">Lokasi Anda</small>
                            <div id="location-name">Mendeteksi lokasi...</div>
                            <div id="location-status" class="text-warning">Mengecek Geofence...</div>
                        </div>
                    </div>
                    <div class="info-item">
                        <i data-lucide="calendar"></i>
                        <div>
                            <small style="display:block; opacity:0.6;">Tanggal Hari Ini</small>
                            <strong><?= date('d F Y') ?></strong>
                        </div>
                    </div>

                    <div class="absen-actions">
                        <button class="btn-absen btn-masuk" onclick="submitAbsen('masuk')" id="btn-masuk">
                            <i data-lucide="log-in"></i> Masuk
                        </button>
                        <button class="btn-absen btn-keluar" onclick="submitAbsen('keluar')" id="btn-keluar">
                            <i data-lucide="log-out"></i> Pulang
                        </button>
                        <button class="btn-absen btn-istirahat" onclick="submitAbsen('istirahat_masuk')" id="btn-istirahat">
                            <i data-lucide="coffee"></i> Istirahat
                        </button>
                        <button class="btn-absen btn-kembali" onclick="submitAbsen('istirahat_keluar')" id="btn-kembali">
                            <i data-lucide="refresh-cw"></i> Kembali
                        </button>
                    </div>
                </div>
            </div>

            <!-- History log -->
            <div class="log-table-container">
                <h3>Riwayat Absensi Hari Ini</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Tipe</th>
                                <th>Foto</th>
                                <th>Lokasi (GPS)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="history-log">
                            <!-- Data populated via AJAX -->
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 40px; opacity:0.5;">
                                    <i data-lucide="loader-2" class="loading-spinner" style="display:inline-block; margin-bottom:10px;"></i>
                                    <p>Memuat riwayat...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        // Clock
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            $('#real-time-clock').text(time);
        }
        setInterval(updateClock, 1000);
        updateClock();

        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        let currentLat = null;
        let currentLng = null;
        let isInsideArea = false;

        // Start Camera
        navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
        .then(stream => {
            video.srcObject = stream;
            $('#camera-loading').hide();
        })
        .catch(err => {
            console.error("Camera Error:", err);
            alert("Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan.");
        });

        // Get Location
        function updateLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.watchPosition(pos => {
                    currentLat = pos.coords.latitude;
                    currentLng = pos.coords.longitude;
                    $('#location-name').text(currentLat.toFixed(6) + ', ' + currentLng.toFixed(6));
                    checkGeofence(currentLat, currentLng);
                }, err => {
                    $('#location-status').text('Gagal mendapatkan lokasi: ' + err.message).addClass('text-danger');
                }, { enableHighAccuracy: true });
            }
        }
        updateLocation();

        function checkGeofence(lat, lng) {
            // Kita kirim ke server untuk validasi jarak dari titik kantor
            $.ajax({
                url: 'absen_proses.php?action=check_geofence',
                method: 'POST',
                data: JSON.stringify({ latitude: lat, longitude: lng }),
                contentType: 'application/json',
                success: function(res) {
                    if (res.success) {
                        $('#location-status').text('OK - Di dalam area absensi (' + res.distance + ' m)').removeClass('text-danger text-warning').addClass('text-success');
                        isInsideArea = true;
                    } else {
                        $('#location-status').text('Di luar radius! (' + res.distance + ' m)').removeClass('text-success text-warning').addClass('text-danger');
                        isInsideArea = false;
                    }
                }
            });
        }

        function captureImage() {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            // Draw mirrored image
            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.8);
        }

        function submitAbsen(type) {
            if (!isInsideArea) {
                alert("Anda berada di luar radius area kantor. Silahkan mendekat ke koordinat kantor untuk absen.");
                return;
            }

            const image = captureImage();
            const btn = $('#btn-' + type);
            btn.prop('disabled', true).html('<div class="loading-spinner" style="display:inline-block;"></div> Memproses...');

            $.ajax({
                url: 'absen_proses.php?action=submit',
                method: 'POST',
                data: JSON.stringify({ 
                    user_id: <?= $user_id ?>, 
                    type: type, 
                    image: image, 
                    latitude: currentLat, 
                    longitude: currentLng 
                }),
                contentType: 'application/json',
                success: function(res) {
                    if (res.success) {
                        alert("Absensi " + type + " BERHASIL dicatat!");
                        loadHistory();
                    } else {
                        alert("Gagal: " + res.message);
                    }
                },
                error: function() {
                    alert("Terjadi kesalahan sistem.");
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i data-lucide="' + (type === 'masuk' ? 'log-in' : type === 'keluar' ? 'log-out' : 'coffee') + '"></i> ' + type.charAt(0).toUpperCase() + type.slice(1));
                    lucide.createIcons();
                }
            });
        }

        function loadHistory() {
            $.get('absen_proses.php?action=history', function(data) {
                let html = '';
                if(data.length > 0) {
                    data.forEach(row => {
                        html += `
                            <tr>
                                <td>${row.jam}</td>
                                <td><span class="badge ${getStatusBadge(row.status)}">${row.status.toUpperCase()}</span></td>
                                <td><img src="absen_foto/${row.foto}" class="thumb-photo" onclick="window.open(this.src)"></td>
                                <td>${row.latitude}, ${row.longitude}</td>
                                <td class="text-success">Verified</td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="5" style="text-align:center; opacity:0.5;">Bel_um ada riwayat absensi hari ini</td></tr>';
                }
                $('#history-log').html(html);
            });
        }

        function getStatusBadge(status) {
            switch(status) {
                case 'masuk': return 'bg-success';
                case 'keluar': return 'bg-danger';
                default: return 'bg-warning';
            }
        }

        $(document).ready(loadHistory);
    </script>
</body>
</html>
