# Analisis Menyeluruh Proyek FixPoint - Smart Office Management System

## 1. Ikhtisar Sistem
FixPoint adalah sistem ERP (Enterprise Resource Planning) terintegrasi yang dirancang khusus untuk manajemen kantor cerdas, dengan fitur tambahan yang dioptimalkan untuk instansi kesehatan (Rumah Sakit).

## 2. Struktur Direktori & Arsitektur
*   **Root Folder**: Berisi entry point `index.php` dan file administrasi proyek.
*   **`dist/`**: Inti aplikasi. Berisi seluruh modul PHP, aset (CSS/JS), dan logika bisnis. Menggunakan template **Stisla Admin**.
*   **`sources/`**: Kemungkinan file sumber orisinal atau komponen mentah.
*   **`vendor/`**: Dependency PHP via Composer (PHPMailer, mPDF, dll).
*   **`phpqrcode/`**: Pustaka khusus untuk integrasi QR Code.

## 3. Teknologi Utama
*   **Bahasa**: PHP 7.x/8.x (MySQLi Extension).
*   **Database**: MySQL (Host: `localhost`, DB: `fx1`).
*   **Keamanan**: `password_verify()` untuk login, sistem integritas file (`check_integrity.php`), dan skema lisensi.
*   **Integrasi Pihak Ketiga**:
    *   **WhatsApp**: Fonnte API / Watzap untuk notifikasi otomatis.
    *   **Telegram**: Telegram Bot API.
    *   **Email**: SMTP Gmail via PHPMailer.
*   **Reporting**: mPDF, FPDF, dan DomPDF untuk cetak dokumen legal dan laporan.

## 4. Modul Unggulan
1.  **IT Helpdesk**: Sistem tiket (Hardware/Software) lengkap dengan pelacakan *Handling Time*.
2.  **Manajemen SDM (HRD)**: Cuti online, perizinan keluar gerbang (WA Notify), dan perhitungan gaji (Payroll).
3.  **Kesekretariatan**: Manajemen surat masuk/keluar serta agenda Direksi yang terintegrasi pengingat WA.
4.  **Tanda Tangan Elektronik (TTE)**: Modul verifikasi dan pembubuhan tanda tangan digital berbasis QR Code/Hash.
5.  **Indikator Mutu & Akreditasi**: Khusus RS untuk pemantauan kualitas pelayanan harian dan manajemen dokumen pokja.

## 5. Strategi Implementasi & Kustomisasi (Lesson Learned)
*   **Pathing Strategy**: Aplikasi ini sangat bergantung pada struktur folder `dist/`. Perubahan jalur harus konsisten di `koneksi.php` dan file navigasi (`sidebar.php`).
*   **Activity Tracking**: Menggunakan kolom `last_activity` pada tabel users (mirip dengan implementasi BexMedia) untuk monitoring ketersediaan admin.
*   **Notification Engine**: Menggunakan file helper terpisah (`send_wa.php`, `kirim_email_gaji.php`) memudahkan kustomisasi provider notifikasi di masa depan.

## 6. Rekomendasi untuk Proyek Berikutnya
*   **Unified Encryption**: Pindah ke `AES_ENCRYPT` seperti BexMedia untuk data sensitif (NIK/Gaji) jika standar keamanan ditingkatkan.
*   **Modularization**: Memisahkan logika database ke `conf/config.php` seperti pola BexMedia untuk memudahkan maintenance multi-server (Hybrid).
*   **UI/UX Modernization**: Mengadopsi Glassmorphism dari BexMedia ke dalam FixPoint untuk tampilan yang lebih premium.

---
*Dianalisis oleh: Antigravity AI*
*Tanggal: 20 Februari 2026*
