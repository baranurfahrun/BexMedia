# ❄️ BexMedia - Development Notes (v2.0 Hybrid Security Edition)

## 📋 Project Overview
**BexMedia** adalah platform aplikasi dashboard "Double Engine" yang terintegrasi dengan SIMRS Khanza, dirancang dengan keamanan tingkat tinggi (Audit Approved) dan estetika premium Swiss Minimalist.

## 🛡️ Security Status: 100/100 (APPROVED)
Berdasarkan audit internal (16 Feb 2026), aplikasi telah menerapkan:
1.  **Anti-SQL Injection:** 100% menggunakan *Prepared Statements* via `safe_query()`.
2.  **Hybrid Authentication:** Login ganda menggunakan database internal BexMedia & database Master Khanza (Remote).
3.  **Audit Logging:** Pencatatan jejak aktivitas user secara real-time di tabel `web_dokter_audit_log`.
4.  **Security Headers:** Implementasi CSP, HSTS, XSS Protection, dan SameOrigin Policy.
5.  **Brute Force Protection:** Rate limiting (Max 2 percobaan) dengan IP Lockout otomatis.

## ⚙️ Hybrid Server Architecture
Aplikasi ini berjalan di atas dua sumber data yang berbeda secara fisik:
-   **Local Server (BexMedia DB):** Menyimpan User Internal, Audit Logs, dan System Settings.
-   **Remote Server (Khanza/SIK DB):** Menarik data master operasional SIMRS (IP Default: `192.20.20.253`).
-   **Auto-Bootstrap:** Sistem koneksi dinamis yang membaca IP dan Credentials langsung dari tabel `web_settings` (Dapat diubah via UI).

## 🎨 Design System: "Crystal Ice"
-   **Theme:** Swiss Minimalist v2.
-   **Branding:** Nama aplikasi bersifat dinamis (diatur via menu Settings).
-   **UI Structure:** Sidebar dengan menu operasional dan akses cepat pengaturan sistem di bagian bawah.

## 📂 Core Components
- `conf/config.php`: Engine utama (Koneksi Ganda, Keamanan, Helper Utility).
- `login.php`: Gerbang autentikasi hybrid dengan deteksi sumber (Internal vs Khanza).
- `index.php`: Dashboard High-Fidelity dengan data terintegrasi.
- `settings.php`: Control Panel untuk mengatur IP Server dan Parameter Aplikasi.
- `css/index.css`: Komprehensif Desain Sistem & Variabel "Arctic".

## 🚀 Roadmap Status
1.  ✅ **Integrasi Data Ganda:** Arsitektur multi-server selesai.
2.  ✅ **Hybrid Login:** Berhasil mengoneksikan akun Khanza ke dashboard.
3.  ✅ **Security Hardening:** Lulus audit keamanan dasar.
4.  ⏳ **Module Build:** Pengembangan halaman detail Transaksi & Media Assets.
5.  ⏳ **Reporting:** Automasi laporan data dari database Khanza ke UI BexMedia.

---
*Last Updated: 19 Februari 2026 - Antigravity Dev Team*
