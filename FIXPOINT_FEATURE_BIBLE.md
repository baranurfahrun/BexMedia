# 📔 FixPoint: Comprehensive Feature Library & Bible

Dokumen ini berisi daftar lengkap seluruh fitur, modul, dan logika sistem yang ditemukan dalam proyek **FixPoint**. Gunakan ini sebagai referensi untuk porting fitur ke **BexMedia** atau projek masa depan.

---

## 1. 📂 Core & Architectures
Fitur dasar yang menggerakkan seluruh sistem.

| Fitur | Deskripsi | File Kunci |
| :--- | :--- | :--- |
| **RBAC (Role Based Access Control)** | Izin akses per-user berdasarkan menu yang terdaftar di database. | `sidebar.php`, `hak_akses.php` |
| **Integrity Guard** | Verifikasi SHA1 file `sidebar.php` untuk mencegah modifikasi hak cipta/sidebar tidak sah. | `check_integrity.php` |
| **Dynamic Menu Sync** | Pendaftaran menu otomatis (mirip `syncMenus` di BexMedia). | `hak_akses.php` |
| **Malware Scanner** | Scan file PHP dari fungsi berbahaya (`eval`, `exec`, `base64_decode`). | `scan_malware.php` |
| **Attribution Protection** | Proteksi tombol "Tentang Aplikasi" yang wajib ada di setiap akun. | `sidebar.php` |

---

## 2. 🔐 Tanda Tangan Elektronik (TTE)
Sistem tanda tangan digital mandiri (Non-Sertifikasi) untuk dokumen internal.

| Fitur | Deskripsi | File Kunci |
| :--- | :--- | :--- |
| **TTE Generator** | Membuat token TTE unik untuk setiap user. | `buat_tte.php` |
| **PDF Signer** | Membubuhkan QR Code TTE ke file PDF dengan metode *Drag & Drop*. | `bubuhkan_tte.php` |
| **Word Signer** | Membubuhkan TTE ke file `.docx` menggunakan `PhpWord`. | `bubuhkan_tte.php` |
| **TTE Verification** | Memverifikasi keabsahan dokumen berdasarkan token QR dan hash file database. | `cek_tte.php` |
| **Hash Verification** | Membandingkan SHA256 file fisik dengan database untuk mendeteksi manipulasi. | `tte_hash_helper.php` |

---

## 3. 🛠️ IT Departemen & Sarpras
Modul operasional teknis dan pemeliharaan aset.

| Fitur | Deskripsi | File Kunci |
| :--- | :--- | :--- |
| **Ticketing System** | Pengajuan perbaikan software/hardware/sarpras. | `order_tiket_*.php` |
| **Handling Time** | Laporan durasi pelayanan IT (dari tiket dibuat sampai selesai). | `handling_time.php` |
| **Asset Management** | Inventarisasi barang IT dan AC (Sarpras). | `data_barang_*.php` |
| **Maintenance Rutin** | Jadwal dan checklist perawatan rutin perangkat. | `maintenance_rutin.php` |
| **Bridging Connection** | Monitoring status koneksi API/Database ke sistem luar. | `koneksi_bridging.php` |
| **Berita Acara** | Generate otomatis dokumen serah terima atau pemeriksaan barang. | `berita_acara_it.php` |

---

## 4. 🏥 Laporan SIMRS (PHE)
Pemantauan indikator kinerja rumah sakit (khusus integrasi BPJS/SatuSehat).

| Fitur | Deskripsi | File Kunci |
| :--- | :--- | :--- |
| **Antrian JKN** | % Pemanfaatan antrian via Mobile JKN. | `mjkn_antrian.php` |
| **All Antrian** | % Total antrian online vs offline (SEP BPJS). | `semua_antrian.php` |
| **E-RM Statistics** | Monitoring kelengkapan pengisian Rekam Medis Elektronik. | `erm.php` |
| **Satu Sehat Monitor** | Tracking pengiriman data ke platform SatuSehat Kemenkes. | `satu_sehat.php` |
| **Dashboard Grafik** | Visualisasi tren capaian bulanan menggunakan `Chart.js`. | `semua_antrian.php` |

---

## 5. 💰 Keuangan & Payroll
Manajemen gaji dan administrasi tunjangan karyawan.

| Fitur | Deskripsi | File Kunci |
| :--- | :--- | :--- |
| **Slip Gaji Digital** | Generate slip gaji PDF dan kirim otomatis via Email. | `cetak_gaji.php`, `kirim_email_gaji.php` |
| **Tunjangan Dinamis** | Manajemen master Masa Kerja, Struktural, dan Fungsional. | `masa_kerja.php`, `fungsional.php` |
| **Potongan Otomatis** | Perhitungan BPJS Kesehatan, Ketenagakerjaan (JHT/JP), dan PPH21. | `potongan_bpjs_*.php` |

---

## 6. 📅 HRD & Manajemen SDM
Administrasi kepegawaian dan absensi.

| Fitur | Deskripsi | File Kunci |
| :--- | :--- | :--- |
| **Cuti Online** | Pengajuan cuti dengan verifikasi bertingkat (Atasan -> HRD). | `pengajuan_cuti.php`, `jatah_cuti.php` |
| **Exit Clearance** | Checklist pengembalian inventaris bagi karyawan yang keluar. | `exit_clearance.php` |
| **Izin Keluar Perusahaan** | Form izin jangka pendek dengan notifikasi WA ke Atasan. | `izin_keluar.php` |
| **Rekap Absensi** | Penarikan data absensi dan jadwal dinas. | `absensi.php`, `jadwal_dinas.php` |

---

## 7. ✉️ Kesekretariatan & Agenda
Manajemen surat menyurat dan jadwal pimpinan.

| Fitur | Deskripsi | File Kunci |
| :--- | :--- | :--- |
| **Surat Masuk/Keluar** | Penomoran otomatis dan arsip digital (PDF scan). | `surat_masuk.php`, `surat_keluar.php` |
| **Disposisi Digital** | Lembar disposisi surat yang bisa ditandatangani via TTE. | `disposisi.php` |
| **Agenda Direktur** | Kalender kegiatan pimpinan dengan notifikasi harian ke pimpinan. | `agenda_direktur.php` |

---

## 🤖 Notification Engine (Helper)
Logika pengiriman notifikasi yang digunakan oleh berbagai modul.

| Nama File | Fungsi |
| :--- | :--- |
| `send_wa.php` | Kirim pesan personal via API Fonnte/Watzap. |
| `send_wa_grup.php` | Kirim update/tiket ke grup departemen tertentu. |
| `kirim_email_gaji.php` | Integrasi PHPMailer untuk broadcast slip gaji. |

---
**Catatan Penting**: Sebagian besar template UI menggunakan **Stisla**. Untuk porting ke BexMedia, logic PHP dapat diambil langsung namun styling CSS/HTML harus disesuaikan dengan **Swiss/Crystal Ice UI** milik BexMedia.

## 🗺️ Full Menu Map (Direct Catalog)
Berikut adalah daftar urut seluruh menu yang ada di Sidebar FixPoint untuk referensi pemetaan file.

### 1. DASHBOARD
-   `dashboard.php`: Dashboard Utama
-   `dashboard2.php`: Dashboard Direktur

### 2. PENGAJUAN / ORDER
-   `order_tiket_it_software.php`: Tiket IT Software
-   `order_tiket_it_hardware.php`: Tiket IT Hardware
-   `order_tiket_sarpras.php`: Tiket Sarpras
-   `off_duty.php`: Off-Duty
-   `izin_keluar.php`: Izin Keluar
-   `pengajuan_cuti.php`: Pengajuan Cuti
-   `ganti_jadwal_dinas.php`: Ganti Jadwal
-   `edit_data_simrs.php`: Edit Data SIMRS
-   `hapus_data.php`: Hapus Data SIMRS

### 3. DATA PENGAJUAN
-   `data_tiket_it_software.php`: Data Tiket IT Soft
-   `data_tiket_it_hardware.php`: Data Tiket IT Hard
-   `data_tiket_sarpras.php`: Data Tiket Sarpras
-   `data_off_duty.php`: Data Off-Duty
-   `acc_edit_data.php`: Permintaan Edit
-   `data_permintaan_hapus_data_simrs.php`: Hapus Data
-   `data_cuti_delegasi.php`: ACC Cuti Delegasi
-   `data_cuti_atasan.php`: ACC Cuti Atasan
-   `data_cuti_hrd.php`: ACC Cuti HRD
-   `acc_keluar_atasan.php`: ACC Keluar Atasan
-   `acc_keluar_sdm.php`: ACC Keluar SDM

### 4. TANDA TANGAN ELEKTRONIK (TTE)
-   `cek_tte.php`: Cek TTE
-   `bubuhkan_tte.php`: TTE Dokumen
-   `dokumen_tte.php`: Dokumen Saya
-   `dokumen_tte_semua.php`: Semua Dok. TTE
-   `buat_tte.php`: TTE Generate

### 5. IT DEPARTEMEN
-   `data_permintaan_hapus_simrs.php`: Data Hapus SIMRS
-   `data_permintaan_edit_simrs.php`: Data Edit SIMRS
-   `handling_time.php`: Handling Time
-   `spo_it.php`: SPO IT
-   `input_spo_it.php`: Input SPO IT
-   `berita_acara_it.php`: Berita Acara
-   `data_barang_it.php`: Data Barang IT
-   `maintenance_rutin.php`: Maintenance Rutin
-   `koneksi_bridging.php`: Koneksi Bridging
-   `log_login.php`: Log Login

### 6. SARPRAS
-   `handling_time_sarpras.php`: Handling Time
-   `data_barang_ac.php`: Barang Sarpras
-   `maintanance_rutin_sarpras.php`: Maintenance

### 7. KEUANGAN
-   `input_gaji.php`: Transaksi Gaji
-   `data_gaji.php`: Data Gaji
-   `masa_kerja.php`, `kesehatan.php`, `fungsional.php`, `struktural.php`, `gaji_pokok.php`: Penerimaan
-   `potongan_bpjs_*.php`, `potongan_dana_sosial.php`, `pph21.php`: Potongan

### 8. HR / SDM
-   `data_cuti.php`, `rekap_catatan_kerja.php`, `master_cuti.php`, `jatah_cuti.php`: Manajemen Cuti/Kerja
-   `data_karyawan.php`: Data Karyawan
-   `exit_clearance.php`: Exit Clearance

### 9. KESEKTARIATAN
-   `surat_masuk.php`, `surat_keluar.php`: Manajemen Surat
-   `arsip_digital.php`: Arsip Digital
-   `agenda_direktur.php`, `lihat_agenda.php`: Agenda Direksi
-   `kategori_arsip.php`: Kategori Arsip

### 10. LAPORAN KERJA
-   `catatan_kerja.php`: Catatan Kerja Karyawan
-   `laporan_harian.php`, `laporan_bulanan.php`, `laporan_tahunan.php`: Laporan Periodik
-   `input_kpi.php`, `master_kpi.php`: Key Performance Indicators

### 11. AKREDITASI
-   `data_dokumen.php`, `input_dokumen.php`, `master_pokja.php`: Manajemen Dokumen Akreditasi

### 12. KOMITE KEPERAWATAN (KOMKEP)
-   `praktek.php`, `wawancara.php`, `ujian_tertulis.php`: Kredensial
-   `hasil_*.php`: Rekap Hasil Kredensial
-   `kegiatan_komite.php`, `laporan_komite.php`: Aktivitas Komkep
-   `judul_soal.php`, `input_soal.php`, `data_anggota_komite.php`: Master Komkep

### 13. INDIKATOR MUTU
-   `master_indikator*.php`: Master IMN / IMUT RS / IMUT Unit
-   `input_harian.php`: Input Harian
-   `capaian_imut.php`: Capaian Mutu

### 14. LAPORAN SIMRS
-   `semua_antrian.php`, `mjkn_antrian.php`, `poli_antrian.php`: Monitoring Antrian Online
-   `erm.php`: Statistik E-RM
-   `satu_sehat.php`: Integrasi SatuSehat
-   `progres_kerja.php`, `slide_pelaporan.php`: Reporting Slide

### 15. JADWAL & ABSENSI
-   `absensi.php`, `data_absensi.php`: Absensi Foto/QR
-   `jadwal_dinas.php`: Input Jadwal
-   `jam_kerja.php`: Master Jam Kerja

### 16. MASTER DATA & SETTING
-   `perusahaan.php`, `pengguna.php`, `hak_akses.php`: Core Admin
-   `mail_setting.php`, `tele_setting.php`, `wa_setting.php`: Notif Configuration
-   `unit_kerja.php`, `jabatan.php`, `master_poliklinik.php`: Organizational Master
-   `profile.php`, `profile2.php`: User Account Settings

---

## 🏛️ BexMedia Implementation: Structured Sidebar Navigation
Strategi pengelompokan menu yang diterapkan di **BexMedia** untuk menjaga estetika *Swiss Minimalist* dan efisiensi navigasi.

### 1. 📊 INSIGHT & ANALYTICS (Dropdown)
Ini adalah "Otak" aplikasi yang berisi semua laporan data.
- **Dashboard Utama** (`index.php`)
- **Monitoring Antrian** (Online & Offline)
- **Statistik E-RM** (Rekam Medis)
- **Satu Sehat Tracker**
- **Slide Laporan Direksi**

### 2. 🛠️ TECHNICAL SUPPORT (Dropdown)
Berisi urusan perbaikan dan pemeliharaan.
- **IT Support** (Software & Hardware)
- **Sarpras Support** (Fisik & Alat Kesehatan)
- **Maintenance Rutin** (Cek berkala)
- **Handling Time Analysis** (Performa Teknisi)
- **Berita Acara** (Dokumen Kerusakan)

### 3. 👥 EMPLOYEE HUB (Dropdown)
Berisi semua fitur yang berhubungan dengan karyawan.
- **Data Karyawan** (Database SDM)
- **Absensi & Jadwal Dinas** (Hadir & Shift)
- **Cuti & Izin Keluar** (Administrasi Mandiri)
- **Payroll & Slip Gaji** (Keuangan Karyawan)
- **Evaluasi KPI** (Kinerja)

### 4. 📑 DIGITAL ARCHIVE (Dropdown)
Pusat dokumen dan surat-menyurat elektronik.
- **Tanda Tangan Elektronik (TTE)** (Fitur Unggulan)
- **Surat Masuk & Keluar** (Korespondensi)
- **Arsip Digital & Agenda** (Penyimpanan File)
- **Dokumen Akreditasi** (Khusus Persiapan Audit)

### 5. 🛡️ QUALITY ASSURANCE (Dropdown)
Fokus pada standar mutu dan kredensial tenaga medis.
- **Indikator Mutu** (IMN, RS, Unit)
- **Komite Keperawatan** (Kredensial & Wawancara)
- **SPO Digital** (Standard Prosedur)

### 6. ⚙️ SYSTEM CENTER (Dropdown)
Semua konfigurasi sistem yang hanya bisa diakses Super Admin.
- **Pengaturan Perusahaan**
- **User & Hak Akses** (RBAC)
- **Integrasi Notifikasi** (WA, Telegram, Mail)
- **Log System** (Audit Log & Login History)
