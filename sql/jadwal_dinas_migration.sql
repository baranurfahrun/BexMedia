-- ================================================
-- BexMedia: Jadwal Dinas Tables
-- ================================================

USE bexmedia;

CREATE TABLE IF NOT EXISTS `jam_kerja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama_jam` varchar(50) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `warna` varchar(10) DEFAULT '#3B82F6',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default shifts (bisa disesuaikan)
INSERT IGNORE INTO `jam_kerja` (`kode`, `nama_jam`, `jam_mulai`, `jam_selesai`, `warna`) VALUES
('PAGI',  'Pagi',  '07:00:00', '14:00:00', '#3B82F6'),
('SIANG', 'Siang', '14:00:00', '21:00:00', '#10B981'),
('MALAM', 'Malam', '21:00:00', '07:00:00', '#8B5CF6'),
('MID_P', 'Middle Pagi',  '10:00:00', '17:00:00', '#F59E0B'),
('MID_S', 'Middle Siang', '17:00:00', '00:00:00', '#EF4444'),
('LIBUR', 'Libur', '00:00:00', '00:00:00', '#94A3B8');

CREATE TABLE IF NOT EXISTS `jadwal_dinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `bulan` tinyint(2) NOT NULL,
  `tahun` smallint(4) NOT NULL,
  `jam_kerja_id` int(11) NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_jadwal` (`user_id`, `tanggal`),
  KEY `user_id` (`user_id`),
  KEY `jam_kerja_id` (`jam_kerja_id`),
  CONSTRAINT `fk_jadwal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jadwal_jam` FOREIGN KEY (`jam_kerja_id`) REFERENCES `jam_kerja` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
