<?php
/**
 * ajax_get_employees_by_unit.php - Mengambil daftar pegawai berdasarkan unit kerja
 */
include 'security.php';

$unit = trim($_POST['unit'] ?? '');

if (empty($unit)) {
    echo '<div style="text-align:center; color:#94A3B8; font-size:12px; padding:10px;">Pilih unit terlebih dahulu.</div>';
    exit;
}

$unitEsc = mysqli_real_escape_string($conn, $unit);
$res = mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE unit_kerja = '$unitEsc' ORDER BY nama_lengkap ASC");

if (mysqli_num_rows($res) === 0) {
    echo '<div style="grid-column: span 2; text-align:center; color:#94A3B8; font-size:12px; padding:10px;">Tidak ada pegawai di unit ini.</div>';
    exit;
}

echo '
<table class="matrix-table" style="width:100%; border-collapse:collapse; font-size:0.8rem; background:white;">
    <thead>
        <tr style="background:#F1F5F9; border-bottom:2px solid #E2E8F0;">
            <th style="padding:10px; text-align:center; width:30px;"><input type="checkbox" id="toggleAllRows" onclick="toggleMatrixAll(this)"></th>
            <th style="padding:10px; text-align:left;">Nama Pegawai</th>
            <th style="padding:10px; text-align:center; border-left:1px solid #E2E8F0;">P1 <input type="checkbox" onclick="toggleMatrixCol(1, this)"></th>
            <th style="padding:10px; text-align:center; border-left:1px solid #E2E8F0;">P2 <input type="checkbox" onclick="toggleMatrixCol(2, this)"></th>
            <th style="padding:10px; text-align:center; border-left:1px solid #E2E8F0;">P3 <input type="checkbox" onclick="toggleMatrixCol(3, this)"></th>
            <th style="padding:10px; text-align:center; border-left:1px solid #E2E8F0;">P4 <input type="checkbox" onclick="toggleMatrixCol(4, this)"></th>
            <th style="padding:10px; text-align:center; border-left:1px solid #E2E8F0;">P5 <input type="checkbox" onclick="toggleMatrixCol(5, this)"></th>
        </tr>
    </thead>
    <tbody>';

while ($u = mysqli_fetch_assoc($res)) {
    $uid = $u['id'];
    echo '
    <tr style="border-bottom:1px solid #F1F5F9;">
        <td style="padding:8px; text-align:center;"><input type="checkbox" class="row-trigger" data-uid="'.$uid.'" onclick="toggleMatrixRow('.$uid.', this)"></td>
        <td style="padding:8px; font-weight:500; color:#334155;">'. h($u['nama_lengkap']) .'</td>';
        for($w=1; $w<=5; $w++) {
            echo '<td style="padding:8px; text-align:center; border-left:1px solid #F1F5F9;">
                    <input type="checkbox" class="gen-exclude matrix-check" 
                           data-uid="'.$uid.'" 
                           data-week="'.$w.'" 
                           value="'.$uid.'">
                  </td>';
        }
    echo '</tr>';
}

echo '</tbody></table>';
