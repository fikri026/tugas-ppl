# Sistem Informasi Rekam Medis Klinik Berbasis Web (PHP Native & MySQL)

Sistem Informasi Rekam Medis Klinik (Electronic Medical Record / EMR) modern, responsif, profesional, dan siap pakai yang dibangun murni menggunakan **PHP Native** dan **MySQL** (PDO Prepared Statements) dengan arsitektur kode yang rapi dan modular.

---

## 🚀 Fitur Unggulan Sistem

1. **Dashboard Manajemen Modern**:
   - Kartu statistik real-time: Total Pasien, Tenaga Medis Aktif, Kunjungan Hari Ini, Total Pemeriksaan Rekam Medis.
   - Grafik interaktif tren kunjungan pasien (Chart.js 7 hari terakhir).
   - Diagram doughnut sebaran jenis pembayaran pasien (Umum, BPJS, Asuransi Swasta).
   - Notifikasi jadwal kontrol pasien hari ini.
   - Tabel 8 kunjungan terbaru dengan status dan aksi cepat.

2. **Data Pasien**:
   - Auto-generate Nomor Rekam Medis resmi berurutan (`RM-YYYY-XXXX`).
   - CRUD data pasien: NIK, nama lengkap, jenis kelamin, tempat tanggal lahir, usia otomatis, kontak WhatsApp, golongan darah, riwayat alergi, dan alamat.
   - Pencarian real-time pasien berdasarkan nama, NIK, dan No. RM.
   - Kartu identitas pasien modal popup.

3. **Data Tenaga Kesehatan (Dokter & Nakes)**:
   - CRUD tenaga medis: NIP, nama dokter & gelar, spesialisasi poliklinik, jadwal praktik kerja, kontak telepon, email, dan status keaktifan (Aktif / Cuti / Nonaktif).

4. **Pendaftaran Pasien & Manajemen Antrean**:
   - Registrasi kunjungan harian ke poliklinik/dokter yang dituju.
   - Nomor antrean otomatis harian (`A-001`, `A-002`, dst).
   - Fitur **Cetak Tiket Antrean Pasien** siap cetak thermal/printer.
   - Pengelolaan status antrean: Menunggu, Diperiksa, Selesai, Dibatalkan.

5. **Riwayat Kunjungan**:
   - Riwayat lengkap kunjungan poliklinik pasien.
   - Filter rentang tanggal kunjungan, filter dokter pemeriksa, dan pencarian pasien.
   - Tampilan detail kunjungan terintegrasi dengan data rekam medis.

6. **Pemeriksaan Rekam Medis (EMR)**:
   - Input tanda-tanda vital lengkap (Tekanan Darah, Suhu Tubuh °C, Berat Badan kg, Tinggi Badan cm).
   - Anamnesis keluhan pasien, diagnosis klinis ICD-10, tindakan/terapi medis, dan catatan instruksi dokter.
   - Otomatis mengubah status antrean kunjungan menjadi **Selesai**.
   - **Cetak Lembar Resume Rekam Medis Rawat Jalan Resmi** lengkap dengan kop klinik dan tanda tangan penanggung jawab pelayanan medis.

7. **Resep dan Manajemen Farmasi Obat**:
   - **Katalog & Stok Obat**: CRUD master obat, satuan (Strip/Botol/Tube), bentuk sediaan (Tablet/Kapsul/Sirup/Salep), harga satuan, dan peringatan stok menipis otomatis.
   - **Pembuatan Resep Elektronik**: Mendukung resep multi-obat dinamis dengan dosis dan aturan pakai (Signa).
   - **Pemotongan Stok Obat Otomatis** saat resep diterbitkan.
   - **Cetak Salinan Resep Dokter Resmi (R/)**.

8. **Jadwal Kontrol Pasien**:
   - Pembuatan jadwal kontrol ulang pasca rawat jalan.
   - Notifikasi pasien yang wajib kontrol pada hari ini.
   - Tautan langsung pengingat WhatsApp ke nomor pasien.
   - Status kontrol: Terjadwal, Selesai, Dibatalkan, Terlewat.

9. **Laporan Pelayanan Klinik**:
   - Laporan Kunjungan Pasien Rawat Jalan.
   - Laporan Rekam Medis & Diagnosis Penyakit.
   - Laporan Konsumsi & Peresepan Obat Farmasi.
   - Filter periode rentang tanggal bebas (Dari - Sampai).
   - Ringkasan metrik statistik laporan.
   - **Cetak Laporan Resmi** berstandar dokumen dengan format kop klinik dan tanda tangan penanggung jawab.

10. **Pengaturan Sistem & Keamanan**:
    - Konfigurasi profil klinik (Nama, Slogan, Alamat, Hotline, Email, Website, Pimpinan Klinik, Jam Operasional).
    - Manajemen profil akun pengguna yang sedang login.
    - Ubah password akun dengan validasi password lama dan enkripsi `password_hash()` (Bcrypt).
    - Keamanan autentikasi sesi (`$_SESSION`), proteksi seluruh halaman internal, dan prepared statements terhadap SQL Injection & XSS.

---

## 🛠️ Teknologi yang Digunakan

- **Bahasa Utama**: PHP 8.0+ Native (Object-Oriented PDO)
- **Database**: MySQL / MariaDB (Prepared Statements & Foreign Key Constraints)
- **Styling**: CSS3 Custom Modern Healthcare Design System & Bootstrap 5.3
- **Ikonografi**: Lucide Icons
- **Visualisasi Data**: Chart.js
- **Interaktivitas**: Vanilla JavaScript (ES6+)
- **Autentikasi**: PHP Native Session & `password_verify` Bcrypt

---

## 📁 Struktur Folder Project

```
rekam-medis-klinik/
├── config/
│   └── database.php           # Koneksi PDO MySQL & Fungsi Helper Global
├── includes/
│   ├── header.php             # Head HTML, CSS CDN, Session Guard, Flash Alert
│   ├── sidebar.php            # 10 Menu Navigasi Utama dengan Lucide Icons
│   ├── navbar.php             # Jam Digital Real-time, Quick Action, Profil Dropdown
│   └── footer.php             # Modal Universal Hapus, Script JS, Tag Penutup
├── pages/
│   ├── dashboard.php          # Dashboard Metrik, Chart.js, Tabel Kunjungan
│   ├── pasien.php             # CRUD Pasien, No RM Otomatis, Detail Identitas
│   ├── tenaga-kesehatan.php   # CRUD Dokter & Nakes, Jadwal Praktik
│   ├── pendaftaran.php        # Kunjungan, No Antrean Otomatis, Cetak Tiket
│   ├── kunjungan.php          # Log Arsip Kunjungan & Filter Multi Parameter
│   ├── pemeriksaan.php        # Pemeriksaan Tanda Vital, Diagnosa, Cetak Resume
│   ├── resep-obat.php         # Master Obat, Resep Multi-item, Potong Stok, Cetak R/
│   ├── jadwal-kontrol.php     # Agenda Kontrol Pasien, WhatsApp Reminder
│   ├── laporan.php            # Laporan Pelayanan & Cetak Berstandar Resmi
│   └── pengaturan.php         # Profil Klinik, Akun Saya, Ubah Password
├── assets/
│   ├── css/
│   │   └── style.css          # Tema Modern Medical Blue & Mint Green
│   ├── js/
│   │   ├── script.js          # Jam Live, Toggle Mobile, Filter Tabel Realtime
│   │   └── app.js             # Modal Handler, Auto Age, Baris Resep Dinamis
│   └── images/                # Aset Gambar & Logo
├── database/
│   └── rekam_medis.sql        # Skema SQL Lengkap + Data Dummy Demonstrasi
├── index.php                  # Redirector Berdasarkan Status Login
├── login.php                  # Halaman Login Modern + Demo Account Helper
├── logout.php                 # Pembersihan Sesi Aman & Redirect
├── .gitignore
└── README.md
```

---

## 💻 Panduan Instalasi & Menjalankan di XAMPP

1. **Jalankan XAMPP Control Panel**:
   - Aktifkan modul **Apache** dan **MySQL**.

2. **Letakkan Folder Project**:
   - Tempatkan folder `rekam-medis-klinik` di dalam folder `htdocs` XAMPP Anda:
     ```
     C:\xampp\htdocs\rekam-medis-klinik
     ```

3. **Import Database**:
   - Buka browser dan akses **phpMyAdmin**: `http://localhost/phpmyadmin/`
   - Buat database baru bernama `rekam_medis` (atau langsung import file SQL).
   - Klik tab **Import**, pilih file:
     ```
     database/rekam_medis.sql
     ```
   - Klik tombol **Import / Kirim** di bagian bawah.

4. **Akses Website di Browser**:
   - Buka browser Anda dan navigasikan ke:
     ```
     http://localhost/rekam-medis-klinik/
     ```

---

## 🔑 Akun Login Demonstrasi

Sistem telah dilengkapi data dummy siap uji untuk berbagai peran:

| Role | Username | Password | Deskripsi |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Akses penuh ke seluruh fitur dan pengaturan klinik |
| **Dokter** | `dr_budi` | `admin123` | dr. Budi Santoso, Sp.PD (Spesialis Penyakit Dalam) |
| **Petugas RM** | `petugas1` | `admin123` | Anisa Triana, A.Md.RMIK (Perekam Medis) |

*(Halaman login juga dilengkapi tombol klik otomatis untuk mengisi akun demo di atas secara instan).*
