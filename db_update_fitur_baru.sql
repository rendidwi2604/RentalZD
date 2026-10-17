-- ============================================================
-- ZN RENT — Database Migration: Fitur Baru
-- Jalankan file ini setelah db_rentalmobil.sql
-- ============================================================

USE `db_rentalmobil`;

-- ============================================================
-- 1. Availability Check: tambah kolom periode ke log
-- (sudah ada tanggal_sewa & tanggal_kembali — tidak perlu alter)

-- 2. E-Agreement: simpan persetujuan per booking
ALTER TABLE `log_penyewaan`
  ADD COLUMN IF NOT EXISTS `agreement_accepted` TINYINT(1) DEFAULT 0 AFTER `admin_confirmed`,
  ADD COLUMN IF NOT EXISTS `agreement_at` DATETIME DEFAULT NULL AFTER `agreement_accepted`;

ALTER TABLE `log_sewa_motor`
  ADD COLUMN IF NOT EXISTS `agreement_accepted` TINYINT(1) DEFAULT 0 AFTER `admin_confirmed`,
  ADD COLUMN IF NOT EXISTS `agreement_at` DATETIME DEFAULT NULL AFTER `agreement_accepted`;

-- ============================================================
-- 3. Kondisi Kendaraan (Pre/Post Checklist)
CREATE TABLE IF NOT EXISTS `checklist_kendaraan` (
  `id_checklist`   INT(11)               NOT NULL AUTO_INCREMENT,
  `id_sewa`        INT(11)               DEFAULT NULL,
  `jenis`          ENUM('mobil','motor') DEFAULT 'mobil',
  `tipe`           ENUM('keluar','kembali') NOT NULL,
  `kondisi_eksterior` VARCHAR(20) DEFAULT 'baik',
  `kondisi_interior`  VARCHAR(20) DEFAULT 'baik',
  `kondisi_mesin`     VARCHAR(20) DEFAULT 'baik',
  `kondisi_ban`       VARCHAR(20) DEFAULT 'baik',
  `bbm_level`      VARCHAR(20) DEFAULT 'penuh',
  `km_odometer`    INT(11) DEFAULT 0,
  `catatan`        TEXT DEFAULT NULL,
  `foto_urls`      TEXT DEFAULT NULL,
  `input_by`       INT(11) DEFAULT NULL,
  `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_checklist`),
  KEY `id_sewa` (`id_sewa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. Rating & Review Kendaraan
CREATE TABLE IF NOT EXISTS `review_kendaraan` (
  `id_review`    INT(11)               NOT NULL AUTO_INCREMENT,
  `id_sewa`      INT(11)               DEFAULT NULL,
  `id_user`      INT(11)               DEFAULT NULL,
  `id_kendaraan` INT(11)               DEFAULT NULL,
  `jenis`        ENUM('mobil','motor') DEFAULT 'mobil',
  `rating`       TINYINT(1)            DEFAULT 5,
  `komentar`     TEXT                  DEFAULT NULL,
  `created_at`   DATETIME              DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_review`),
  UNIQUE KEY `uq_review` (`id_sewa`,`jenis`),
  KEY `id_kendaraan_jenis` (`id_kendaraan`,`jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. Denda / Biaya Tambahan
CREATE TABLE IF NOT EXISTS `denda_biaya` (
  `id_denda`   INT(11)               NOT NULL AUTO_INCREMENT,
  `id_sewa`    INT(11)               DEFAULT NULL,
  `jenis`      ENUM('mobil','motor') DEFAULT 'mobil',
  `tipe_denda` VARCHAR(50)           DEFAULT 'kerusakan',
  `deskripsi`  TEXT                  DEFAULT NULL,
  `jumlah`     DECIMAL(12,2)         DEFAULT 0,
  `input_by`   INT(11)               DEFAULT NULL,
  `created_at` DATETIME              DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_denda`),
  KEY `id_sewa` (`id_sewa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. Promo & Voucher
CREATE TABLE IF NOT EXISTS `promo_voucher` (
  `id_promo`      INT(11)     NOT NULL AUTO_INCREMENT,
  `kode`          VARCHAR(30) NOT NULL,
  `deskripsi`     VARCHAR(255) DEFAULT NULL,
  `tipe_diskon`   ENUM('persen','nominal') DEFAULT 'persen',
  `nilai_diskon`  DECIMAL(12,2) DEFAULT 0,
  `min_transaksi` DECIMAL(12,2) DEFAULT 0,
  `max_penggunaan` INT(11)   DEFAULT 100,
  `sudah_dipakai`  INT(11)   DEFAULT 0,
  `berlaku_dari`  DATE        DEFAULT NULL,
  `berlaku_sampai` DATE       DEFAULT NULL,
  `status`        ENUM('aktif','nonaktif') DEFAULT 'aktif',
  `created_at`    DATETIME    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_promo`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `promo_voucher` (`kode`,`deskripsi`,`tipe_diskon`,`nilai_diskon`,`min_transaksi`,`max_penggunaan`,`berlaku_dari`,`berlaku_sampai`) VALUES
('ZNRENT10','Diskon 10% untuk semua kendaraan','persen',10,200000,50,'2026-01-01','2026-12-31'),
('HEMAT50K','Potongan Rp 50.000 min. transaksi Rp 500.000','nominal',50000,500000,30,'2026-01-01','2026-06-30');

-- Kolom promo di log_penyewaan & log_sewa_motor
ALTER TABLE `log_penyewaan`
  ADD COLUMN IF NOT EXISTS `kode_promo`     VARCHAR(30)   DEFAULT NULL AFTER `addon_detail`,
  ADD COLUMN IF NOT EXISTS `diskon_promo`   DECIMAL(12,2) DEFAULT 0   AFTER `kode_promo`;

ALTER TABLE `log_sewa_motor`
  ADD COLUMN IF NOT EXISTS `kode_promo`     VARCHAR(30)   DEFAULT NULL AFTER `addon_detail`,
  ADD COLUMN IF NOT EXISTS `diskon_promo`   DECIMAL(12,2) DEFAULT 0   AFTER `kode_promo`;

-- ============================================================
-- 7. Update foto motor dengan URL Unsplash yang lebih jelas
UPDATE `motor` SET `foto_motor` = 'https://images.unsplash.com/photo-1622184935273-52c39bef8e7b?auto=format&fit=crop&w=600&q=80' WHERE id_motor = 1; -- Vario 160
UPDATE `motor` SET `foto_motor` = 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=600&q=80' WHERE id_motor = 2; -- NMAX
UPDATE `motor` SET `foto_motor` = 'https://images.unsplash.com/photo-1609630875171-b1321377ee65?auto=format&fit=crop&w=600&q=80' WHERE id_motor = 3; -- Aerox
UPDATE `motor` SET `foto_motor` = 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=600&q=80' WHERE id_motor = 4; -- PCX 160
UPDATE `motor` SET `foto_motor` = 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?auto=format&fit=crop&w=600&q=80' WHERE id_motor = 5; -- Scoopy
UPDATE `motor` SET `foto_motor` = 'https://images.unsplash.com/photo-1449426468159-d96dbf08f19f?auto=format&fit=crop&w=600&q=80' WHERE id_motor = 6; -- Lexi

-- ============================================================
COMMIT;
