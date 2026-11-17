<?php
/**
 * Halaman Manajemen Jadwal Kontrol Pasien
 * Fitur: Penjadwalan kontrol pasca pemeriksaan, monitoring pasien kontrol hari ini, ubah status kontrol (Terjadwal/Selesai/Batal)
 */

$page_title = 'Jadwal Kontrol';
$active_menu = 'jadwal-kontrol';

require_once __DIR__ . '/../includes/header.php';

// 1. TAMBAH JADWAL KONTROL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_kontrol') {
    $id_pasien = (int)($_POST['id_pasien'] ?? 0);
    $id_dokter = (int)($_POST['id_dokter'] ?? 0);
    $id_rekam_medis = !empty($_POST['id_rekam_medis']) ? (int)$_POST['id_rekam_medis'] : null;
    $tanggal_kontrol = $_POST['tanggal_kontrol'] ?? '';
    $catatan_kontrol = trim($_POST['catatan_kontrol'] ?? '');

    if ($id_pasien <= 0 || $id_dokter <= 0 || empty($tanggal_kontrol)) {
        set_flash('danger', 'Pilih pasien, dokter, dan tanggal kontrol dengan benar!');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO jadwal_kontrol (id_pasien, id_dokter, id_rekam_medis, tanggal_kontrol, catatan_kontrol, status)
            VALUES (?, ?, ?, ?, ?, 'Terjadwal')
        ");
        $stmt->execute([$id_pasien, $id_dokter, $id_rekam_medis, $tanggal_kontrol, $catatan_kontrol]);
        set_flash('success', 'Jadwal kontrol pasien berhasil disimpan!');
        header('Location: ' . base_url('pages/jadwal-kontrol.php'));
        exit;
    }
}

// 2. UBAH STATUS KONTROL
if (isset($_GET['action']) && $_GET['action'] === 'set_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $st = $_GET['status'];
    $allowed = ['Terjadwal', 'Selesai', 'Dibatalkan', 'Terlewat'];

    if (in_array($st, $allowed)) {
        $stmt = $pdo->prepare("UPDATE jadwal_kontrol SET status = ? WHERE id = ?");
        $stmt->execute([$st, $id]);
        set_flash('success', "Status jadwal kontrol berhasil diubah menjadi <strong>{$st}</strong>.");
    }
    header('Location: ' . base_url('pages/jadwal-kontrol.php'));
    exit;
}

// 3. HAPUS JADWAL KONTROL
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM jadwal_kontrol WHERE id = ?");
    $stmt->execute([$id]);
    set_flash('success', 'Jadwal kontrol berhasil dihapus!');
    header('Location: ' . base_url('pages/jadwal-kontrol.php'));
    exit;
}

// 4. FILTER JADWAL KONTROL
$filter_tgl = $_GET['filter_tgl'] ?? '';
$filter_status = $_GET['status'] ?? '';

$sql = "
    SELECT jk.*, pas.nomor_rm, pas.nama_pasien, pas.no_telepon, d.nama_dokter, d.spesialisasi
    FROM jadwal_kontrol jk
    JOIN pasien pas ON jk.id_pasien = pas.id
    JOIN dokter d ON jk.id_dokter = d.id
    WHERE 1=1
";
$params = [];

if ($filter_tgl !== '') {
    $sql .= " AND jk.tanggal_kontrol = ?";
    $params[] = $filter_tgl;
}
if ($filter_status !== '') {
    $sql .= " AND jk.status = ?";
    $params[] = $filter_status;
}
$sql .= " ORDER BY jk.tanggal_kontrol ASC, jk.id DESC";

$stmt_k = $pdo->prepare($sql);
$stmt_k->execute($params);
$jadwal_list = $stmt_k->fetchAll();

// Master Pasien & Dokter
$pasien_all = $pdo->query("SELECT id, nomor_rm, nama_pasien FROM pasien ORDER BY nama_pasien ASC")->fetchAll();
$dokter_all = $pdo->query("SELECT id, nama_dokter, spesialisasi FROM dokter WHERE status = 'Aktif' ORDER BY nama_dokter ASC")->fetchAll();
?>

<!-- Header Halaman -->
<div class="page-header">
  <div>
    <h2 class="page-title">Jadwal Kontrol Pasien</h2>
    <p class="page-subtitle">Penjadwalan pemeriksaan ulang pasca rawat jalan, evaluasi pengobatan, dan notifikasi kontrol.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahKontrol">
      <i data-lucide="calendar-plus" style="width:18px;height:18px;"></i>
      <span>Buat Jadwal Kontrol Baru</span>
    </button>
  </div>
</div>

<!-- Filter & Tabel Jadwal Kontrol -->
<div class="card-custom">
  <div class="card-header-custom flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <h3 class="card-header-title">
        <i data-lucide="calendar-clock" class="text-primary" style="width:20px;height:20px;"></i>
        <span>Agenda Kontrol Rutin</span>
      </h3>
      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><?= count($jadwal_list); ?> Jadwal</span>
    </div>

    <!-- Filter Form -->
    <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
      <a href="<?= base_url('pages/jadwal-kontrol.php?filter_tgl=' . date('Y-m-d')); ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
        <i data-lucide="calendar" style="width:14px;height:14px;"></i> Hari Ini
      </a>
      <form action="" method="GET" class="d-flex align-items-center gap-2">
        <input type="date" name="filter_tgl" class="form-control form-control-sm" value="<?= e($filter_tgl); ?>">
        <select name="status" class="form-select form-select-sm" style="width:140px;">
          <option value="">Semua Status</option>
          <option value="Terjadwal" <?= ($filter_status === 'Terjadwal') ? 'selected' : ''; ?>>Terjadwal</option>
          <option value="Selesai" <?= ($filter_status === 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
          <option value="Dibatalkan" <?= ($filter_status === 'Dibatalkan') ? 'selected' : ''; ?>>Dibatalkan</option>
        </select>
        <button class="btn btn-sm btn-primary" type="submit">Filter</button>
        <?php if ($filter_tgl !== '' || $filter_status !== ''): ?>
          <a href="<?= base_url('pages/jadwal-kontrol.php'); ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>Tanggal Kontrol</th>
          <th>No. RM & Nama Pasien</th>
          <th>No. Telepon / WA</th>
          <th>Dokter Dituju</th>
          <th>Catatan / Tujuan Evaluasi</th>
          <th>Status</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($jadwal_list)): ?>
          <?php foreach ($jadwal_list as $row): ?>
            <?php
              $is_today = ($row['tanggal_kontrol'] === date('Y-m-d'));
              $is_past = ($row['tanggal_kontrol'] < date('Y-m-d') && $row['status'] === 'Terjadwal');
            ?>
            <tr class="<?= $is_today ? 'table-warning-subtle' : ''; ?>">
              <td>
                <div class="fw-bold <?= $is_today ? 'text-primary' : 'text-dark'; ?>">
                  <?= format_date_id($row['tanggal_kontrol']); ?>
                </div>
                <?php if ($is_today): ?>
                  <span class="badge bg-warning text-dark border border-warning small">Hari Ini</span>
                <?php elseif ($is_past): ?>
                  <span class="badge bg-danger text-white small">Terlewat</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= e($row['nama_pasien']); ?></div>
                <span class="font-monospace text-primary small"><?= e($row['nomor_rm']); ?></span>
              </td>
              <td>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $row['no_telepon']); ?>?text=Halo%20<?= urlencode($row['nama_pasien']); ?>,%20mengingatkan%20jadwal%20kontrol%20ulang%20di%20<?= urlencode($clinic['nama_klinik']); ?>%20pada%20tanggal%20<?= urlencode(format_date_id($row['tanggal_kontrol'])); ?>" target="_blank" class="btn btn-xs btn-outline-success d-inline-flex align-items-center gap-1">
                  <i data-lucide="message-circle" style="width:14px;height:14px;"></i>
                  <span><?= e($row['no_telepon']); ?></span>
                </a>
              </td>
              <td>
                <div class="fw-medium text-dark"><?= e($row['nama_dokter']); ?></div>
                <div class="small text-muted"><?= e($row['spesialisasi']); ?></div>
              </td>
              <td>
                <span class="small text-muted text-truncate d-inline-block" style="max-width:250px;" title="<?= e($row['catatan_kontrol']); ?>">
                  <?= e($row['catatan_kontrol'] ?: 'Evaluasi umum kondisi pasien.'); ?>
                </span>
              </td>
              <td>
                <?php if ($row['status'] === 'Selesai'): ?>
                  <span class="badge-custom badge-success-soft">Selesai</span>
                <?php elseif ($row['status'] === 'Terjadwal'): ?>
                  <span class="badge-custom badge-primary-soft">Terjadwal</span>
                <?php else: ?>
                  <span class="badge-custom badge-danger-soft">Dibatalkan</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <!-- Set Selesai -->
                  <?php if ($row['status'] === 'Terjadwal'): ?>
                    <a href="<?= base_url('pages/jadwal-kontrol.php?action=set_status&id=' . $row['id'] . '&status=Selesai'); ?>" class="btn-action-icon text-success" title="Tandai Sudah Kontrol">
                      <i data-lucide="check" style="width:16px;height:16px;"></i>
                    </a>
                    <a href="<?= base_url('pages/jadwal-kontrol.php?action=set_status&id=' . $row['id'] . '&status=Dibatalkan'); ?>" class="btn-action-icon text-warning" title="Batalkan Kontrol">
                      <i data-lucide="x" style="width:16px;height:16px;"></i>
                    </a>
                  <?php endif; ?>
                  <button type="button" class="btn-action-icon danger" title="Hapus Jadwal" onclick="confirmDelete('<?= base_url('pages/jadwal-kontrol.php?action=hapus&id=' . $row['id']); ?>', 'Hapus Jadwal Kontrol', 'Hapus jadwal kontrol pasien <?= addslashes(e($row['nama_pasien'])); ?>?')">
                    <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">Belum ada agenda jadwal kontrol yang sesuai.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Jadwal Kontrol -->
<div class="modal fade" id="modalTambahKontrol" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <i data-lucide="calendar-plus" class="text-primary"></i>
          <span>Jadwalkan Kontrol Ulang Pasien</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="tambah_kontrol">
        <input type="hidden" name="id_rekam_medis" value="<?= (int)($_GET['rm_id'] ?? 0); ?>">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label form-label-custom">Pilih Pasien *</label>
              <select name="id_pasien" class="form-select form-select-custom" required>
                <option value="">-- Pilih Nama Pasien atau No. RM --</option>
                <?php foreach ($pasien_all as $pas): ?>
                  <option value="<?= $pas['id']; ?>" <?= (isset($_GET['pasien_id']) && $_GET['pasien_id'] == $pas['id']) ? 'selected' : ''; ?>>
                    [<?= e($pas['nomor_rm']); ?>] <?= e($pas['nama_pasien']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Dokter yang Dituju *</label>
              <select name="id_dokter" class="form-select form-select-custom" required>
                <option value="">-- Pilih Dokter --</option>
                <?php foreach ($dokter_all as $dok): ?>
                  <option value="<?= $dok['id']; ?>">
                    <?= e($dok['nama_dokter']); ?> (<?= e($dok['spesialisasi']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Tanggal Rencana Kontrol *</label>
              <input type="date" name="tanggal_kontrol" class="form-control form-control-custom" value="<?= date('Y-m-d', strtotime('+3 days')); ?>" required>
            </div>

            <div class="col-12">
              <label class="form-label form-label-custom">Tujuan Kontrol / Instruksi</label>
              <textarea name="catatan_kontrol" class="form-control form-control-custom" rows="3" placeholder="Contoh: Evaluasi hasil minum obat antibiotik 5 hari, cek tekanan darah ulang, cek gula darah puasa..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer modal-footer-custom">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">Simpan Jadwal Kontrol</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (isset($_GET['pasien_id'])): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const kontrolModal = new bootstrap.Modal(document.getElementById('modalTambahKontrol'));
    kontrolModal.show();
  });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
