<?php
/**
 * Halaman Pendaftaran Kunjungan Pasien
 * Fitur: Pendaftaran antrean baru, pemilihan pasien & dokter, nomor antrean otomatis, ubah status, cetak tiket antrean
 */

$page_title = 'Pendaftaran Pasien';
$active_menu = 'pendaftaran';

require_once __DIR__ . '/../includes/header.php';

// 1. Tambah Pendaftaran Kunjungan Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $id_pasien = (int)($_POST['id_pasien'] ?? 0);
    $id_dokter = (int)($_POST['id_dokter'] ?? 0);
    $tanggal_kunjungan = $_POST['tanggal_kunjungan'] ?? date('Y-m-d');
    $jenis_pasien = $_POST['jenis_pasien'] ?? 'Umum';
    $keluhan_awal = trim($_POST['keluhan_awal'] ?? '');

    if ($id_pasien <= 0 || $id_dokter <= 0 || empty($tanggal_kunjungan)) {
        set_flash('danger', 'Silakan pilih pasien, dokter pemeriksa, dan tanggal kunjungan!');
    } else {
        // Generate nomor antrean otomatis untuk tanggal tersebut
        $nomor_antrean = generate_nomor_antrean($pdo, $tanggal_kunjungan);

        $stmt = $pdo->prepare("
            INSERT INTO pendaftaran (nomor_antrean, id_pasien, id_dokter, tanggal_kunjungan, keluhan_awal, jenis_pasien, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Menunggu')
        ");
        $stmt->execute([$nomor_antrean, $id_pasien, $id_dokter, $tanggal_kunjungan, $keluhan_awal, $jenis_pasien]);
        $new_id = $pdo->lastInsertId();

        set_flash('success', "Pendaftaran berhasil! Nomor antrean pasien adalah <strong>{$nomor_antrean}</strong>. <a href='pendaftaran.php?cetak_tiket={$new_id}' target='_blank' class='btn btn-xs btn-outline-primary ms-2'>Cetak Tiket Antrean</a>");
        header('Location: ' . base_url('pages/pendaftaran.php'));
        exit;
    }
}

// 2. Ubah Status Kunjungan Cepat
if (isset($_GET['action']) && $_GET['action'] === 'status' && isset($_GET['id']) && isset($_GET['set'])) {
    $id = (int)$_GET['id'];
    $new_status = $_GET['set'];
    $allowed = ['Menunggu', 'Diperiksa', 'Selesai', 'Dibatalkan'];

    if (in_array($new_status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE pendaftaran SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        set_flash('success', "Status antrean #{$id} berhasil diubah menjadi <strong>{$new_status}</strong>.");
    }
    header('Location: ' . base_url('pages/pendaftaran.php'));
    exit;
}

// 3. Hapus Pendaftaran
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM pendaftaran WHERE id = ?");
    $stmt->execute([$id]);
    set_flash('success', 'Data pendaftaran antrean berhasil dihapus!');
    header('Location: ' . base_url('pages/pendaftaran.php'));
    exit;
}

// 4. Modal Cetak Tiket Antrean Khusus
$cetak_id = (int)($_GET['cetak_tiket'] ?? 0);
$tiket_data = null;
if ($cetak_id > 0) {
    $stmt_tiket = $pdo->prepare("
        SELECT p.*, pas.nomor_rm, pas.nama_pasien, pas.nik, d.nama_dokter, d.spesialisasi
        FROM pendaftaran p
        JOIN pasien pas ON p.id_pasien = pas.id
        JOIN dokter d ON p.id_dokter = d.id
        WHERE p.id = ?
    ");
    $stmt_tiket->execute([$cetak_id]);
    $tiket_data = $stmt_tiket->fetch();
}

// 5. Filter Tanggal & Data Pendaftaran
$filter_tgl = $_GET['tgl'] ?? date('Y-m-d');
$filter_status = $_GET['status'] ?? '';

$sql = "
    SELECT p.*, pas.nomor_rm, pas.nama_pasien, pas.jenis_kelamin, pas.no_telepon, d.nama_dokter, d.spesialisasi
    FROM pendaftaran p
    JOIN pasien pas ON p.id_pasien = pas.id
    JOIN dokter d ON p.id_dokter = d.id
    WHERE 1=1
";
$params = [];

if ($filter_tgl !== '') {
    $sql .= " AND p.tanggal_kunjungan = ?";
    $params[] = $filter_tgl;
}
if ($filter_status !== '') {
    $sql .= " AND p.status = ?";
    $params[] = $filter_status;
}
$sql .= " ORDER BY p.id DESC";

$stmt_pendaftaran = $pdo->prepare($sql);
$stmt_pendaftaran->execute($params);
$pendaftaran_list = $stmt_pendaftaran->fetchAll();

// Ambil Master Pasien & Dokter untuk form pendaftaran
$pasien_all = $pdo->query("SELECT id, nomor_rm, nama_pasien, nik FROM pasien ORDER BY nama_pasien ASC")->fetchAll();
$dokter_all = $pdo->query("SELECT id, nama_dokter, spesialisasi, jadwal_praktek FROM dokter WHERE status = 'Aktif' ORDER BY nama_dokter ASC")->fetchAll();
?>

<!-- Header Halaman -->
<div class="page-header">
  <div>
    <h2 class="page-title">Pendaftaran & Antrean Pasien</h2>
    <p class="page-subtitle">Registrasi kunjungan harian, pembagian nomor antrean poliklinik, dan monitoring antrean.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahPendaftaran">
      <i data-lucide="clipboard-plus" style="width:18px;height:18px;"></i>
      <span>Daftar Kunjungan Baru</span>
    </button>
  </div>
</div>

<!-- Modal Pop-up Cetak Tiket Antrean (Jika ada parameter cetak_tiket) -->
<?php if ($tiket_data): ?>
  <div class="card-custom mb-4 border-primary">
    <div class="card-body-custom p-4">
      <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
        <h5 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2">
          <i data-lucide="ticket" style="width:20px;height:20px;"></i>
          <span>Tiket Antrean Pasien</span>
        </h5>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-primary btn-sm" onclick="printDocument()">
            <i data-lucide="printer" style="width:14px;height:14px;"></i> Cetak Tiket
          </button>
          <a href="<?= base_url('pages/pendaftaran.php'); ?>" class="btn btn-outline-secondary btn-sm">Tutup</a>
        </div>
      </div>

      <div class="text-center p-3 border rounded-3 bg-light" style="max-width:380px;margin:0 auto;">
        <h5 class="fw-bold mb-0"><?= e($clinic['nama_klinik']); ?></h5>
        <div class="small text-muted mb-3"><?= e($clinic['alamat_klinik']); ?></div>
        
        <div class="p-3 bg-white border rounded-3 my-2 shadow-sm">
          <div class="small text-muted text-uppercase fw-semibold">Nomor Antrean Anda</div>
          <div class="display-3 fw-bold text-primary my-1 font-monospace"><?= e($tiket_data['nomor_antrean']); ?></div>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($tiket_data['jenis_pasien']); ?></span>
        </div>

        <div class="text-start small mt-3">
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">No. Rekam Medis:</span>
            <strong class="font-monospace"><?= e($tiket_data['nomor_rm']); ?></strong>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Nama Pasien:</span>
            <strong><?= e($tiket_data['nama_pasien']); ?></strong>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Dokter:</span>
            <span><?= e($tiket_data['nama_dokter']); ?></span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">Tanggal:</span>
            <span><?= format_date_id($tiket_data['tanggal_kunjungan'], true); ?></span>
          </div>
        </div>
        <div class="small text-muted mt-3 fst-italic">Harap menunggu panggilan petugas di ruang tunggu klinik.</div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Filter & Tabel Antrean Pasien -->
<div class="card-custom">
  <div class="card-header-custom flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <h3 class="card-header-title">
        <i data-lucide="list" class="text-primary" style="width:20px;height:20px;"></i>
        <span>Daftar Antrean & Kunjungan</span>
      </h3>
      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
        <?= count($pendaftaran_list); ?> Antrean
      </span>
    </div>

    <!-- Filter Form -->
    <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
      <form action="" method="GET" class="d-flex align-items-center gap-2">
        <input type="date" name="tgl" class="form-control form-control-sm" value="<?= e($filter_tgl); ?>">
        <select name="status" class="form-select form-select-sm" style="width:140px;">
          <option value="">Semua Status</option>
          <option value="Menunggu" <?= ($filter_status === 'Menunggu') ? 'selected' : ''; ?>>Menunggu</option>
          <option value="Diperiksa" <?= ($filter_status === 'Diperiksa') ? 'selected' : ''; ?>>Diperiksa</option>
          <option value="Selesai" <?= ($filter_status === 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
          <option value="Dibatalkan" <?= ($filter_status === 'Dibatalkan') ? 'selected' : ''; ?>>Dibatalkan</option>
        </select>
        <button class="btn btn-sm btn-primary" type="submit">
          <i data-lucide="filter" style="width:14px;height:14px;"></i> Filter
        </button>
        <?php if ($filter_tgl !== date('Y-m-d') || $filter_status !== ''): ?>
          <a href="<?= base_url('pages/pendaftaran.php'); ?>" class="btn btn-sm btn-outline-secondary" title="Hari Ini">
            Hari Ini
          </a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No. Antrean</th>
          <th>Waktu Daftar</th>
          <th>Pasien & No. RM</th>
          <th>Dokter Dituju</th>
          <th>Jenis Pasien</th>
          <th>Keluhan Awal</th>
          <th>Status</th>
          <th class="text-end">Aksi / Alur</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($pendaftaran_list)): ?>
          <?php foreach ($pendaftaran_list as $row): ?>
            <tr>
              <td>
                <span class="display-6 fs-5 font-monospace fw-bold text-primary bg-primary-subtle px-2 py-1 rounded">
                  <?= e($row['nomor_antrean']); ?>
                </span>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= format_date_id($row['tanggal_kunjungan']); ?></div>
                <div class="small text-muted"><?= date('H:i', strtotime($row['created_at'])); ?> WIB</div>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= e($row['nama_pasien']); ?></div>
                <div class="small text-muted font-monospace"><?= e($row['nomor_rm']); ?> &bull; <?= e($row['no_telepon']); ?></div>
              </td>
              <td>
                <div class="fw-medium text-dark"><?= e($row['nama_dokter']); ?></div>
                <div class="small text-muted"><?= e($row['spesialisasi']); ?></div>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  <?= e($row['jenis_pasien']); ?>
                </span>
              </td>
              <td>
                <span class="small text-muted text-truncate d-inline-block" style="max-width: 200px;" title="<?= e($row['keluhan_awal']); ?>">
                  <?= e($row['keluhan_awal'] ?: '-'); ?>
                </span>
              </td>
              <td>
                <?php if ($row['status'] === 'Selesai'): ?>
                  <span class="badge-custom badge-success-soft">
                    <i data-lucide="check" style="width:12px;height:12px;"></i> Selesai
                  </span>
                <?php elseif ($row['status'] === 'Diperiksa'): ?>
                  <span class="badge-custom badge-warning-soft">
                    <i data-lucide="activity" style="width:12px;height:12px;"></i> Diperiksa
                  </span>
                <?php elseif ($row['status'] === 'Menunggu'): ?>
                  <span class="badge-custom badge-primary-soft">
                    <i data-lucide="clock" style="width:12px;height:12px;"></i> Menunggu
                  </span>
                <?php else: ?>
                  <span class="badge-custom badge-danger-soft">
                    <i data-lucide="x" style="width:12px;height:12px;"></i> Batal
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-1 align-items-center">
                  <!-- Tombol Cetak Tiket -->
                  <a href="<?= base_url('pages/pendaftaran.php?cetak_tiket=' . $row['id']); ?>" class="btn-action-icon text-primary" title="Cetak Tiket">
                    <i data-lucide="printer" style="width:15px;height:15px;"></i>
                  </a>

                  <!-- Jika masih Menunggu / Diperiksa, tombol lanjut ke Pemeriksaan Rekam Medis -->
                  <?php if ($row['status'] !== 'Selesai' && $row['status'] !== 'Dibatalkan'): ?>
                    <a href="<?= base_url('pages/pemeriksaan.php?id_pendaftaran=' . $row['id']); ?>" class="btn btn-sm btn-success px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size:0.775rem;" title="Input Pemeriksaan RM">
                      <i data-lucide="stethoscope" style="width:14px;height:14px;"></i>
                      <span>Periksa</span>
                    </a>
                  <?php endif; ?>

                  <!-- Dropdown Status Cepat -->
                  <div class="dropdown">
                    <button class="btn-action-icon" type="button" data-bs-toggle="dropdown">
                      <i data-lucide="more-vertical" style="width:15px;height:15px;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                      <li><h6 class="dropdown-header">Ubah Status Antrean</h6></li>
                      <li><a class="dropdown-item" href="<?= base_url('pages/pendaftaran.php?action=status&id=' . $row['id'] . '&set=Menunggu'); ?>">Set Menunggu</a></li>
                      <li><a class="dropdown-item" href="<?= base_url('pages/pendaftaran.php?action=status&id=' . $row['id'] . '&set=Diperiksa'); ?>">Set Sedang Diperiksa</a></li>
                      <li><a class="dropdown-item" href="<?= base_url('pages/pendaftaran.php?action=status&id=' . $row['id'] . '&set=Selesai'); ?>">Set Selesai</a></li>
                      <li><a class="dropdown-item text-danger" href="<?= base_url('pages/pendaftaran.php?action=status&id=' . $row['id'] . '&set=Dibatalkan'); ?>">Batalkan Antrean</a></li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <a class="dropdown-item text-danger" href="#" onclick="confirmDelete('<?= base_url('pages/pendaftaran.php?action=hapus&id=' . $row['id']); ?>', 'Hapus Pendaftaran', 'Hapus nomor antrean <?= $row['nomor_antrean']; ?>?')">
                          Hapus Data
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">Tidak ada data antrean pendaftaran pada tanggal / filter yang dipilih.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Pendaftaran Kunjungan -->
<div class="modal fade" id="modalTambahPendaftaran" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <i data-lucide="clipboard-plus" class="text-primary"></i>
          <span>Pendaftaran Kunjungan Pasien</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="tambah">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label form-label-custom">Pilih Pasien Terdaftar *</label>
              <select name="id_pasien" class="form-select form-select-custom" required>
                <option value="">-- Pilih Nama Pasien atau No. RM --</option>
                <?php foreach ($pasien_all as $pas): ?>
                  <option value="<?= $pas['id']; ?>" <?= (isset($_GET['pasien_id']) && $_GET['pasien_id'] == $pas['id']) ? 'selected' : ''; ?>>
                    [<?= e($pas['nomor_rm']); ?>] <?= e($pas['nama_pasien']); ?> - NIK: <?= e($pas['nik']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="small text-muted mt-1">
                Belum terdaftar? <a href="<?= base_url('pages/pasien.php?action=tambah'); ?>" target="_blank">Registrasi Pasien Baru</a>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Dokter Pemeriksa *</label>
              <select name="id_dokter" class="form-select form-select-custom" required>
                <option value="">-- Pilih Dokter & Poliklinik --</option>
                <?php foreach ($dokter_all as $dok): ?>
                  <option value="<?= $dok['id']; ?>">
                    <?= e($dok['nama_dokter']); ?> (<?= e($dok['spesialisasi']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Jenis Pembayaran / Pasien *</label>
              <select name="jenis_pasien" class="form-select form-select-custom" required>
                <option value="Umum">Pasien Umum (Mandiri)</option>
                <option value="BPJS">BPJS Kesehatan</option>
                <option value="Asuransi Swasta">Asuransi Swasta / Rekanan</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Tanggal Kunjungan *</label>
              <input type="date" name="tanggal_kunjungan" class="form-control form-control-custom" value="<?= date('Y-m-d'); ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Estimasi Nomor Antrean</label>
              <input type="text" class="form-control form-control-custom bg-light font-monospace fw-bold text-primary" value="<?= generate_nomor_antrean($pdo); ?> (Otomatis dibuat sistem)" readonly>
            </div>

            <div class="col-12">
              <label class="form-label form-label-custom">Keluhan Awal Pasien</label>
              <textarea name="keluhan_awal" class="form-control form-control-custom" rows="3" placeholder="Deskripsikan gejala awal atau tujuan kunjungan pasien..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer modal-footer-custom">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">
            <i data-lucide="check" style="width:16px;height:16px;"></i>
            <span>Daftarkan Antrean</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (isset($_GET['action']) && $_GET['action'] === 'tambah'): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const pendaftaranModal = new bootstrap.Modal(document.getElementById('modalTambahPendaftaran'));
    pendaftaranModal.show();
  });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
