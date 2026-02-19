-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 19 Feb 2026 pada 14.32
-- Versi server: 10.4.25-MariaDB
-- Versi PHP: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bexmedia`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `login_attempts`
--

CREATE TABLE `login_attempts` (
  `ip` varchar(50) NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varbinary(255) NOT NULL,
  `password` varbinary(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `created_at`) VALUES
(2, 0x6eccaeebb6810946088bb37973490bc6, 0xb8f485f28ab647cd3211211be2cbb078, 'Administrator BexMedia', '2026-02-19 06:26:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `web_dokter_audit_log`
--

CREATE TABLE `web_dokter_audit_log` (
  `id` int(11) NOT NULL,
  `user_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `web_dokter_audit_log`
--

INSERT INTO `web_dokter_audit_log` (`id`, `user_id`, `ip_address`, `action`, `description`, `user_agent`, `created_at`) VALUES
(1, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:57:11'),
(2, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:57:16'),
(3, 'admin', '::1', 'UPDATE_PROFILE', 'User admin memperbarui profil & foto (Clean-up old file).', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:57:23'),
(4, 'admin', '::1', 'UPDATE_PROFILE', 'User admin memperbarui profil & foto (Clean-up old file).', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:58:19'),
(5, 'admin', '::1', 'UPDATE_PROFILE', 'User admin memperbarui profil & foto (Clean-up old file).', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:58:25'),
(6, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:58:35'),
(7, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:58:39'),
(8, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:02:19'),
(9, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:02:50'),
(10, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:03:26'),
(11, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:04:03'),
(12, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:05:26'),
(13, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:07:04'),
(14, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:07:09'),
(15, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:07:14'),
(16, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:07:37'),
(17, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:07:56'),
(18, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:09:59'),
(19, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:11:51'),
(20, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:12:07'),
(21, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:13:54'),
(22, 'admin', '::1', 'UPDATE_SETTINGS', 'User admin memperbarui pengaturan sistem.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:16:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `web_settings`
--

CREATE TABLE `web_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `web_settings`
--

INSERT INTO `web_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('app_name', 'BexMedia', '2026-02-19 08:42:19'),
('host_bex', 'localhost', '2026-02-19 08:42:19'),
('host_khanza', '192.20.20.253', '2026-02-19 08:42:19'),
('name_bex', 'bexmedia', '2026-02-19 08:42:19'),
('name_khanza', 'sik', '2026-02-19 08:42:19'),
('pass_bex', '', '2026-02-19 08:42:19'),
('pass_khanza', 'root', '2026-02-19 08:42:19'),
('user_bex', 'root', '2026-02-19 08:42:19'),
('user_khanza', 'root', '2026-02-19 08:42:19');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`ip`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `web_dokter_audit_log`
--
ALTER TABLE `web_dokter_audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `web_settings`
--
ALTER TABLE `web_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `web_dokter_audit_log`
--
ALTER TABLE `web_dokter_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
