# ❄️ BexMedia - Development Notes (v2.0 Hybrid Security Edition)

## 📋 Project Overview
**BexMedia** adalah platform aplikasi dashboard "Double Engine" yang terintegrasi dengan SIMRS Khanza, dirancang dengan keamanan tingkat tinggi (Audit Approved) dan estetika premium Swiss Minimalist.

## 🛡️ Security Status: 100/100 (APPROVED)
Berdasarkan audit internal terbaru:
1.  **AES Data Protection:** Username dan Password menggunakan enkripsi AES-256 (Internal BexMedia).
2.  **Anti-SQL Injection:** 100% menggunakan *Prepared Statements* via `safe_query()`.
3.  **Audit Logging:** Pencatatan jejak aktivitas user secara real-time di tabel `web_dokter_audit_log`.
4.  **Activity Tracking:** Pemantauan status online/offline user secara real-time melalui kolom `last_activity`.
5.  **Brute Force Protection:** Rate limiting (Max 2 percobaan) dengan IP Lockout otomatis.
6.  **CSRF & XSS Protection:** Implementasi token CSRF pada setiap form dan sanitasi output (H-function).

## 📂 Directory Structure (New)
Aplikasi telah dirapikan ke struktur yang lebih modular:
-   `dist/`: Berisi file operasional utama (`index.php`, `settings.php`, `clients.php`, `logout.php`).
-   `tools/`: Script maintenance, debug, dan migrasi.
-   `sql/`: Penyimpanan skema dan backup database.
-   `conf/`, `css/`, `images/`, `js/`: Folder resource global.

## ⚙️ Core Modules & Status
1.  **Management Akun (100% - STABLE)**
    - ✅ **Daftar Akun:** Menampilkan status online/offline user.
    - ✅ **Konfirmasi Akun:** Aktivasi user baru (status pending).
    - ✅ **Hak Akses:** Sistem Role-Based Access Control (RBAC) dengan sinkronisasi menu otomatis (`syncMenus`).
2.  **Client Management (40% - BETA)** (NOT FIX)
    - ✅ **UI Design:** Kartu klien premium dengan status pill.
    - ✅ **Database:** Skema tabel `clients` & initial data seeding.
    - ⏳ **CRUD Ops:** Fitur Tambah, Edit, dan Hapus klien (Pending).
3.  **Dashboard (80% - STABLE)**
    - ✅ **Hybrid Engine:** Penarikan data dari Local & Remote (Khanza).
    - ✅ **Crystal Ice UI:** Desain responsif untuk monitor monitor besar.

## 🚀 Roadmap (Backlog - NOT FIX)
- [x] **Implementasi Hak Akses (RBAC):** Selesai diintegrasikan dengan BexMedia.
- [ ] **Client CRUD:** Menyelelesaikan fitur pengelolaan instansi/rekanan secara penuh.
- [ ] **Module Report:** Otomatisasi laporan data opeasional untuk dikirim ke Telegram/Email.
- [ ] **Campaign & Analytics:** Mengaktifkan dashboard interaktif untuk monitoring performa.

## 🏛️ Legacy Analysis (FixPoint)
Hasil analisis sistem **FixPoint** (v1.0 Source) untuk referensi pengembangan:
- **Architecture:** Menggunakan pola *Direct Scripting* dengan integrasi Stisla Template. Sangat kuat di sisi notifikasi (WA Fonnte & Telegram Bot).
- **Key Logic:** Penggunaan `password_verify` untuk login dan integrasi `PHPMailer` untuk slip gaji otomatis.
- **TTE Integration:** Menggunakan hashing QR Code dan hash fisik file untuk verifikasi dokumen legal (Sangat disarankan diadaptasi ke BexMedia Report).
- **Lesson Learned:** Pemisahan folder `dist` membantu kebersihan `root`, namun memerlukan pengecekan `SCRIPT_FILENAME` yang akurat untuk redirect login (Sudah diterapkan di BexMedia v2.0).

## 💡 Aturan Pengembangan (Dev Rules)
Setiap pembuatan menu/file baru di dalam folder `dist` wajib menyertakan pengecekan hak akses di bagian paling atas file (setelah koneksi). Gunakan logika verifikasi berdasarkan `user_id` dan `basename(__FILE__)` terhadap tabel `akses_menu` untuk memastikan keamanan akses halaman per user.

---
*Last Updated: 20 Februari 2026 - Antigravity Dev Team (Bara N. Fahrun)*
