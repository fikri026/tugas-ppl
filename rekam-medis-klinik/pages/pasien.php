<?php
/**
 * Halaman Manajemen Data Pasien
 * CRUD Lengkap: Tambah Pasien, Edit, Hapus, Detail Pasien & Riwayat Medis, Auto-generate No RM
 */

$page_title = 'Data Pasien';
$active_menu = 'pasien';

require_once __DIR__ . '/../includes/header.php';

// 1. Proses Tambah Pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nomor_rm = trim($_POST['nomor_rm'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $nama_pasien = trim($_POST['nama_pasien'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'Laki-laki';
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $alamat = trim($_POST['alamat'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $gol_darah = $_POST['gol_darah'] ?? '-';
    $alergi = trim($_POST['alergi'] ?? 'Tidak Ada');

    if (empty($nama_pasien) || empty($nik) || empty($tanggal_lahir) || empty($alamat) || empty($no_telepon)) {
        set_flash('danger', 'Semua kolom bertanda bintang (*) wajib diisi!');
    } else {
        // Cek duplikasi NIK atau Nomor RM
        $stmt_check = $pdo->prepare("SELECT id FROM pasien WHERE nik = ? OR nomor_rm = ?");
        $stmt_check->execute([$nik, $nomor_rm]);
        if ($stmt_check->fetch()) {
            set_flash('danger', 'Pasien dengan NIK atau Nomor RM tersebut sudah terdaftar di sistem!');
        } else {
            $stmt_insert = $pdo->prepare("
                INSERT INTO pasien (nomor_rm, nik, nama_pasien, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, no_telepon, gol_darah, alergi)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_insert->execute([$nomor_rm, $nik, $nama_pasien, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $alamat, $no_telepon, $gol_darah, $alergi]);
            set_flash('success', "Pasien baru dengan Nomor RM <strong>{$nomor_rm}</strong> ({$nama_pasien}) berhasil ditambahkan!");
            header('Location: ' . base_url('pages/pasien.php'));
            exit;
        }
    }
}

// 2. Proses Edit Pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $nik = trim($_POST['nik'] ?? '');
    $nama_pasien = trim($_POST['nama_pasien'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'Laki-laki';
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $alamat = trim($_POST['alamat'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $gol_darah = $_POST['gol_darah'] ?? '-';
    $alergi = trim($_POST['alergi'] ?? 'Tidak Ada');

    if (empty($nama_pasien) || empty($nik) || empty($tanggal_lahir) || empty($alamat) || empty($no_telepon)) {
        set_flash('danger', 'Seluruh kolom bertanda bintang (*) wajib diisi!');
    } else {
        $stmt_update = $pdo->prepare("
            UPDATE pasien 
            SET nik = ?, nama_pasien = ?, jenis_kelamin = ?, tempat_lahir = ?, tanggal_lahir = ?, alamat = ?, no_telepon = ?, gol_darah = ?, alergi = ?
            WHERE id = ?
        ");
        $stmt_update->execute([$nik, $nama_pasien, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $alamat, $no_telepon, $gol_darah, $alergi, $id]);
        set_flash('success', "Data pasien <strong>{$nama_pasien}</strong> berhasil diperbarui!");
        header('Location: ' . base_url('pages/pasien.php'));
        exit;
    }
}

// 3. Proses Hapus Pasien
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt_del = $pdo->prepare("DELETE FROM pasien WHERE id = ?");
    $stmt_del->execute([$id]);
    set_flash('success', 'Data pasien berhasil dihapus dari sistem!');
    header('Location: ' . base_url('pages/pasien.php'));
    exit;
}

// 4. Data Pencarian dan List Pasien
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt_list = $pdo->prepare("
        SELECT * FROM pasien 
        WHERE nomor_rm LIKE ? OR nama_pasien LIKE ? OR nik LIKE ? OR no_telepon LIKE ?
        ORDER BY id DESC
    ");
    $param = "%{$search}%";
    $stmt_list->execute([$param, $param, $param, $param]);
} else {
    $stmt_list = $pdo->query("SELECT * FROM pasien ORDER BY id DESC");
}
$pasien_list = $stmt_list->fetchAll();

// Nomor RM Baru Otomatis
$auto_rm = generate_nomor_rm($pdo);
?>

<!-- Header Halaman -->
<div class="page-header">
  <div>
    <h2 class="page-title">Manajemen Data Pasien</h2>
    <p class="page-subtitle">Pencatatan identitas pasien, riwayat alergi, dan nomor rekam medis otomatis.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahPasien">
      <i data-lucide="user-plus" style="width:18px;height:18px;"></i>
      <span>Tambah Pasien Baru</span>
    </button>
  </div>
</div>

<!-- Card Data Pasien -->
<div class="card-custom">
  <div class="card-header-custom flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <h3 class="card-header-title">
        <i data-lucide="users" class="text-primary" style="width:20px;height:20px;"></i>
        <span>Daftar Pasien Terdaftar</span>
      </h3>
      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
        <?= count($pasien_list); ?> Pasien
      </span>
    </div>

    <!-- Kotak Pencarian -->
    <div class="d-flex align-items-center gap-2 ms-auto">
      <form action="" method="GET" class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm" style="width: 280px;">
          <input type="text" name="q" class="form-control" placeholder="Cari Nama, No RM, NIK..." value="<?= e($search); ?>">
          <button class="btn btn-primary" type="submit">
            <i data-lucide="search" style="width:16px;height:16px;"></i>
          </button>
        </div>
        <?php if ($search !== ''): ?>
          <a href="<?= base_url('pages/pasien.php'); ?>" class="btn btn-sm btn-outline-secondary" title="Reset Pencarian">
            <i data-lucide="x" style="width:16px;height:16px;"></i>
          </a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No. RM</th>
          <th>Nama Pasien & NIK</th>
          <th>L/P</th>
          <th>Tgl Lahir / Usia</th>
          <th>No. Telepon</th>
          <th>Gol. Darah</th>
          <th>Alergi</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($pasien_list)): ?>
          <?php foreach ($pasien_list as $p): ?>
            <?php
              $birthDate = new DateTime($p['tanggal_lahir']);
              $now = new DateTime();
              $age = $now->diff($birthDate)->y;
            ?>
            <tr>
              <td>
                <span class="font-monospace fw-bold text-primary bg-primary-subtle px-2 py-1 rounded">
                  <?= e($p['nomor_rm']); ?>
                </span>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= e($p['nama_pasien']); ?></div>
                <div class="small text-muted font-monospace">NIK: <?= e($p['nik']); ?></div>
              </td>
              <td>
                <?php if ($p['jenis_kelamin'] === 'Laki-laki'): ?>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle">L</span>
                <?php else: ?>
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle">P</span>
                <?php endif; ?>
              </td>
              <td>
                <div><?= format_date_id($p['tanggal_lahir']); ?></div>
                <span class="small text-muted"><?= $age; ?> tahun</span>
              </td>
              <td>
                <a href="tel:<?= e($p['no_telepon']); ?>" class="text-decoration-none text-dark">
                  <?= e($p['no_telepon']); ?>
                </a>
              </td>
              <td>
                <span class="badge bg-light text-dark border fw-bold"><?= e($p['gol_darah']); ?></span>
              </td>
              <td>
                <?php if ($p['alergi'] !== 'Tidak Ada' && !empty($p['alergi'])): ?>
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                    <i data-lucide="alert-circle" style="width:12px;height:12px;"></i> <?= e($p['alergi']); ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted small">Tidak Ada</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <!-- Tombol Pendaftaran Kunjungan Cepat -->
                  <a href="<?= base_url('pages/pendaftaran.php?pasien_id=' . $p['id']); ?>" class="btn-action-icon text-primary" title="Daftarkan Kunjungan">
                    <i data-lucide="clipboard-plus" style="width:16px;height:16px;"></i>
                  </a>
                  <!-- Tombol Detail Pasien -->
                  <button type="button" class="btn-action-icon text-info" title="Detail Pasien" onclick='openDetailPasienModal(<?= json_encode($p); ?>)'>
                    <i data-lucide="eye" style="width:16px;height:16px;"></i>
                  </button>
                  <!-- Tombol Edit Pasien -->
                  <button type="button" class="btn-action-icon text-warning" title="Edit Data" onclick='openEditPasienModal(<?= json_encode($p); ?>)'>
                    <i data-lucide="edit-3" style="width:16px;height:16px;"></i>
                  </button>
                  <!-- Tombol Hapus Pasien -->
                  <button type="button" class="btn-action-icon danger" title="Hapus Pasien" onclick="confirmDelete('<?= base_url('pages/pasien.php?action=hapus&id=' . $p['id']); ?>', 'Hapus Pasien', 'Apakah Anda yakin ingin menghapus data pasien <?= addslashes(e($p['nama_pasien'])); ?>?')">
                    <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">
              <?= $search ? 'Tidak ada pasien yang sesuai dengan kata kunci pencarian.' : 'Belum ada data pasien terdaftar.'; ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Pasien Baru -->
<div class="modal fade" id="modalTambahPasien" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <i data-lucide="user-plus" class="text-primary"></i>
          <span>Registrasi Pasien Baru</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="tambah">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-label-custom">Nomor Rekam Medis (Otomatis)</label>
              <input type="text" name="nomor_rm" class="form-control form-control-custom bg-light font-monospace fw-bold text-primary" value="<?= e($auto_rm); ?>" readonly required>
              <small class="text-muted">Nomor unik rekam medis tahun berjalan.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Nomor Induk Kependudukan (NIK) *</label>
              <input type="text" name="nik" class="form-control form-control-custom font-monospace" placeholder="16 Digit NIK KTP" maxlength="20" required>
            </div>

            <div class="col-md-8">
              <label class="form-label form-label-custom">Nama Lengkap Pasien *</label>
              <input type="text" name="nama_pasien" class="form-control form-control-custom" placeholder="Nama sesuai identitas resmi" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-custom">Jenis Kelamin *</label>
              <select name="jenis_kelamin" class="form-select form-select-custom" required>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Tempat Lahir</label>
              <input type="text" name="tempat_lahir" class="form-control form-control-custom" placeholder="Kota / Kabupaten Lahir">
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Tanggal Lahir *</label>
              <input type="date" name="tanggal_lahir" class="form-control form-control-custom" required>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Nomor WhatsApp / Telepon *</label>
              <input type="text" name="no_telepon" class="form-control form-control-custom" placeholder="08xxxxxxxxxx" required>
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-custom">Golongan Darah</label>
              <select name="gol_darah" class="form-select form-select-custom">
                <option value="-">-</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
                <option value="O">O</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-custom">Riwayat Alergi</label>
              <input type="text" name="alergi" class="form-control form-control-custom" placeholder="Contoh: Penisilin, Udang" value="Tidak Ada">
            </div>

            <div class="col-12">
              <label class="form-label form-label-custom">Alamat Lengkap Domisili *</label>
              <textarea name="alamat" class="form-control form-control-custom" rows="2" placeholder="Nama Jalan, RT/RW, Kelurahan, Kecamatan, Kota" required></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer modal-footer-custom">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">
            <i data-lucide="save" style="width:16px;height:16px;"></i>
            <span>Simpan Pasien</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Pasien -->
<div class="modal fade" id="modalEditPasien" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <i data-lucide="edit-3" class="text-warning"></i>
          <span>Edit Data Pasien</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-label-custom">Nomor Rekam Medis</label>
              <input type="text" name="nomor_rm" id="edit_nomor_rm" class="form-control form-control-custom bg-light font-monospace fw-bold" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">NIK *</label>
              <input type="text" name="nik" id="edit_nik" class="form-control form-control-custom font-monospace" required>
            </div>

            <div class="col-md-8">
              <label class="form-label form-label-custom">Nama Pasien *</label>
              <input type="text" name="nama_pasien" id="edit_nama_pasien" class="form-control form-control-custom" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-custom">Jenis Kelamin *</label>
              <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-select form-select-custom" required>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Tempat Lahir</label>
              <input type="text" name="tempat_lahir" id="edit_tempat_lahir" class="form-control form-control-custom">
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Tanggal Lahir *</label>
              <input type="date" name="tanggal_lahir" id="edit_tanggal_lahir" class="form-control form-control-custom" required>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">No. Telepon *</label>
              <input type="text" name="no_telepon" id="edit_no_telepon" class="form-control form-control-custom" required>
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-custom">Golongan Darah</label>
              <select name="gol_darah" id="edit_gol_darah" class="form-select form-select-custom">
                <option value="-">-</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
                <option value="O">O</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-custom">Riwayat Alergi</label>
              <input type="text" name="alergi" id="edit_alergi" class="form-control form-control-custom">
            </div>

            <div class="col-12">
              <label class="form-label form-label-custom">Alamat Domisili *</label>
              <textarea name="alamat" id="edit_alamat" class="form-control form-control-custom" rows="2" required></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer modal-footer-custom">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">
            <i data-lucide="check" style="width:16px;height:16px;"></i>
            <span>Simpan Perubahan</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Detail Pasien -->
<div class="modal fade" id="modalDetailPasien" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <i data-lucide="user-check" class="text-primary"></i>
          <span>Kartu Identitas Pasien</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="text-center pb-3 border-bottom mb-3">
          <h4 class="fw-bold mb-1" id="detail_nama">-</h4>
          <span class="badge bg-primary text-white font-monospace px-3 py-1 fs-6" id="detail_rm">-</span>
        </div>
        <div class="row g-2 small">
          <div class="col-4 text-muted">Nomor NIK:</div>
          <div class="col-8 fw-semibold font-monospace" id="detail_nik">-</div>

          <div class="col-4 text-muted">Jenis Kelamin:</div>
          <div class="col-8 fw-semibold" id="detail_gender">-</div>

          <div class="col-4 text-muted">TTL:</div>
          <div class="col-8 fw-semibold" id="detail_ttl">-</div>

          <div class="col-4 text-muted">Usia:</div>
          <div class="col-8 fw-semibold" id="detail_umur">-</div>

          <div class="col-4 text-muted">No. Telepon:</div>
          <div class="col-8 fw-semibold" id="detail_telepon">-</div>

          <div class="col-4 text-muted">Gol. Darah:</div>
          <div class="col-8 fw-semibold" id="detail_goldar">-</div>

          <div class="col-4 text-muted">Alergi:</div>
          <div class="col-8 fw-semibold text-danger" id="detail_alergi">-</div>

          <div class="col-4 text-muted">Alamat:</div>
          <div class="col-8 fw-semibold" id="detail_alamat">-</div>
        </div>
      </div>
      <div class="modal-footer modal-footer-custom">
        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?php if (isset($_GET['action']) && $_GET['action'] === 'tambah'): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const tambahModal = new bootstrap.Modal(document.getElementById('modalTambahPasien'));
    tambahModal.show();
  });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
