# 📊 USULAN RESTRUKTURISASI SIDEBAR BEXMEDIA (ALA FIXPOINT)

Catatan ini dibuat untuk merapikan sidebar BexMedia agar menu berada pada "poin" yang benar, memisahkan antara **INPUT (Pelayanan)**, **DATA (Approval/Monitoring)**, dan **MASTER DATA**.

---

## 🏗️ STRUKTUR BARU SIDEBAR

### **1. 📊 STRATEGIC DATA & ANALYTICS**
*Fokus: Visualisasi data real-time untuk mendukung pengambilan keputusan strategis.*
- **Dashboard Utama** (`index.php`)
- **Dashboard Direktur** (`dashboard2.php`)
- **Antrian Online All** (`semua_antrian.php`)
- **Monitoring Mobile JKN** (`mjkn_antrian.php`)
- **Antrian Per Poli** (`poli_antrian.php`)
- **Statistik E-RM** (`erm.php`)
- **Satu Sehat Monitor** (`satu_sehat.php`)
- **Progres Kerja SIMRS** (`progres_kerja.php`)
- **Slide Laporan Direksi** (`slide_pelaporan.php`)

### **2. 📝 OPERATIONAL SERVICES (INPUT)**
*Fokus: Gerbang pelayanan staf. Berisi seluruh formulir pengajuan dan pelaporan aktivitas.*
- **Request IT (Software)** (`order_tiket_it_software.php`)
- **Request IT (Hardware)** (`order_tiket_it_hardware.php`)
- **Request Sarpras** (`order_tiket_sarpras.php`)
- **Pengajuan Cuti** (`pengajuan_cuti.php`)
- **Izin Keluar Kantor** (`izin_keluar.php`)
- **Izin Pulang Cepat** (`izin_pulang_cepat.php`)
- **Ganti Jadwal Dinas** (`ganti_jadwal_dinas.php`)
- **Off-Duty Request** (`off_duty.php`)
- **Lembur Request** (`lembur.php`)
- **Request Edit SIMRS** (`edit_data_simrs.php`)
- **Request Hapus SIMRS** (`hapus_data.php`)
- **Logbook Kerja Harian** (`catatan_kerja.php`)

### **3. 🕹️ COMMAND CENTER (APPROVAL)**
*Fokus: Pusat kendali untuk Admin, Atasan, dan Manajemen untuk melakukan verifikasi dan ACC.*
- **Data Tiket IT/Sarpras** (`data_tiket_it_hardware.php`, `data_tiket_it_software.php`, `data_tiket_sarpras.php`)
- **Handling Time Service** (`handling_time.php`)
- **Data Off-Duty** (`data_off_duty.php`)
- **ACC Cuti (Atasan/HRD/Delegasi)** (`data_cuti_atasan.php`, `data_cuti_hrd.php`, `data_cuti_delegasi.php`)
- **ACC Izin (Atasan/SDM)** (`acc_keluar_atasan.php`, `acc_keluar_sdm.php`)
- **ACC Lembur (Atasan/SDM)** (`acc_lembur_atasan.php`, `acc_lembur_sdm.php`)
- **ACC Edit/Hapus SIMRS** (`acc_edit_data.php`, `data_permintaan_edit_simrs.php`, `data_permintaan_hapus_simrs.php`)

### **4. 📁 DIGITAL GOVERNANCE & TTE**
*Fokus: Standarisasi dokumen digital, tanda tangan elektronik, dan arsip legalitas.*
- **Signature (TTE)** (`bubuhkan_tte.php`, `cek_tte.php`)
- **Stempel Digital** (`bubuhkan_stampel.php`, `cek_stampel.php`)
- **Surat Masuk & Disposisi** (`surat_masuk.php`, `disposisi.php`)
- **Surat Keluar** (`surat_keluar.php`)
- **E-SPO Digital** (`spo.php`)
- **Notulen Rapat Bulanan** (`rapat_bulanan.php`)
- **Surat Edaran & Pemberitahuan** (`surat_edaran.php`, `pemberitahuan.php`)
- **Brankas Digital (Arsip)** (`arsip_digital.php`)

### **5. 📦 MASTER REPOSITORY & ASSETS**
*Fokus: Gudang data master, inventarisasi aset fisik/digital, dan database SDM.*
- **Aset IT Departemen** (`data_barang_it.php`)
- **Aset Sarpras (AC, dll)** (`data_barang_ac.php`)
- **Database Karyawan/SDM** (`data_karyawan.php`)
- **Master Unit & Jabatan** (`unit_kerja.php`, `jabatan.php`)
- **Master Poliklinik** (`master_poliklinik.php`)

### **6. 🛡️ SYSTEM INTEGRITY & SECURITY**
*Fokus: Menjaga integritas data, audit log keamanan, dan laporan kepatuhan sistem.*
- **Log Login & Aktivitas** (`log_login.php`, `activity_log.php`)
- **Security Scanner** (`scan_malware.php`)
- **Audit Integritas Data** (`generate_hash_asli.php`)
- **Laporan Harian/Bulanan/Tahunan** (`laporan_harian.php`, `laporan_bulanan.php`, `laporan_tahunan.php`)

---

## 🛠️ RENCANA EKSEKUSI KODE (`sidebar.php`)

1. **RBAC Logic Protection**: Memastikan pengecekan `$allowed_files` tetap ada di setiap menu.
2. **Lucide Icon Integration**: Menyelaraskan icon dengan fungsi menu yang baru.
3. **Dropdown Optimization**: Mengelompokkan menu di atas ke dalam dropdown yang relevan.
4. **Maintenance Rutin Menu**: Memasukkan menu maintenance ke masing-masing grup Technical Support.

---
**Catatan Akhir:** Perubahan ini akan membuat sidebar terasa lebih "pintar" karena memandu user berdasarkan alur kerja yang logis.
