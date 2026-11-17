<?php
/**
 * Halaman Riwayat Kunjungan Pasien
 * Fitur: Pencarian riwayat lengkap kunjungan pasien, filter rentang tanggal & dokter, detail kunjungan & tautan ke rekam medis
 */

$page_title = 'Riwayat Kunjungan';
$active_menu = 'kunjungan';

require_once __DIR__ . '/../includes/header.php';

// Filter parameter
$filter_dari = $_GET['dari'] ?? date('Y-m-01');
$filter_sampai = $_GET['sampai'] ?? date('Y-m-d');
$filter_dokter = (int)($_GET['dokter'] ?? 0);
$filter_pasien = (int)($_GET['pasien_id'] ?? 0);
$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT p.*, pas.nomor_rm, pas.nama_pasien, pas.nik, pas.jenis_kelamin, pas.no_telepon, pas.gol_darah,
           d.nama_dokter, d.spesialisasi,
           rm.id AS id_rekam_medis, rm.diagnosis, rm.tindakan, rm.tekanan_darah, rm.suhu_tubuh
    FROM pendaftaran p
    JOIN pasien pas ON p.id_pasien = pas.id
    JOIN dokter d ON p.id_dokter = d.id
    LEFT JOIN rekam_medis rm ON p.id = rm.id_pendaftaran
    WHERE 1=1
";
$params = [];

if (!empty($filter_dari)) {
    $sql .= " AND p.tanggal_kunjungan >= ?";
    $params[] = $filter_dari;
}
if (!empty($filter_sampai)) {
    $sql .= " AND p.tanggal_kunjungan <= ?";
    $params[] = $filter_sampai;
}
if ($filter_dokter > 0) {
    $sql .= " AND p.id_dokter = ?";
    $params[] = $filter_dokter;
}
if ($filter_pasien > 0) {
    $sql .= " AND p.id_pasien = ?";
    $params[] = $filter_pasien;
}
if ($search !== '') {
    $sql .= " AND (pas.nama_pasien LIKE ? OR pas.nomor_rm LIKE ? OR p.nomor_antrean LIKE ?)";
    $param_s = "%{$search}%";
    $params[] = $param_s;
    $params[] = $param_s;
    $params[] = $param_s;
}

$sql .= " ORDER BY p.tanggal_kunjungan DESC, p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$kunjungan_list = $stmt->fetchAll();

// Detail Kunjungan jika diklik parameter ?id=...
$detail_kunjungan = null;
if (isset($_GET['id'])) {
    $detail_id = (int)$_GET['id'];
    $stmt_detail = $pdo->prepare("
        SELECT p.*, pas.nomor_rm, pas.nama_pasien, pas.nik, pas.jenis_kelamin, pas.alamat, pas.tanggal_lahir, pas.no_telepon, pas.alergi,
               d.nama_dokter, d.spesialisasi,
               rm.id AS id_rekam_medis, rm.tanggal_pemeriksaan, rm.keluhan AS keluhan_rm, rm.diagnosis, rm.tindakan, 
               rm.tekanan_darah, rm.suhu_tubuh, rm.berat_badan, rm.tinggi_badan, rm.catatan_dokter
        FROM pendaftaran p
        JOIN pasien pas ON p.id_pasien = pas.id
        JOIN dokter d ON p.id_dokter = d.id
        LEFT JOIN rekam_medis rm ON p.id = rm.id_pendaftaran
        WHERE p.id = ?
    ");
    $stmt_detail->execute([$detail_id]);
    $detail_kunjungan = $stmt_detail->fetch();
}

$dokter_list = $pdo->query("SELECT id, nama_dokter FROM dokter ORDER BY nama_dokter ASC")->fetchAll();
?>

<!-- Header Halaman -->
<div class="page-header">
  <div>
    <h2 class="page-title">Riwayat Kunjungan Pasien</h2>
    <p class="page-subtitle">Arsip seluruh rekam jejak kunjungan, riwayat konsultasi poliklinik, dan hasil diagnosa.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a href="<?= base_url('pages/pendaftaran.php?action=tambah'); ?>" class="btn btn-primary-custom">
      <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
      <span>Pendaftaran Baru</span>
    </a>
  </div>
</div>

<!-- Tampilan Detail Kunjungan (Bila Dipilih) -->
<?php if ($detail_kunjungan): ?>
  <div class="card-custom mb-4 border-primary">
    <div class="card-header-custom bg-light">
      <h3 class="card-header-title text-primary">
        <i data-lucide="file-text" style="width:20px;height:20px;"></i>
        <span>Detail Kunjungan & Rekam Medis (Antrean: <?= e($detail_kunjungan['nomor_antrean']); ?>)</span>
      </h3>
      <div class="d-flex gap-2">
        <?php if ($detail_kunjungan['id_rekam_medis']): ?>
          <a href="<?= base_url('pages/pemeriksaan.php?detail_rm=' . $detail_kunjungan['id_rekam_medis']); ?>" class="btn btn-sm btn-primary">
            <i data-lucide="printer" style="width:14px;height:14px;"></i> Resume Medis
          </a>
        <?php else: ?>
          <a href="<?= base_url('pages/pemeriksaan.php?id_pendaftaran=' . $detail_kunjungan['id']); ?>" class="btn btn-sm btn-success">
            <i data-lucide="stethoscope" style="width:14px;height:14px;"></i> Mulai Pemeriksaan
          </a>
        <?php endif; ?>
        <a href="<?= base_url('pages/kunjungan.php'); ?>" class="btn btn-sm btn-outline-secondary">Tutup</a>
      </div>
    </div>
    <div class="card-body-custom">
      <div class="row g-4">
        <!-- Data Pasien -->
        <div class="col-md-4 border-end">
          <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-1">
            <i data-lucide="user" class="text-primary" style="width:16px;height:16px;"></i> Identitas Pasien
          </h6>
          <div class="mb-2">
            <span class="text-muted small d-block">Nama Lengkap</span>
            <strong class="fs-6"><?= e($detail_kunjungan['nama_pasien']); ?></strong>
          </div>
          <div class="mb-2">
            <span class="text-muted small d-block">Nomor Rekam Medis</span>
            <span class="badge bg-primary text-white font-monospace"><?= e($detail_kunjungan['nomor_rm']); ?></span>
          </div>
          <div class="mb-2">
            <span class="text-muted small d-block">NIK & Kontak</span>
            <span><?= e($detail_kunjungan['nik']); ?> &bull; <?= e($detail_kunjungan['no_telepon']); ?></span>
          </div>
          <div class="mb-2">
            <span class="text-muted small d-block">Alamat Pasien</span>
            <span><?= e($detail_kunjungan['alamat']); ?></span>
          </div>
          <div>
            <span class="text-muted small d-block">Alergi Terdaftar</span>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= e($detail_kunjungan['alergi']); ?></span>
          </div>
        </div>

        <!-- Data Kunjungan -->
        <div class="col-md-4 border-end">
          <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-1">
            <i data-lucide="clipboard" class="text-info" style="width:16px;height:16px;"></i> Informasi Kunjungan
          </h6>
          <div class="mb-2">
            <span class="text-muted small d-block">Tanggal & Waktu Kunjungan</span>
            <strong><?= format_date_id($detail_kunjungan['tanggal_kunjungan'], true); ?></strong>
          </div>
          <div class="mb-2">
            <span class="text-muted small d-block">Dokter Pemeriksa</span>
            <div class="fw-bold"><?= e($detail_kunjungan['nama_dokter']); ?></div>
            <div class="small text-muted"><?= e($detail_kunjungan['spesialisasi']); ?></div>
          </div>
          <div class="mb-2">
            <span class="text-muted small d-block">Jenis Pembayaran & Status</span>
            <span class="badge bg-light text-dark border"><?= e($detail_kunjungan['jenis_pasien']); ?></span>
            <span class="badge bg-success-subtle text-success border border-success-subtle"><?= e($detail_kunjungan['status']); ?></span>
          </div>
          <div>
            <span class="text-muted small d-block">Keluhan Awal Pendaftaran</span>
            <div class="p-2 bg-light rounded text-dark small fst-italic">
              "<?= e($detail_kunjungan['keluhan_awal'] ?: 'Tidak ada keluhan tertulis'); ?>"
            </div>
          </div>
        </div>

        <!-- Hasil Pemeriksaan (Jika Ada) -->
        <div class="col-md-4">
          <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-1">
            <i data-lucide="heart-pulse" class="text-danger" style="width:16px;height:16px;"></i> Rekam Medis Klinis
          </h6>
          <?php if ($detail_kunjungan['id_rekam_medis']): ?>
            <div class="row g-2 small mb-3">
              <div class="col-6 bg-light p-2 rounded text-center">
                <span class="text-muted d-block">Tekanan Darah</span>
                <strong><?= e($detail_kunjungan['tekanan_darah']); ?> mmHg</strong>
              </div>
              <div class="col-6 bg-light p-2 rounded text-center">
                <span class="text-muted d-block">Suhu Tubuh</span>
                <strong><?= e($detail_kunjungan['suhu_tubuh']); ?> &deg;C</strong>
              </div>
              <div class="col-6 bg-light p-2 rounded text-center">
                <span class="text-muted d-block">Berat Badan</span>
                <strong><?= e($detail_kunjungan['berat_badan']); ?> kg</strong>
              </div>
              <div class="col-6 bg-light p-2 rounded text-center">
                <span class="text-muted d-block">Tinggi Badan</span>
                <strong><?= e($detail_kunjungan['tinggi_badan']); ?> cm</strong>
              </div>
            </div>
            <div class="mb-2">
              <span class="text-muted small d-block">Diagnosis Medis:</span>
              <strong class="text-danger"><?= e($detail_kunjungan['diagnosis']); ?></strong>
            </div>
            <div class="mb-2">
              <span class="text-muted small d-block">Tindakan / Terapi:</span>
              <span><?= e($detail_kunjungan['tindakan']); ?></span>
            </div>
            <div>
              <span class="text-muted small d-block">Catatan Dokter:</span>
              <span class="small text-muted"><?= e($detail_kunjungan['catatan_dokter'] ?: '-'); ?></span>
            </div>
          <?php else: ?>
            <div class="alert alert-secondary text-center py-4 my-2">
              <i data-lucide="clock" class="text-muted mb-2" style="width:28px;height:28px;"></i>
              <div class="small fw-semibold text-muted">Pasien ini belum diperiksa oleh dokter.</div>
              <a href="<?= base_url('pages/pemeriksaan.php?id_pendaftaran=' . $detail_kunjungan['id']); ?>" class="btn btn-sm btn-primary mt-2">
                Input Pemeriksaan
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Card Tabel Riwayat Kunjungan -->
<div class="card-custom">
  <div class="card-header-custom flex-wrap gap-3">
    <div>
      <h3 class="card-header-title">
        <i data-lucide="history" class="text-primary" style="width:20px;height:20px;"></i>
        <span>Log Arsip Kunjungan Pasien</span>
      </h3>
      <span class="small text-muted">Menampilkan <?= count($kunjungan_list); ?> total kunjungan</span>
    </div>

    <!-- Filter Multi Parameter -->
    <form action="" method="GET" class="d-flex align-items-center gap-2 flex-wrap ms-auto">
      <div class="d-flex align-items-center gap-1">
        <label class="small text-muted">Dari:</label>
        <input type="date" name="dari" class="form-control form-control-sm" value="<?= e($filter_dari); ?>">
      </div>
      <div class="d-flex align-items-center gap-1">
        <label class="small text-muted">Sampai:</label>
        <input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($filter_sampai); ?>">
      </div>
      <select name="dokter" class="form-select form-select-sm" style="max-width:160px;">
        <option value="0">Semua Dokter</option>
        <?php foreach ($dokter_list as $d): ?>
          <option value="<?= $d['id']; ?>" <?= ($filter_dokter === (int)$d['id']) ? 'selected' : ''; ?>>
            <?= e($d['nama_dokter']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Nama / No RM..." value="<?= e($search); ?>" style="max-width:140px;">
      <button type="submit" class="btn btn-sm btn-primary">
        <i data-lucide="filter" style="width:14px;height:14px;"></i> Terapkan
      </button>
      <a href="<?= base_url('pages/kunjungan.php'); ?>" class="btn btn-sm btn-outline-secondary" title="Reset">Reset</a>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>Tanggal & Antrean</th>
          <th>Identitas Pasien</th>
          <th>Dokter Pemeriksa</th>
          <th>Status Kunjungan</th>
          <th>Hasil Diagnosis</th>
          <th>Tindakan / Terapi</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($kunjungan_list)): ?>
          <?php foreach ($kunjungan_list as $row): ?>
            <tr>
              <td>
                <div class="fw-bold text-dark"><?= format_date_id($row['tanggal_kunjungan']); ?></div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
                  <?= e($row['nomor_antrean']); ?>
                </span>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= e($row['nama_pasien']); ?></div>
                <div class="small text-muted font-monospace"><?= e($row['nomor_rm']); ?> &bull; <?= e($row['jenis_kelamin']); ?></div>
              </td>
              <td>
                <div class="fw-medium text-dark"><?= e($row['nama_dokter']); ?></div>
                <div class="small text-muted"><?= e($row['spesialisasi']); ?></div>
              </td>
              <td>
                <?php if ($row['status'] === 'Selesai'): ?>
                  <span class="badge-custom badge-success-soft">Selesai</span>
                <?php elseif ($row['status'] === 'Diperiksa'): ?>
                  <span class="badge-custom badge-warning-soft">Diperiksa</span>
                <?php elseif ($row['status'] === 'Menunggu'): ?>
                  <span class="badge-custom badge-primary-soft">Menunggu</span>
                <?php else: ?>
                  <span class="badge-custom badge-danger-soft">Batal</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($row['diagnosis'])): ?>
                  <div class="fw-semibold text-danger small"><?= e($row['diagnosis']); ?></div>
                  <div class="small text-muted">Tensi: <?= e($row['tekanan_darah']); ?> mmHg</div>
                <?php else: ?>
                  <span class="text-muted small fst-italic">Belum diperiksa</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="small text-muted text-truncate d-inline-block" style="max-width:220px;" title="<?= e($row['tindakan']); ?>">
                  <?= e($row['tindakan'] ?: '-'); ?>
                </span>
              </td>
              <td class="text-end">
                <a href="<?= base_url('pages/kunjungan.php?id=' . $row['id']); ?>" class="btn-action-icon text-primary" title="Lihat Detail Lengkap">
                  <i data-lucide="eye" style="width:16px;height:16px;"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">Tidak ada data riwayat kunjungan yang sesuai dengan filter.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
