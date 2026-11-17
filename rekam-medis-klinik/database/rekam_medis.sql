-- ==============================================================
-- SISTEM INFORMASI REKAM MEDIS KLINIK BERBASIS WEB
-- Database: rekam_medis
-- Kompatibel dengan MySQL / MariaDB / phpMyAdmin (XAMPP)
-- ==============================================================

SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE IF NOT EXISTS `rekam_medis` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rekam_medis`;

-- --------------------------------------------------------
-- 1. Tabel users (Autentikasi Pengguna & Role)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'dokter', 'petugas') NOT NULL DEFAULT 'admin',
  `email` VARCHAR(100) DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT 'default-avatar.png',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Tabel dokter (Tenaga Kesehatan)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `dokter`;
CREATE TABLE `dokter` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nip` VARCHAR(30) NOT NULL UNIQUE,
  `nama_dokter` VARCHAR(100) NOT NULL,
  `spesialisasi` VARCHAR(100) NOT NULL,
  `no_telepon` VARCHAR(25) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `jadwal_praktek` VARCHAR(255) NOT NULL,
  `status` ENUM('Aktif', 'Cuti', 'Nonaktif') NOT NULL DEFAULT 'Aktif',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Tabel pasien (Data Pasien & No Rekam Medis)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pasien`;
CREATE TABLE `pasien` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_rm` VARCHAR(20) NOT NULL UNIQUE,
  `nik` VARCHAR(20) NOT NULL,
  `nama_pasien` VARCHAR(100) NOT NULL,
  `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL,
  `tempat_lahir` VARCHAR(50) DEFAULT NULL,
  `tanggal_lahir` DATE NOT NULL,
  `alamat` TEXT NOT NULL,
  `no_telepon` VARCHAR(25) NOT NULL,
  `gol_darah` ENUM('A', 'B', 'AB', 'O', '-') DEFAULT '-',
  `alergi` VARCHAR(255) DEFAULT 'Tidak Ada',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Tabel pendaftaran (Kunjungan & Antrean Pasien)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pendaftaran`;
CREATE TABLE `pendaftaran` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_antrean` VARCHAR(20) NOT NULL,
  `id_pasien` INT NOT NULL,
  `id_dokter` INT NOT NULL,
  `tanggal_kunjungan` DATE NOT NULL,
  `keluhan_awal` TEXT DEFAULT NULL,
  `jenis_pasien` ENUM('Umum', 'BPJS', 'Asuransi Swasta') NOT NULL DEFAULT 'Umum',
  `status` ENUM('Menunggu', 'Diperiksa', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`tanggal_kunjungan`),
  INDEX (`status`),
  CONSTRAINT `fk_pendaftaran_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pendaftaran_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Tabel rekam_medis (Pemeriksaan Klinis & Hasil Diagnosa)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `rekam_medis`;
CREATE TABLE `rekam_medis` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pendaftaran` INT NOT NULL UNIQUE,
  `id_pasien` INT NOT NULL,
  `id_dokter` INT NOT NULL,
  `tanggal_pemeriksaan` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `keluhan` TEXT NOT NULL,
  `tekanan_darah` VARCHAR(20) DEFAULT '-',
  `suhu_tubuh` DECIMAL(4,1) DEFAULT NULL,
  `berat_badan` DECIMAL(5,1) DEFAULT NULL,
  `tinggi_badan` DECIMAL(5,1) DEFAULT NULL,
  `diagnosis` TEXT NOT NULL,
  `tindakan` TEXT NOT NULL,
  `catatan_dokter` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rm_pendaftaran` FOREIGN KEY (`id_pendaftaran`) REFERENCES `pendaftaran` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rm_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rm_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Tabel obat (Manajemen Stok & Data Obat)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `obat`;
CREATE TABLE `obat` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_obat` VARCHAR(20) NOT NULL UNIQUE,
  `nama_obat` VARCHAR(100) NOT NULL,
  `kategori` VARCHAR(50) NOT NULL,
  `jenis_obat` ENUM('Tablet', 'Kapsul', 'Sirup', 'Salep', 'Injeksi', 'Tetes') NOT NULL DEFAULT 'Tablet',
  `stok` INT NOT NULL DEFAULT 0,
  `satuan` VARCHAR(25) NOT NULL DEFAULT 'Strip',
  `harga` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `deskripsi` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Tabel resep_obat (Master Resep Dokter)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `resep_obat`;
CREATE TABLE `resep_obat` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_rekam_medis` INT NOT NULL,
  `id_pasien` INT NOT NULL,
  `id_dokter` INT NOT NULL,
  `tanggal_resep` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Menunggu', 'Diproses', 'Selesai') NOT NULL DEFAULT 'Menunggu',
  `catatan_resep` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_resep_rm` FOREIGN KEY (`id_rekam_medis`) REFERENCES `rekam_medis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resep_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resep_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Tabel resep_detail (Detail Item Obat dalam Resep)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `resep_detail`;
CREATE TABLE `resep_detail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_resep` INT NOT NULL,
  `id_obat` INT NOT NULL,
  `dosis` VARCHAR(50) NOT NULL,
  `aturan_pakai` VARCHAR(100) NOT NULL,
  `jumlah` INT NOT NULL DEFAULT 1,
  `keterangan` VARCHAR(100) DEFAULT '',
  CONSTRAINT `fk_detail_resep` FOREIGN KEY (`id_resep`) REFERENCES `resep_obat` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_detail_obat` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Tabel jadwal_kontrol (Kontrol Pasien Pasca Periksa)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `jadwal_kontrol`;
CREATE TABLE `jadwal_kontrol` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pasien` INT NOT NULL,
  `id_dokter` INT NOT NULL,
  `id_rekam_medis` INT DEFAULT NULL,
  `tanggal_kontrol` DATE NOT NULL,
  `catatan_kontrol` TEXT DEFAULT NULL,
  `status` ENUM('Terjadwal', 'Selesai', 'Dibatalkan', 'Terlewat') NOT NULL DEFAULT 'Terjadwal',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_kontrol_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kontrol_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Tabel pengaturan_klinik (Profil Klinik)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pengaturan_klinik`;
CREATE TABLE `pengaturan_klinik` (
  `id` INT PRIMARY KEY,
  `nama_klinik` VARCHAR(150) NOT NULL,
  `slogan` VARCHAR(255) DEFAULT 'Melayani dengan Sepenuh Hati dan Profesional',
  `alamat_klinik` TEXT NOT NULL,
  `no_telepon` VARCHAR(30) NOT NULL,
  `email_klinik` VARCHAR(100) NOT NULL,
  `website` VARCHAR(100) DEFAULT 'www.klinikmedika.com',
  `pimpinan_klinik` VARCHAR(100) NOT NULL,
  `jam_operasional` VARCHAR(150) DEFAULT 'Senin - Sabtu: 08.00 - 21.00 WIB',
  `logo` VARCHAR(255) DEFAULT 'logo-klinik.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- DUMMY / SEED DATA
-- Password default akun: admin123
-- Hash: $2y$10$4tkryBfhAzNKOGGg38Oqou8rl3AFUXvzXdzzPJ0JqtyBAF.J5ZKW6
-- ==============================================================

-- Seed Profil Klinik
INSERT INTO `pengaturan_klinik` (`id`, `nama_klinik`, `slogan`, `alamat_klinik`, `no_telepon`, `email_klinik`, `website`, `pimpinan_klinik`, `jam_operasional`, `logo`) VALUES
(1, 'Klinik Pratama Sehat Medika', 'Pelayanan Kesehatan Prima, Modern & Terpercaya', 'Jl. Kesehatan No. 45, Kebayoran Baru, Jakarta Selatan', '(021) 7890-1234', 'info@sehatmedika.co.id', 'https://sehatmedika.co.id', 'dr. H. Hendra Wijaya, Sp.PD', 'Senin - Sabtu: 08.00 - 21.00 WIB', 'logo-klinik.png');

-- Seed Users
INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `email`) VALUES
(1, 'admin', '$2y$10$4tkryBfhAzNKOGGg38Oqou8rl3AFUXvzXdzzPJ0JqtyBAF.J5ZKW6', 'Administrator Utama', 'admin', 'admin@sehatmedika.co.id'),
(2, 'dr_budi', '$2y$10$4tkryBfhAzNKOGGg38Oqou8rl3AFUXvzXdzzPJ0JqtyBAF.J5ZKW6', 'dr. Budi Santoso, Sp.PD', 'dokter', 'dr.budi@sehatmedika.co.id'),
(3, 'dr_siti', '$2y$10$4tkryBfhAzNKOGGg38Oqou8rl3AFUXvzXdzzPJ0JqtyBAF.J5ZKW6', 'dr. Siti Rahma, Sp.A', 'dokter', 'dr.siti@sehatmedika.co.id'),
(4, 'petugas1', '$2y$10$4tkryBfhAzNKOGGg38Oqou8rl3AFUXvzXdzzPJ0JqtyBAF.J5ZKW6', 'Anisa Triana, A.Md.RMIK', 'petugas', 'anisa@sehatmedika.co.id');

-- Seed Dokter (Tenaga Kesehatan)
INSERT INTO `dokter` (`id`, `nip`, `nama_dokter`, `spesialisasi`, `no_telepon`, `email`, `jadwal_praktek`, `status`) VALUES
(1, '198205122010011002', 'dr. Budi Santoso, Sp.PD', 'Spesialis Penyakit Dalam', '081234567890', 'dr.budi@sehatmedika.co.id', 'Senin - Rabu (08.00 - 14.00)', 'Aktif'),
(2, '198709242014022003', 'dr. Siti Rahma, Sp.A', 'Spesialis Anak', '081398765432', 'dr.siti@sehatmedika.co.id', 'Senin - Kamis (13.00 - 18.00)', 'Aktif'),
(3, '199003152018011005', 'dr. Andi Pratama', 'Dokter Umum', '085712349876', 'dr.andi@sehatmedika.co.id', 'Setiap Hari (08.00 - 16.00)', 'Aktif'),
(4, '198511202012022001', 'drg. Maya Lestari, Sp.KG', 'Dokter Gigi & Mulut', '082155667788', 'drg.maya@sehatmedika.co.id', 'Selasa & Jumat (09.00 - 15.00)', 'Aktif'),
(5, '199104082019031004', 'dr. Kevin Sanjaya, Sp.JP', 'Spesialis Jantung & Pembuluh Darah', '081822334455', 'dr.kevin@sehatmedika.co.id', 'Kamis & Sabtu (10.00 - 16.00)', 'Aktif');

-- Seed Pasien
INSERT INTO `pasien` (`id`, `nomor_rm`, `nik`, `nama_pasien`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `no_telepon`, `gol_darah`, `alergi`) VALUES
(1, 'RM-2026-0001', '3171012304850001', 'Ahmad Fauzi', 'Laki-laki', 'Jakarta', '1985-04-23', 'Jl. Tebet Barat Dalam No. 12, Jakarta Selatan', '081211223344', 'O', 'Tidak Ada'),
(2, 'RM-2026-0002', '3171025509920002', 'Dewi Sartika', 'Perempuan', 'Bandung', '1992-09-15', 'Jl. Fatmawati Raya No. 88, Cilandak', '081322334455', 'A', 'Penisilin'),
(3, 'RM-2026-0003', '3171031102780003', 'Bambang Pamungkas', 'Laki-laki', 'Surabaya', '1978-02-11', 'Jl. Pasar Minggu Km 18, Jakarta Selatan', '085633445566', 'B', 'Tidak Ada'),
(4, 'RM-2026-0004', '3171046812010004', 'Putri Ayu Wandira', 'Perempuan', 'Bogor', '2001-12-28', 'Jl. Mampang Prapatan IV No. 25, Jaksel', '087844556677', 'AB', 'Seafood / Udang'),
(5, 'RM-2026-0005', '3171050505960005', 'Reza Rahardian', 'Laki-laki', 'Semarang', '1996-05-05', 'Jl. Pancoran Timur III No. 5, Jaksel', '089655667788', 'O', 'Tidak Ada'),
(6, 'RM-2026-0006', '3171064201990006', 'Ratna Galih Permata', 'Perempuan', 'Yogyakarta', '1999-01-02', 'Jl. Radio Dalam No. 17, Gandaria Utara', '082266778899', 'B', 'Aspirin'),
(7, 'RM-2026-0007', '3171071407880007', 'Eko Prasetyo', 'Laki-laki', 'Solo', '1988-07-14', 'Jl. Melawai Raya No. 40, Blok M', '081577889900', 'A', 'Tidak Ada'),
(8, 'RM-2026-0008', '3171085910050008', 'Nabila Syakieb', 'Perempuan', 'Medan', '2005-10-19', 'Jl. Kemang Raya No. 62, Bangka', '083888990011', 'O', 'Sulfa');

-- Seed Data Obat
INSERT INTO `obat` (`id`, `kode_obat`, `nama_obat`, `kategori`, `jenis_obat`, `stok`, `satuan`, `harga`, `deskripsi`) VALUES
(1, 'OBT-001', 'Paracetamol 500 mg', 'Analgesik & Antipiretik', 'Tablet', 250, 'Strip', 8500.00, 'Meredakan demam dan nyeri ringan hingga sedang'),
(2, 'OBT-002', 'Amoxicillin 500 mg', 'Antibiotik', 'Kapsul', 180, 'Strip', 14000.00, 'Antibiotik spektrum luas untuk infeksi bakteri'),
(3, 'OBT-003', 'Ambroxol Sirup 15 mg/5ml', 'Mukolitik', 'Sirup', 65, 'Botol', 18500.00, 'Pengencer dahak pada batuk produktif'),
(4, 'OBT-004', 'Antasida Doen', 'Antasida', 'Tablet', 300, 'Strip', 6000.00, 'Mengurangi gejala kelebihan asam lambung dan maag'),
(5, 'OBT-005', 'Cetirizine 10 mg', 'Antihistamin', 'Tablet', 140, 'Strip', 11000.00, 'Meredakan gejala alergi, bersin, dan gatal-gatal'),
(6, 'OBT-006', 'Omeprazole 20 mg', 'Antasida & Refluks', 'Kapsul', 95, 'Strip', 22000.00, 'Penghambat pompa proton untuk tukak lambung dan GERD'),
(7, 'OBT-007', 'Asam Mefenamat 500 mg', 'Analgesik & Anti-inflamasi', 'Tablet', 120, 'Strip', 9500.00, 'Meredakan sakit gigi, sakit kepala, dan nyeri haid'),
(8, 'OBT-008', 'Amlodipine 5 mg', 'Antihipertensi', 'Tablet', 160, 'Strip', 13500.00, 'Menurunkan tekanan darah tinggi'),
(9, 'OBT-009', 'Metformin 500 mg', 'Antidiabetes', 'Tablet', 110, 'Strip', 12000.00, 'Mengontrol kadar gula darah pada pasien diabetes tipe 2'),
(10, 'OBT-010', 'Salbutamol Sirup 2 mg/5ml', 'Bronkodilator', 'Sirup', 45, 'Botol', 24000.00, 'Meredakan sesak napas dan bronkospasme pada asma'),
(11, 'OBT-011', 'Betadine Salep Antiseptik 10g', 'Antiseptik Topikal', 'Salep', 50, 'Tube', 27000.00, 'Mencegah infeksi pada luka bakar dan lecet'),
(12, 'OBT-012', 'Vitamin C 500 mg (IPI)', 'Vitamin & Suplemen', 'Tablet', 200, 'Botol', 9000.00, 'Membantu menjaga daya tahan tubuh');

-- Seed Pendaftaran Pasien (Kunjungan)
INSERT INTO `pendaftaran` (`id`, `nomor_antrean`, `id_pasien`, `id_dokter`, `tanggal_kunjungan`, `keluhan_awal`, `jenis_pasien`, `status`, `created_at`) VALUES
(1, 'A-001', 1, 1, CURDATE(), 'Nyeri ulu hati, mual sudah 3 hari tidak nafsu makan', 'Umum', 'Selesai', NOW() - INTERVAL 4 HOUR),
(2, 'A-002', 2, 3, CURDATE(), 'Demam tinggi naik turun sejak 2 hari, flu dan batuk berdahak', 'BPJS', 'Selesai', NOW() - INTERVAL 3 HOUR),
(3, 'A-003', 3, 1, CURDATE(), 'Pusing berputar, leher kaku, tensi terasa naik', 'Umum', 'Diperiksa', NOW() - INTERVAL 1 HOUR),
(4, 'A-004', 4, 3, CURDATE(), 'Gatal-gatal kemerahan di seluruh lengan setelah makan bakso seafood', 'Umum', 'Menunggu', NOW() - INTERVAL 30 MINUTE),
(5, 'A-005', 5, 1, CURDATE(), 'Pemeriksaan rutin gula darah dan cek tensi berkala', 'BPJS', 'Menunggu', NOW() - INTERVAL 15 MINUTE),
(6, 'A-001', 6, 2, CURDATE() - INTERVAL 1 DAY, 'Anak demam 38.5 C disertai pilek dan batuk kering', 'Umum', 'Selesai', NOW() - INTERVAL 25 HOUR),
(7, 'A-002', 7, 4, CURDATE() - INTERVAL 1 DAY, 'Sakit gigi geraham kanan bawah berdenyut sejak kemarin malam', 'Umum', 'Selesai', NOW() - INTERVAL 24 HOUR),
(8, 'A-003', 8, 1, CURDATE() - INTERVAL 2 DAY, 'Lemas, kepala kliyengan, telapak tangan dingin', 'BPJS', 'Selesai', NOW() - INTERVAL 48 HOUR),
(9, 'A-001', 1, 3, CURDATE() - INTERVAL 3 DAY, 'Batuk pilek dan radang tenggorokan', 'Umum', 'Selesai', NOW() - INTERVAL 72 HOUR),
(10, 'A-002', 3, 5, CURDATE() - INTERVAL 4 DAY, 'Dada terasa berdebar saat kelelahan', 'Asuransi Swasta', 'Selesai', NOW() - INTERVAL 96 HOUR);

-- Seed Rekam Medis (Pemeriksaan)
INSERT INTO `rekam_medis` (`id`, `id_pendaftaran`, `id_pasien`, `id_dokter`, `tanggal_pemeriksaan`, `keluhan`, `tekanan_darah`, `suhu_tubuh`, `berat_badan`, `tinggi_badan`, `diagnosis`, `tindakan`, `catatan_dokter`) VALUES
(1, 1, 1, 1, NOW() - INTERVAL 3 HOUR, 'Nyeri ulu hati seperti terbakar, mual, perut kembung', '120/80', 36.6, 68.0, 172.0, 'Dispepsia / Gastritis Akut (K29.7)', 'Edukasi diet lambung, hindari pedas asam kopi, terapi medikamentosa', 'Kontrol ulang jika nyeri menetap lebih dari 5 hari.'),
(2, 2, 2, 3, NOW() - INTERVAL 2 HOUR, 'Demam suhu 38.2 C, hidung tersumbat, batuk lendir putih kekuningan', '110/70', 38.2, 52.0, 158.0, 'Infeksi Saluran Pernapasan Akut / ISPA (J06.9)', 'Pemberian antipiretik, mukolitik, dan suplemen daya tahan tubuh', 'Istirahat cukup minimal 3 hari, minum air hangat yang banyak.'),
(3, 6, 6, 2, NOW() - INTERVAL 24 HOUR, 'Demam 38.5 C sejak 2 hari, anak rewel dan batuk berdahak', '100/65', 38.5, 48.0, 155.0, 'Faringitis Akut (J02.9)', 'Kompres hangat, nebulizer sederhana, terapi sirup pereda batuk', 'Jika suhu diatas 39 C segera bawa ke IGD.'),
(4, 7, 7, 4, NOW() - INTERVAL 23 HOUR, 'Nyeri hebat pada gigi molar 2 kanan bawah, gusi sedikit bengkak', '130/85', 36.8, 75.0, 168.0, 'Pulpitis Akut reversible molar inferior (K04.0)', 'Pembersihan kavitas sementara, pemberian analgesik & anti-inflamasi', 'Jadwalkan penambalan permanen setelah radang mereda.'),
(5, 8, 8, 1, NOW() - INTERVAL 47 HOUR, 'Lemas berat, pandangan kabur sesaat ketika berdiri tegak', '95/60', 36.4, 46.0, 160.0, 'Hipotensi Ortostatik & Anemia Ringan (I95.1)', 'Pemberian multivitamin zat besi, edukasi hidrasi dan konsumsi garam cukup', 'Periksa lab darah lengkap jika keluhan berulang.');

-- Seed Resep Obat Master
INSERT INTO `resep_obat` (`id`, `id_rekam_medis`, `id_pasien`, `id_dokter`, `tanggal_resep`, `status`, `catatan_resep`) VALUES
(1, 1, 1, 1, NOW() - INTERVAL 3 HOUR, 'Selesai', 'Minum obat lambung 30 menit sebelum makan pagi.'),
(2, 2, 2, 3, NOW() - INTERVAL 2 HOUR, 'Selesai', 'Paracetamol diminum bila demam saja.'),
(3, 3, 6, 2, NOW() - INTERVAL 24 HOUR, 'Selesai', 'Kocok sirup sebelum diminum.'),
(4, 4, 7, 4, NOW() - INTERVAL 23 HOUR, 'Selesai', 'Diminum setelah makan kenyang.');

-- Seed Resep Detail
INSERT INTO `resep_detail` (`id`, `id_resep`, `id_obat`, `dosis`, `aturan_pakai`, `jumlah`, `keterangan`) VALUES
(1, 1, 6, '20 mg', '1 x sehari 1 kapsul (sebelum sarapan)', 1, 'Pagi hari sebelum makan'),
(2, 1, 4, '1 tablet', '3 x sehari 1 tablet dikunyah', 2, 'Sebelum makan / saat perih'),
(3, 2, 1, '500 mg', '3 x sehari 1 tablet', 1, 'Sesudah makan bila demam'),
(4, 2, 3, '15 mg / 5ml', '3 x sehari 1 sendok takar (5 ml)', 1, 'Sesudah makan'),
(5, 2, 12, '500 mg', '1 x sehari 1 tablet', 1, 'Pagi sesudah makan'),
(6, 3, 1, '500 mg', '3 x sehari 1/2 tablet', 1, 'Sesudah makan'),
(7, 3, 3, '15 mg / 5ml', '3 x sehari 1 sendok takar', 1, 'Sesudah makan'),
(8, 4, 7, '500 mg', '3 x sehari 1 tablet', 1, 'Sesudah makan jika nyeri'),
(9, 4, 2, '500 mg', '3 x sehari 1 kapsul dihabiskan', 2, 'Antibiotik wajib habis');

-- Seed Jadwal Kontrol
INSERT INTO `jadwal_kontrol` (`id`, `id_pasien`, `id_dokter`, `id_rekam_medis`, `tanggal_kontrol`, `catatan_kontrol`, `status`) VALUES
(1, 1, 1, 1, CURDATE() + INTERVAL 5 DAY, 'Evaluasi keluhan nyeri ulu hati dan respon terapi obat lambung', 'Terjadwal'),
(2, 7, 4, 4, CURDATE() + INTERVAL 4 DAY, 'Kontrol lanjutan penambalan tetap gigi geraham kanan', 'Terjadwal'),
(3, 3, 1, NULL, CURDATE(), 'Evaluasi tensi berkala dan kadar gula darah puasa', 'Terjadwal'),
(4, 6, 2, 3, CURDATE() - INTERVAL 1 DAY, 'Evaluasi batuk anak dan demam pasca obat', 'Selesai');

SET FOREIGN_KEY_CHECKS = 1;
