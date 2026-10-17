-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 11 Apr 2026 pada 11.47
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_rentalmobil`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `bukti_bayar`
--

CREATE TABLE `bukti_bayar` (
  `id` int(11) NOT NULL,
  `id_sewa` int(11) DEFAULT NULL,
  `jenis` enum('mobil','motor') DEFAULT 'mobil',
  `id_user` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `metode` varchar(50) DEFAULT 'Transfer SeaBank',
  `status` enum('menunggu','diterima','ditolak') DEFAULT 'menunggu',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bukti_bayar`
--

INSERT INTO `bukti_bayar` (`id`, `id_sewa`, `jenis`, `id_user`, `file_path`, `catatan`, `metode`, `status`, `created_at`) VALUES
(1, 6, 'mobil', 2, 'uploads/bukti/bukti_mobil_6_1775900620_a2bfdd54.jpg', '', 'Transfer SeaBank', 'menunggu', '2026-04-11 16:43:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `checklist_kendaraan`
--

CREATE TABLE `checklist_kendaraan` (
  `id_checklist` int(11) NOT NULL,
  `id_sewa` int(11) DEFAULT NULL,
  `jenis` enum('mobil','motor') DEFAULT 'mobil',
  `tipe` enum('keluar','kembali') NOT NULL,
  `kondisi_eksterior` varchar(20) DEFAULT 'baik',
  `kondisi_interior` varchar(20) DEFAULT 'baik',
  `kondisi_mesin` varchar(20) DEFAULT 'baik',
  `kondisi_ban` varchar(20) DEFAULT 'baik',
  `bbm_level` varchar(20) DEFAULT 'penuh',
  `km_odometer` int(11) DEFAULT 0,
  `catatan` text DEFAULT NULL,
  `foto_urls` text DEFAULT NULL,
  `input_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `denda_biaya`
--

CREATE TABLE `denda_biaya` (
  `id_denda` int(11) NOT NULL,
  `id_sewa` int(11) DEFAULT NULL,
  `jenis` enum('mobil','motor') DEFAULT 'mobil',
  `tipe_denda` varchar(50) DEFAULT 'kerusakan',
  `deskripsi` text DEFAULT NULL,
  `jumlah` decimal(12,2) DEFAULT 0.00,
  `input_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `denda_biaya`
--

INSERT INTO `denda_biaya` (`id_denda`, `id_sewa`, `jenis`, `tipe_denda`, `deskripsi`, `jumlah`, `input_by`, `created_at`) VALUES
(1, 2, 'motor', 'kerusakan', '', 250000.00, 1, '2026-04-11 16:28:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_penyewaan`
--

CREATE TABLE `log_penyewaan` (
  `id_sewa` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_mobil` int(11) DEFAULT NULL,
  `tanggal_sewa` date DEFAULT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `total_bayar` decimal(12,2) DEFAULT NULL,
  `status` enum('aktif','selesai') DEFAULT 'aktif',
  `payment_status` varchar(20) DEFAULT 'pending',
  `payment_method` varchar(30) DEFAULT 'Transfer Bank',
  `addon_total` decimal(12,2) DEFAULT 0.00,
  `addon_detail` text DEFAULT NULL,
  `kode_promo` varchar(30) DEFAULT NULL,
  `diskon_promo` decimal(12,2) DEFAULT 0.00,
  `admin_confirmed` tinyint(1) DEFAULT 0,
  `agreement_accepted` tinyint(1) DEFAULT 0,
  `agreement_at` datetime DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `log_penyewaan`
--

INSERT INTO `log_penyewaan` (`id_sewa`, `id_user`, `id_mobil`, `tanggal_sewa`, `tanggal_kembali`, `total_bayar`, `status`, `payment_status`, `payment_method`, `addon_total`, `addon_detail`, `kode_promo`, `diskon_promo`, `admin_confirmed`, `agreement_accepted`, `agreement_at`, `catatan_admin`, `created_at`) VALUES
(1, 2, 4, '2026-01-04', '2026-01-05', 500000.00, 'selesai', 'lunas', 'Transfer Bank', 0.00, NULL, NULL, 0.00, 1, 0, NULL, NULL, '2026-04-11 03:45:28'),
(2, 3, 1, '2026-01-10', '2026-01-12', 600000.00, 'selesai', 'lunas', 'QRIS', 0.00, NULL, NULL, 0.00, 1, 0, NULL, NULL, '2026-04-11 03:45:28'),
(3, 4, 5, '2026-02-01', '2026-02-03', 870000.00, 'selesai', 'lunas', 'Transfer Bank', 150000.00, 'Driver', NULL, 0.00, 1, 0, NULL, NULL, '2026-04-11 03:45:28'),
(4, 2, 1, '2026-04-11', '2026-04-12', 500000.00, 'aktif', 'menunggu_konfirmasi', 'Transfer Bank', 250000.00, 'Driver, Airport Pickup', 'HEMAT50K', 50000.00, 1, 1, '2026-04-11 15:52:06', '', '2026-04-11 15:52:06'),
(6, 2, 1, '2026-04-11', '2026-04-12', 300000.00, 'aktif', 'menunggu_konfirmasi', 'Transfer Bank', 0.00, '', NULL, 0.00, 1, 1, '2026-04-11 16:33:27', '', '2026-04-11 16:33:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_sewa_motor`
--

CREATE TABLE `log_sewa_motor` (
  `id_sewa` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_motor` int(11) DEFAULT NULL,
  `tanggal_sewa` date DEFAULT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `total_bayar` decimal(12,2) DEFAULT NULL,
  `status` enum('aktif','selesai') DEFAULT 'aktif',
  `payment_status` varchar(20) DEFAULT 'pending',
  `payment_method` varchar(30) DEFAULT 'Transfer Bank',
  `addon_total` decimal(12,2) DEFAULT 0.00,
  `addon_detail` text DEFAULT NULL,
  `kode_promo` varchar(30) DEFAULT NULL,
  `diskon_promo` decimal(12,2) DEFAULT 0.00,
  `admin_confirmed` tinyint(1) DEFAULT 0,
  `agreement_accepted` tinyint(1) DEFAULT 0,
  `agreement_at` datetime DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `log_sewa_motor`
--

INSERT INTO `log_sewa_motor` (`id_sewa`, `id_user`, `id_motor`, `tanggal_sewa`, `tanggal_kembali`, `total_bayar`, `status`, `payment_status`, `payment_method`, `addon_total`, `addon_detail`, `kode_promo`, `diskon_promo`, `admin_confirmed`, `agreement_accepted`, `agreement_at`, `catatan_admin`, `created_at`) VALUES
(1, 5, 2, '2026-01-15', '2026-01-17', 180000.00, 'selesai', 'lunas', 'QRIS', 0.00, NULL, NULL, 0.00, 1, 0, NULL, NULL, '2026-04-11 03:45:28'),
(2, 2, 3, '2026-04-11', '2026-04-12', 85000.00, 'selesai', 'lunas', 'Transfer Bank', 0.00, '', NULL, 0.00, 1, 1, '2026-04-11 16:25:50', '', '2026-04-11 16:25:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mobil`
--

CREATE TABLE `mobil` (
  `id_mobil` int(11) NOT NULL,
  `nama_mobil` varchar(100) NOT NULL,
  `merk` varchar(50) DEFAULT NULL,
  `tahun` int(11) DEFAULT NULL,
  `harga_sewa` decimal(12,2) DEFAULT NULL,
  `foto_mobil` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mobil`
--

INSERT INTO `mobil` (`id_mobil`, `nama_mobil`, `merk`, `tahun`, `harga_sewa`, `foto_mobil`, `status`) VALUES
(1, 'Avanza', 'Toyota', 2020, 300000.00, 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=600&q=80', 'disewa'),
(2, 'Xenia', 'Daihatsu', 2019, 330000.00, 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(3, 'Brio', 'Honda', 2021, 280000.00, 'https://images.unsplash.com/photo-1541443131876-44b03de101c5?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(4, 'Innova', 'Toyota', 2022, 500000.00, 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(5, 'Ertiga', 'Suzuki', 2020, 360000.00, 'https://images.unsplash.com/photo-1571607388263-1044f9ea01dd?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(6, 'Ayla', 'Daihatsu', 2018, 260000.00, 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(7, 'HR-V', 'Honda', 2023, 550000.00, 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(8, 'Rush', 'Toyota', 2021, 420000.00, 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=600&q=80', 'tersedia');

-- --------------------------------------------------------

--
-- Struktur dari tabel `motor`
--

CREATE TABLE `motor` (
  `id_motor` int(11) NOT NULL,
  `nama_motor` varchar(100) NOT NULL,
  `merk` varchar(50) DEFAULT NULL,
  `tahun` int(11) DEFAULT NULL,
  `harga_sewa` decimal(12,2) DEFAULT NULL,
  `foto_motor` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `motor`
--

INSERT INTO `motor` (`id_motor`, `nama_motor`, `merk`, `tahun`, `harga_sewa`, `foto_motor`, `status`) VALUES
(1, 'Vario 160', 'Honda', 2023, 80000.00, 'https://images.unsplash.com/photo-1622184935273-52c39bef8e7b?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(2, 'NMAX', 'Yamaha', 2022, 90000.00, 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(3, 'Aerox', 'Yamaha', 2023, 85000.00, 'https://images.unsplash.com/photo-1609630875171-b1321377ee65?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(4, 'PCX 160', 'Honda', 2022, 95000.00, 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(5, 'Scoopy', 'Honda', 2021, 70000.00, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?auto=format&fit=crop&w=600&q=80', 'tersedia'),
(6, 'Lexi', 'Yamaha', 2022, 80000.00, 'https://images.unsplash.com/photo-1449426468159-d96dbf08f19f?auto=format&fit=crop&w=600&q=80', 'tersedia');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi_admin`
--

CREATE TABLE `notifikasi_admin` (
  `id_notif` int(11) NOT NULL,
  `tipe` varchar(30) DEFAULT 'pesanan_baru',
  `id_sewa` int(11) DEFAULT NULL,
  `jenis` enum('mobil','motor') DEFAULT 'mobil',
  `pesan` text DEFAULT NULL,
  `sudah_baca` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifikasi_admin`
--

INSERT INTO `notifikasi_admin` (`id_notif`, `tipe`, `id_sewa`, `jenis`, `pesan`, `sudah_baca`, `created_at`) VALUES
(1, 'pesanan_baru', 4, 'mobil', 'Pesanan baru dari Andi Pratama: Avanza (2026-04-11 s/d 2026-04-12) — Rp 500.000', 1, '2026-04-11 15:52:06'),
(2, 'pembayaran', 4, 'mobil', 'Andi Pratama mengklaim sudah membayar Rp 500.000 via Transfer Bank untuk Avanza. Harap verifikasi!', 1, '2026-04-11 15:52:23'),
(3, 'pesanan_baru', 5, 'mobil', 'Pesanan baru dari Andi Pratama: Ayla (2026-04-11 s/d 2026-04-13) — Rp 520.000', 1, '2026-04-11 16:06:02'),
(4, 'pesanan_baru', 2, 'motor', 'Pesanan baru dari Andi Pratama: Aerox (2026-04-11 s/d 2026-04-12) — Rp 85.000', 1, '2026-04-11 16:25:50'),
(5, 'pesanan_baru', 6, 'mobil', 'Pesanan baru dari Andi Pratama: Avanza (2026-04-11 s/d 2026-04-12) — Rp 300.000', 1, '2026-04-11 16:33:27'),
(6, 'pembayaran', 6, 'mobil', 'Andi Pratama mengupload bukti transfer untuk sewa #6 (mobil). Harap verifikasi.', 0, '2026-04-11 16:43:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `promo_voucher`
--

CREATE TABLE `promo_voucher` (
  `id_promo` int(11) NOT NULL,
  `kode` varchar(30) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `tipe_diskon` enum('persen','nominal') DEFAULT 'persen',
  `nilai_diskon` decimal(12,2) DEFAULT 0.00,
  `min_transaksi` decimal(12,2) DEFAULT 0.00,
  `max_penggunaan` int(11) DEFAULT 100,
  `sudah_dipakai` int(11) DEFAULT 0,
  `berlaku_dari` date DEFAULT NULL,
  `berlaku_sampai` date DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `promo_voucher`
--

INSERT INTO `promo_voucher` (`id_promo`, `kode`, `deskripsi`, `tipe_diskon`, `nilai_diskon`, `min_transaksi`, `max_penggunaan`, `sudah_dipakai`, `berlaku_dari`, `berlaku_sampai`, `status`, `created_at`) VALUES
(1, 'ZNRENT10', 'Diskon 10% untuk semua kendaraan', 'persen', 10.00, 200000.00, 50, 0, '2026-01-01', '2026-12-31', 'aktif', '2026-04-11 15:19:53'),
(2, 'HEMAT50K', 'Potongan Rp 50.000 min. transaksi Rp 500.000', 'nominal', 50000.00, 500000.00, 30, 1, '2026-01-01', '2026-06-30', 'aktif', '2026-04-11 15:19:53');

-- --------------------------------------------------------

--
-- Struktur dari tabel `review_kendaraan`
--

CREATE TABLE `review_kendaraan` (
  `id_review` int(11) NOT NULL,
  `id_sewa` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_kendaraan` int(11) DEFAULT NULL,
  `jenis` enum('mobil','motor') DEFAULT 'mobil',
  `rating` tinyint(1) DEFAULT 5,
  `komentar` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `review_kendaraan`
--

INSERT INTO `review_kendaraan` (`id_review`, `id_sewa`, `id_user`, `id_kendaraan`, `jenis`, `rating`, `komentar`, `created_at`) VALUES
(1, 1, 2, 4, 'mobil', 5, 'gg', '2026-04-11 15:20:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tripay_transactions`
--

CREATE TABLE `tripay_transactions` (
  `id` int(11) NOT NULL,
  `id_sewa` int(11) DEFAULT NULL,
  `jenis` enum('mobil','motor') DEFAULT 'mobil',
  `merchant_ref` varchar(60) DEFAULT NULL,
  `tripay_ref` varchar(60) DEFAULT NULL,
  `channel` varchar(30) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'UNPAID',
  `pay_code` varchar(100) DEFAULT NULL,
  `pay_url` varchar(255) DEFAULT NULL,
  `checkout_url` varchar(255) DEFAULT NULL,
  `expired_at` int(11) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `role` enum('admin','customer') DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id_user`, `username`, `password`, `nama_lengkap`, `role`) VALUES
(1, 'admin01', '$2y$10$eCylL8LjH/bsJa/JaHtNX.Aeaoi5LSy/FSOszkmZ6AuPMxay7bx0q', 'Administrator Utama', 'admin'),
(2, 'andi', '$2y$10$SUjswwpRF0VL2H8R8HPSF.Flz8Qdh9N6WKytClwnnqVj7FmIXwhPa', 'Andi Pratama', 'customer'),
(3, 'budi', '$2y$10$Ylmsedqko6S8Q4H202lNzOkLBu/XImQb.J2UBNVxpnsdsCgqYVUU6', 'Budi Santoso', 'customer'),
(4, 'siti', '$2y$10$j1hwxM6SK.nBfjRPay4mI.kM3xdwA6wDRel3u43I0GTjAzm5ZcAoy', 'Siti Aminah', 'customer'),
(5, 'rina', '$2y$10$QsEwe9GzDPk3N28rh48xsea96wIPDeugluOrOo6KrXpyoo7I0i2vO', 'Rina Lestari', 'customer'),
(6, 'admin02', '$2y$10$/CrnWMnSg6A0m7M/9WBtoO3Ou7JD0LNlAvkLClPu2G5mAweSESz/a', 'Administrator 2', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `bukti_bayar`
--
ALTER TABLE `bukti_bayar`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `checklist_kendaraan`
--
ALTER TABLE `checklist_kendaraan`
  ADD PRIMARY KEY (`id_checklist`),
  ADD KEY `id_sewa` (`id_sewa`);

--
-- Indeks untuk tabel `denda_biaya`
--
ALTER TABLE `denda_biaya`
  ADD PRIMARY KEY (`id_denda`),
  ADD KEY `id_sewa` (`id_sewa`);

--
-- Indeks untuk tabel `log_penyewaan`
--
ALTER TABLE `log_penyewaan`
  ADD PRIMARY KEY (`id_sewa`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_mobil` (`id_mobil`);

--
-- Indeks untuk tabel `log_sewa_motor`
--
ALTER TABLE `log_sewa_motor`
  ADD PRIMARY KEY (`id_sewa`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_motor` (`id_motor`);

--
-- Indeks untuk tabel `mobil`
--
ALTER TABLE `mobil`
  ADD PRIMARY KEY (`id_mobil`);

--
-- Indeks untuk tabel `motor`
--
ALTER TABLE `motor`
  ADD PRIMARY KEY (`id_motor`);

--
-- Indeks untuk tabel `notifikasi_admin`
--
ALTER TABLE `notifikasi_admin`
  ADD PRIMARY KEY (`id_notif`);

--
-- Indeks untuk tabel `promo_voucher`
--
ALTER TABLE `promo_voucher`
  ADD PRIMARY KEY (`id_promo`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indeks untuk tabel `review_kendaraan`
--
ALTER TABLE `review_kendaraan`
  ADD PRIMARY KEY (`id_review`),
  ADD UNIQUE KEY `uq_review` (`id_sewa`,`jenis`),
  ADD KEY `id_kendaraan_jenis` (`id_kendaraan`,`jenis`);

--
-- Indeks untuk tabel `tripay_transactions`
--
ALTER TABLE `tripay_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `merchant_ref` (`merchant_ref`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `bukti_bayar`
--
ALTER TABLE `bukti_bayar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `checklist_kendaraan`
--
ALTER TABLE `checklist_kendaraan`
  MODIFY `id_checklist` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `denda_biaya`
--
ALTER TABLE `denda_biaya`
  MODIFY `id_denda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `log_penyewaan`
--
ALTER TABLE `log_penyewaan`
  MODIFY `id_sewa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `log_sewa_motor`
--
ALTER TABLE `log_sewa_motor`
  MODIFY `id_sewa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `mobil`
--
ALTER TABLE `mobil`
  MODIFY `id_mobil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `motor`
--
ALTER TABLE `motor`
  MODIFY `id_motor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `notifikasi_admin`
--
ALTER TABLE `notifikasi_admin`
  MODIFY `id_notif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `promo_voucher`
--
ALTER TABLE `promo_voucher`
  MODIFY `id_promo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `review_kendaraan`
--
ALTER TABLE `review_kendaraan`
  MODIFY `id_review` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `tripay_transactions`
--
ALTER TABLE `tripay_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `log_penyewaan`
--
ALTER TABLE `log_penyewaan`
  ADD CONSTRAINT `fk_log_mobil` FOREIGN KEY (`id_mobil`) REFERENCES `mobil` (`id_mobil`),
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `log_sewa_motor`
--
ALTER TABLE `log_sewa_motor`
  ADD CONSTRAINT `fk_log_motor_motor` FOREIGN KEY (`id_motor`) REFERENCES `motor` (`id_motor`),
  ADD CONSTRAINT `fk_log_motor_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =====================================================
-- Tabel baru: chat_messages (fitur chat customer-admin)
-- =====================================================
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sender` INT NOT NULL,
  `id_receiver` INT DEFAULT NULL COMMENT 'NULL = ke semua admin',
  `pesan` TEXT NOT NULL,
  `sudah_baca` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Kolom tambahan di tabel user (profil lengkap)
-- =====================================================
ALTER TABLE `user`
  ADD COLUMN IF NOT EXISTS `no_hp`       VARCHAR(20)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `alamat`      TEXT         DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `foto_profil` VARCHAR(255) DEFAULT NULL;
