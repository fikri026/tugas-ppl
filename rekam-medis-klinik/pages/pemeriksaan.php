<?php
/**
 * Halaman Pemeriksaan Rekam Medis Pasien
 * Fitur: Form input rekam medis klinis (tanda vital, keluhan, diagnosis, tindakan), cetak resume medis, riwayat pemeriksaan
 */

$page_title = 'Pemeriksaan Rekam Medis';
$active_menu = 'pemeriksaan';

require_once __DIR__ . '/../includes/header.php';

// 1. Simpan Data Pemeriksaan Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_rm') {
    $id_pendaftaran = (int)($_POST['id_pendaftaran'] ?? 0);
    $id_pasien = (int)($_POST['id_pasien'] ?? 0);
    $id_dokter = (int)($_POST['id_dokter'] ?? 0);
    $keluhan = trim($_POST['keluhan'] ?? '');
    $tekanan_darah = trim($_POST['tekanan_darah'] ?? '-');
    $suhu_tubuh = !empty($_POST['suhu_tubuh']) ? (float)$_POST['suhu_tubuh'] : null;
    $berat_badan = !empty($_POST['berat_badan']) ? (float)$_POST['berat_badan'] : null;
    $tinggi_badan = !empty($_POST['tinggi_badan']) ? (float)$_POST['tinggi_badan'] : null;
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $tindakan = trim($_POST['tindakan'] ?? '');
    $catatan_dokter = trim($_POST['catatan_dokter'] ?? '');

    if ($id_pendaftaran <= 0 || empty($diagnosis) || empty($tindakan)) {
        set_flash('danger', 'Diagnosis klinis dan tindakan medis wajib diisi!');
    } else {
        // Cek apakah pendaftaran ini sudah memiliki rekam medis
        $check = $pdo->prepare("SELECT id FROM rekam_medis WHERE id_pendaftaran = ?");
        $check->execute([$id_pendaftaran]);
        $existing = $check->fetch();

        if ($existing) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE rekam_medis 
                SET keluhan = ?, tekanan_darah = ?, suhu_tubuh = ?, berat_badan = ?, tinggi_badan = ?, diagnosis = ?, tindakan = ?, catatan_dokter = ?
                WHERE id = ?
            ");
            $stmt->execute([$keluhan, $tekanan_darah, $suhu_tubuh, $berat_badan, $tinggi_badan, $diagnosis, $tindakan, $catatan_dokter, $existing['id']]);
            $rm_id = $existing['id'];
        } else {
            // Insert baru
            $stmt = $pdo->prepare("
                INSERT INTO rekam_medis (id_pendaftaran, id_pasien, id_dokter, keluhan, tekanan_darah, suhu_tubuh, berat_badan, tinggi_badan, diagnosis, tindakan, catatan_dokter)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_pendaftaran, $id_pasien, $id_dokter, $keluhan, $tekanan_darah, $suhu_tubuh, $berat_badan, $tinggi_badan, $diagnosis, $tindakan, $catatan_dokter]);
            $rm_id = $pdo->lastInsertId();
        }

        // Update status pendaftaran menjadi 'Selesai'
        $update_status = $pdo->prepare("UPDATE pendaftaran SET status = 'Selesai' WHERE id = ?");
        $update_status->execute([$id_pendaftaran]);

        set_flash('success', "Pemeriksaan rekam medis berhasil disimpan! Status kunjungan pasien telah diset Selesai. <a href='pemeriksaan.php?detail_rm={$rm_id}' class='btn btn-xs btn-outline-primary ms-2'>Lihat Resume Medis</a>");
        header('Location: ' . base_url('pages/pemeriksaan.php'));
        exit;
    }
}

// 2. Ambil data pasien antrean jika diarahkan dari halaman pendaftaran / dashboard
$id_pendaftaran_pilih = (int)($_GET['id_pendaftaran'] ?? 0);
$pasien_terpilih = null;
if ($id_pendaftaran_pilih > 0) {
    $stmt_pilih = $pdo->prepare("
        SELECT p.*, pas.nomor_rm, pas.nama_pasien, pas.nik, pas.jenis_kelamin, pas.tanggal_lahir, pas.alergi,
               d.nama_dokter, d.spesialisasi
        FROM pendaftaran p
        JOIN pasien pas ON p.id_pasien = pas.id
        JOIN dokter d ON p.id_dokter = d.id
        WHERE p.id = ?
    ");
    $stmt_pilih->execute([$id_pendaftaran_pilih]);
    $pasien_terpilih = $stmt_pilih->fetch();
}

// 3. Detail Resume Medis untuk Cetak
$detail_rm_id = (int)($_GET['detail_rm'] ?? 0);
$resume_medis = null;
if ($detail_rm_id > 0) {
    $stmt_rm = $pdo->prepare("
        SELECT rm.*, p.nomor_antrean, p.tanggal_kunjungan, p.jenis_pasien,
               pas.nomor_rm, pas.nama_pasien, pas.nik, pas.jenis_kelamin, pas.tanggal_lahir, pas.alamat, pas.no_telepon, pas.gol_darah, pas.alergi,
               d.nama_dokter, d.nip, d.spesialisasi
        FROM rekam_medis rm
        JOIN pendaftaran p ON rm.id_pendaftaran = p.id
        JOIN pasien pas ON rm.id_pasien = pas.id
        JOIN dokter d ON rm.id_dokter = d.id
        WHERE rm.id = ?
    ");
    $stmt_rm->execute([$detail_rm_id]);
    $resume_medis = $stmt_rm->fetch();
}

// 4. Riwayat Pemeriksaan Rekam Medis
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt_riwayat = $pdo->prepare("
        SELECT rm.*, p.nomor_antrean, p.tanggal_kunjungan, pas.nomor_rm, pas.nama_pasien, d.nama_dokter
        FROM rekam_medis rm
        JOIN pendaftaran p ON rm.id_pendaftaran = p.id
        JOIN pasien pas ON rm.id_pasien = pas.id
        JOIN dokter d ON rm.id_dokter = d.id
        WHERE pas.nama_pasien LIKE ? OR pas.nomor_rm LIKE ? OR rm.diagnosis LIKE ?
        ORDER BY rm.id DESC
    ");
    $param_s = "%{$search}%";
    $stmt_riwayat->execute([$param_s, $param_s, $param_s]);
} else {
    $stmt_riwayat = $pdo->query("
        SELECT rm.*, p.nomor_antrean, p.tanggal_kunjungan, pas.nomor_rm, pas.nama_pasien, d.nama_dokter
        FROM rekam_medis rm
        JOIN pendaftaran p ON rm.id_pendaftaran = p.id
        JOIN pasien pas ON rm.id_pasien = pas.id
        JOIN dokter d ON rm.id_dokter = d.id
        ORDER BY rm.id DESC
    ");
}
$riwayat_rm = $stmt_riwayat->fetchAll();

// Daftar Antrean Pasien yang Menunggu Periksa Hari Ini
$antrean_menunggu = $pdo->query("
    SELECT p.*, pas.nomor_rm, pas.nama_pasien, d.nama_dokter
    FROM pendaftaran p
    JOIN pasien pas ON p.id_pasien = pas.id
    JOIN dokter d ON p.id_dokter = d.id
    WHERE p.status IN ('Menunggu', 'Diperiksa')
    ORDER BY p.tanggal_kunjungan ASC, p.id ASC
")->fetchAll();
?>

<!-- Header Halaman -->
<div class="page-header">
  <div>
    <h2 class="page-title">Pemeriksaan Rekam Medis</h2>
    <p class="page-subtitle">Input tanda-tanda vital klinis, anamnesis keluhan, diagnosa dokter, dan resume medis.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <?php if ($antrean_menunggu): ?>
      <button class="btn btn-outline-primary d-flex align-items-center gap-1" data-bs-toggle="collapse" data-bs-target="#boxAntreanMenunggu">
        <i data-lucide="users" style="width:16px;height:16px;"></i>
        <span>Antrean Siap Diperiksa (<?= count($antrean_menunggu); ?>)</span>
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Kotak Antrean Pasien Menunggu (Collapsible) -->
<?php if ($antrean_menunggu): ?>
  <div class="collapse <?= ($id_pendaftaran_pilih == 0) ? 'show' : ''; ?> mb-4" id="boxAntreanMenunggu">
    <div class="card-custom border-primary">
      <div class="card-header-custom bg-light">
        <h4 class="card-header-title text-primary">
          <i data-lucide="bell" style="width:18px;height:18px;"></i>
          <span>Pasien Menunggu Pemeriksaan Dokter</span>
        </h4>
        <span class="small text-muted">Pilih pasien untuk memulai input rekam medis</span>
      </div>
      <div class="card-body-custom p-0">
        <div class="table-responsive">
          <table class="table-custom mb-0">
            <thead>
              <tr>
                <th>No. Antrean</th>
                <th>Tgl Kunjungan</th>
                <th>Pasien & No. RM</th>
                <th>Dokter</th>
                <th>Keluhan Pasien</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($antrean_menunggu as $am): ?>
                <tr>
                  <td>
                    <span class="badge bg-primary text-white font-monospace fs-6"><?= e($am['nomor_antrean']); ?></span>
                  </td>
                  <td><?= format_date_id($am['tanggal_kunjungan']); ?></td>
                  <td>
                    <strong><?= e($am['nama_pasien']); ?></strong>
                    <div class="small text-muted font-monospace"><?= e($am['nomor_rm']); ?></div>
                  </td>
                  <td><?= e($am['nama_dokter']); ?></td>
                  <td>
                    <span class="small text-muted"><?= e($am['keluhan_awal'] ?: '-'); ?></span>
                  </td>
                  <td class="text-end">
                    <a href="<?= base_url('pages/pemeriksaan.php?id_pendaftaran=' . $am['id']); ?>" class="btn btn-sm btn-success px-3">
                      <i data-lucide="stethoscope" style="width:14px;height:14px;"></i> Periksa Sekarang
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- FORM INPUT PEMERIKSAAN MEDIS (Jika Pasien Dipilih) -->
<?php if ($pasien_terpilih): ?>
  <div class="card-custom mb-4 border-success shadow-lg">
    <div class="card-header-custom bg-success text-white">
      <div class="d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" style="width:22px;height:22px;"></i>
        <h4 class="card-header-title text-white mb-0">Formulir Rekam Medis Pasien: <?= e($pasien_terpilih['nama_pasien']); ?></h4>
      </div>
      <a href="<?= base_url('pages/pemeriksaan.php'); ?>" class="btn btn-sm btn-light">Tutup Form</a>
    </div>
    <div class="card-body-custom p-4">
      <!-- Info Singkat Pasien -->
      <div class="row g-3 p-3 bg-light rounded-3 mb-4 border">
        <div class="col-md-3">
          <span class="text-muted small d-block">Nomor Rekam Medis:</span>
          <span class="font-monospace fw-bold fs-6 text-primary"><?= e($pasien_terpilih['nomor_rm']); ?></span>
        </div>
        <div class="col-md-3">
          <span class="text-muted small d-block">Nama Pasien:</span>
          <strong><?= e($pasien_terpilih['nama_pasien']); ?></strong> (<?= e($pasien_terpilih['jenis_kelamin']); ?>)
        </div>
        <div class="col-md-3">
          <span class="text-muted small d-block">Dokter Pemeriksa:</span>
          <span><?= e($pasien_terpilih['nama_dokter']); ?></span>
        </div>
        <div class="col-md-3">
          <span class="text-muted small d-block">Riwayat Alergi:</span>
          <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= e($pasien_terpilih['alergi']); ?></span>
        </div>
      </div>

      <!-- Form Post Rekam Medis -->
      <form action="" method="POST">
        <input type="hidden" name="action" value="simpan_rm">
        <input type="hidden" name="id_pendaftaran" value="<?= $pasien_terpilih['id']; ?>">
        <input type="hidden" name="id_pasien" value="<?= $pasien_terpilih['id_pasien']; ?>">
        <input type="hidden" name="id_dokter" value="<?= $pasien_terpilih['id_dokter']; ?>">

        <!-- Bagian Tanda Vital -->
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
          <i data-lucide="activity" class="text-danger" style="width:18px;height:18px;"></i>
          <span>1. Tanda-Tanda Vital & Pengukuran Fisik</span>
        </h6>
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <label class="form-label form-label-custom">Tekanan Darah (mmHg)</label>
            <input type="text" name="tekanan_darah" class="form-control form-control-custom" placeholder="Contoh: 120/80" value="120/80">
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-custom">Suhu Tubuh (&deg;C)</label>
            <input type="number" step="0.1" name="suhu_tubuh" class="form-control form-control-custom" placeholder="Contoh: 36.5" value="36.5">
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-custom">Berat Badan (kg)</label>
            <input type="number" step="0.1" name="berat_badan" class="form-control form-control-custom" placeholder="Contoh: 65.0">
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-custom">Tinggi Badan (cm)</label>
            <input type="number" step="0.1" name="tinggi_badan" class="form-control form-control-custom" placeholder="Contoh: 170.0">
          </div>
        </div>

        <!-- Bagian Anamnesis & Diagnosa -->
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
          <i data-lucide="file-heart" class="text-primary" style="width:18px;height:18px;"></i>
          <span>2. Anamnesis, Diagnosis & Tindakan Medis</span>
        </h6>
        <div class="row g-3 mb-4">
          <div class="col-md-12">
            <label class="form-label form-label-custom">Anamnesis / Keluhan Lengkap Pasien *</label>
            <textarea name="keluhan" class="form-control form-control-custom" rows="2" placeholder="Keluhan utama, riwayat penyakit sekarang, lama gejala dirasakan..." required><?= e($pasien_terpilih['keluhan_awal']); ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label form-label-custom">Diagnosis Klinis (ICD-10) *</label>
            <input type="text" name="diagnosis" class="form-control form-control-custom" placeholder="Contoh: Dispepsia / Gastritis Akut (K29.7)" required>
          </div>
          <div class="col-md-6">
            <label class="form-label form-label-custom">Tindakan Medis / Rencana Terapi *</label>
            <input type="text" name="tindakan" class="form-control form-control-custom" placeholder="Edukasi diet lambung, medikamentosa, nebulizer..." required>
          </div>
          <div class="col-12">
            <label class="form-label form-label-custom">Catatan / Instruksi Dokter untuk Pasien</label>
            <textarea name="catatan_dokter" class="form-control form-control-custom" rows="2" placeholder="Anjuran istirahat, pantangan makanan, atau pesan khusus bagi pasien..."></textarea>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 border-top pt-3">
          <a href="<?= base_url('pages/pemeriksaan.php'); ?>" class="btn btn-outline-secondary">Batal</a>
          <button type="submit" class="btn btn-success-custom">
            <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
            <span>Simpan Rekam Medis Pasien</span>
          </button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- TAMPILAN RESUME MEDIS RESMI (Print View) -->
<?php if ($resume_medis): ?>
  <div class="card-custom mb-4 border-primary">
    <div class="card-header-custom bg-light">
      <div class="d-flex align-items-center gap-2">
        <i data-lucide="printer" class="text-primary" style="width:20px;height:20px;"></i>
        <h4 class="card-header-title text-primary mb-0">Lembar Resume Rekam Medis Elektronik</h4>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm" onclick="printDocument()">
          <i data-lucide="printer" style="width:14px;height:14px;"></i> Cetak Resume Medis
        </button>
        <a href="<?= base_url('pages/resep-obat.php?rm_id=' . $resume_medis['id']); ?>" class="btn btn-success btn-sm">
          <i data-lucide="pill" style="width:14px;height:14px;"></i> Buat Resep Obat
        </a>
        <a href="<?= base_url('pages/jadwal-kontrol.php?pasien_id=' . $resume_medis['id_pasien'] . '&rm_id=' . $resume_medis['id']); ?>" class="btn btn-info btn-sm text-white">
          <i data-lucide="calendar" style="width:14px;height:14px;"></i> Atur Jadwal Kontrol
        </a>
        <a href="<?= base_url('pages/pemeriksaan.php'); ?>" class="btn btn-outline-secondary btn-sm">Tutup</a>
      </div>
    </div>
    <div class="card-body-custom p-4">
      <!-- Kop Klinik Resmi -->
      <div class="print-header text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold mb-1 text-primary"><?= e($clinic['nama_klinik']); ?></h3>
        <p class="text-muted small mb-1"><?= e($clinic['alamat_klinik']); ?> &bull; Telp: <?= e($clinic['no_telepon']); ?> &bull; Email: <?= e($clinic['email_klinik']); ?></p>
        <div class="fw-bold text-uppercase border-top pt-2 mt-2" style="letter-spacing:1px;">RESUME PELAYANAN REKAM MEDIS RAWAT JALAN</div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-6">
          <table class="table table-sm table-borderless small mb-0">
            <tr>
              <td class="text-muted" style="width:140px;">No. Rekam Medis</td>
              <td>: <strong class="font-monospace text-primary"><?= e($resume_medis['nomor_rm']); ?></strong></td>
            </tr>
            <tr>
              <td class="text-muted">Nama Pasien</td>
              <td>: <strong><?= e($resume_medis['nama_pasien']); ?></strong></td>
            </tr>
            <tr>
              <td class="text-muted">NIK KTP</td>
              <td>: <?= e($resume_medis['nik']); ?></td>
            </tr>
            <tr>
              <td class="text-muted">Jenis Kelamin / Gol Darah</td>
              <td>: <?= e($resume_medis['jenis_kelamin']); ?> / <?= e($resume_medis['gol_darah']); ?></td>
            </tr>
            <tr>
              <td class="text-muted">Alamat Pasien</td>
              <td>: <?= e($resume_medis['alamat']); ?></td>
            </tr>
          </table>
        </div>
        <div class="col-6">
          <table class="table table-sm table-borderless small mb-0">
            <tr>
              <td class="text-muted" style="width:140px;">Tanggal Periksa</td>
              <td>: <?= format_date_id($resume_medis['tanggal_pemeriksaan'], true); ?></td>
            </tr>
            <tr>
              <td class="text-muted">Dokter Pemeriksa</td>
              <td>: <strong><?= e($resume_medis['nama_dokter']); ?></strong></td>
            </tr>
            <tr>
              <td class="text-muted">NIP Dokter</td>
              <td>: <?= e($resume_medis['nip']); ?></td>
            </tr>
            <tr>
              <td class="text-muted">Poliklinik / Spesialis</td>
              <td>: <?= e($resume_medis['spesialisasi']); ?></td>
            </tr>
            <tr>
              <td class="text-muted">Riwayat Alergi</td>
              <td>: <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= e($resume_medis['alergi']); ?></span></td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Tanda Vital -->
      <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">Tanda-Tanda Vital</h6>
      <div class="row g-2 mb-4 text-center small">
        <div class="col-3 p-2 bg-light border rounded">
          <span class="text-muted d-block">Tekanan Darah</span>
          <strong class="fs-6"><?= e($resume_medis['tekanan_darah']); ?> mmHg</strong>
        </div>
        <div class="col-3 p-2 bg-light border rounded">
          <span class="text-muted d-block">Suhu Tubuh</span>
          <strong class="fs-6"><?= e($resume_medis['suhu_tubuh']); ?> &deg;C</strong>
        </div>
        <div class="col-3 p-2 bg-light border rounded">
          <span class="text-muted d-block">Berat Badan</span>
          <strong class="fs-6"><?= e($resume_medis['berat_badan'] ?: '-'); ?> kg</strong>
        </div>
        <div class="col-3 p-2 bg-light border rounded">
          <span class="text-muted d-block">Tinggi Badan</span>
          <strong class="fs-6"><?= e($resume_medis['tinggi_badan'] ?: '-'); ?> cm</strong>
        </div>
      </div>

      <!-- Anamnesis & Diagnosa -->
      <div class="mb-3">
        <h6 class="fw-bold text-dark mb-1">Anamnesis / Keluhan Pasien:</h6>
        <div class="p-2 bg-light rounded text-dark small"><?= nl2br(e($resume_medis['keluhan'])); ?></div>
      </div>

      <div class="mb-3">
        <h6 class="fw-bold text-dark mb-1">Diagnosis Medis:</h6>
        <div class="p-2 bg-danger-subtle border border-danger-subtle rounded text-danger fw-bold"><?= e($resume_medis['diagnosis']); ?></div>
      </div>

      <div class="mb-3">
        <h6 class="fw-bold text-dark mb-1">Tindakan / Terapi Medis:</h6>
        <div class="p-2 bg-light rounded text-dark small"><?= e($resume_medis['tindakan']); ?></div>
      </div>

      <div class="mb-4">
        <h6 class="fw-bold text-dark mb-1">Instruksi Dokter:</h6>
        <div class="p-2 bg-light rounded text-dark small"><?= e($resume_medis['catatan_dokter'] ?: 'Istirahat teratur dan konsumsi obat sesuai aturan pakai.'); ?></div>
      </div>

      <!-- Tanda Tangan -->
      <div class="row pt-4 mt-4 border-top">
        <div class="col-6 text-center">
          <div class="small text-muted mb-5">Pasien / Keluarga Pasien</div>
          <strong>( <?= e($resume_medis['nama_pasien']); ?> )</strong>
        </div>
        <div class="col-6 text-center">
          <div class="small text-muted mb-5"><?= format_date_id(date('Y-m-d')); ?><br>Dokter Penanggung Jawab Pelayanan,</div>
          <strong>( <?= e($resume_medis['nama_dokter']); ?> )</strong>
          <div class="small text-muted">NIP. <?= e($resume_medis['nip']); ?></div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- CARD TABEL RIWAYAT PEMERIKSAAN MEDIS -->
<div class="card-custom">
  <div class="card-header-custom flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <h3 class="card-header-title">
        <i data-lucide="folder-clock" class="text-primary" style="width:20px;height:20px;"></i>
        <span>Riwayat Seluruh Pemeriksaan Rekam Medis</span>
      </h3>
      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
        <?= count($riwayat_rm); ?> Berkas RM
      </span>
    </div>

    <!-- Search Form -->
    <div class="d-flex align-items-center gap-2 ms-auto">
      <form action="" method="GET" class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm" style="width: 280px;">
          <input type="text" name="q" class="form-control" placeholder="Cari Pasien, No RM, Diagnosis..." value="<?= e($search); ?>">
          <button class="btn btn-primary" type="submit">
            <i data-lucide="search" style="width:16px;height:16px;"></i>
          </button>
        </div>
        <?php if ($search !== ''): ?>
          <a href="<?= base_url('pages/pemeriksaan.php'); ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>Tanggal & Jam</th>
          <th>No. RM & Nama Pasien</th>
          <th>Dokter Pemeriksa</th>
          <th>Tanda Vital</th>
          <th>Diagnosis Utama</th>
          <th>Tindakan / Terapi</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($riwayat_rm)): ?>
          <?php foreach ($riwayat_rm as $row): ?>
            <tr>
              <td>
                <div class="fw-semibold text-dark"><?= format_date_id($row['tanggal_pemeriksaan']); ?></div>
                <div class="small text-muted font-monospace"><?= date('H:i', strtotime($row['tanggal_pemeriksaan'])); ?> WIB</div>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= e($row['nama_pasien']); ?></div>
                <span class="font-monospace text-primary small fw-bold"><?= e($row['nomor_rm']); ?></span>
              </td>
              <td>
                <div class="fw-medium text-dark small"><?= e($row['nama_dokter']); ?></div>
              </td>
              <td>
                <span class="badge bg-light text-dark border small">
                  <?= e($row['tekanan_darah']); ?> mmHg &bull; <?= e($row['suhu_tubuh']); ?>&deg;C
                </span>
              </td>
              <td>
                <span class="fw-bold text-danger small"><?= e($row['diagnosis']); ?></span>
              </td>
              <td>
                <span class="small text-muted text-truncate d-inline-block" style="max-width:200px;" title="<?= e($row['tindakan']); ?>">
                  <?= e($row['tindakan']); ?>
                </span>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <!-- Cetak / Lihat Resume Medis -->
                  <a href="<?= base_url('pages/pemeriksaan.php?detail_rm=' . $row['id']); ?>" class="btn-action-icon text-primary" title="Lihat Resume Medis">
                    <i data-lucide="file-text" style="width:16px;height:16px;"></i>
                  </a>
                  <!-- Buat Resep Obat -->
                  <a href="<?= base_url('pages/resep-obat.php?rm_id=' . $row['id']); ?>" class="btn-action-icon text-success" title="Resep Obat">
                    <i data-lucide="pill" style="width:16px;height:16px;"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">Belum ada catatan pemeriksaan rekam medis.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
