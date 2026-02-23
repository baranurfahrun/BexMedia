-- Migration Script for FixPoint Modules in BexMedia
-- Managed by Bara N. Fahrun (BexMedia Guard)

USE bexmedia;

-- 1. Core Menu System (FixPoint Style)
CREATE TABLE IF NOT EXISTS `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_menu` varchar(100) NOT NULL,
  `file_menu` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_menu` (`file_menu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `akses_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Master Tables
CREATE TABLE IF NOT EXISTS `master_url` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_koneksi` varchar(100) NOT NULL,
  `base_url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kategori_hardware` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kategori_software` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Inventory & Maintenance
CREATE TABLE IF NOT EXISTS `data_barang_it` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(50),
  `nama_barang` varchar(100) NOT NULL,
  `kategori` varchar(100),
  `lokasi` varchar(100),
  `status` varchar(50),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `maintanance_rutin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11),
  `nama_teknisi` varchar(100),
  `barang_id` int(11),
  `kondisi_fisik` text,
  `fungsi_perangkat` text,
  `catatan` text,
  `waktu_input` datetime,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Correspondence
CREATE TABLE IF NOT EXISTS `surat_masuk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_surat` varchar(100),
  `tgl_surat` date,
  `tgl_terima` date,
  `pengirim` varchar(255),
  `asal_surat` varchar(255),
  `perihal` text,
  `lampiran` varchar(100),
  `jenis_surat` varchar(50),
  `sifat_surat` varchar(50),
  `perlu_balasan` varchar(10),
  `status_balasan` varchar(50),
  `disposisi_ke` varchar(255),
  `catatan` text,
  `file_surat` varchar(255),
  `user_input` int(11),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `surat_keluar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_surat` varchar(100),
  `tgl_surat` date,
  `tujuan` varchar(255),
  `perihal` text,
  `jenis_surat` varchar(50),
  `sifat_surat` varchar(50),
  `file_surat` varchar(255),
  `balasan_untuk_id` int(11),
  `user_input` int(11),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. HRD & Cuti
CREATE TABLE IF NOT EXISTS `master_cuti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_cuti` varchar(100),
  `jatah_hari` int(11),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `jatah_cuti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11),
  `cuti_id` int(11),
  `tahun` year(4),
  `lama_hari` int(11),
  `sisa_hari` int(11),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. IT Tickets
CREATE TABLE IF NOT EXISTS `tiket_it_hardware` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_tiket` varchar(50),
  `user_id` int(11),
  `barang_id` int(11),
  `keluhan` text,
  `status` enum('menunggu','proses','selesai','batal') DEFAULT 'menunggu',
  `waktu_input` datetime,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tiket_it_software` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_tiket` varchar(50),
  `user_id` int(11),
  `modul` varchar(100),
  `keluhan` text,
  `status` enum('menunggu','proses','selesai','batal') DEFAULT 'menunggu',
  `waktu_input` datetime,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
