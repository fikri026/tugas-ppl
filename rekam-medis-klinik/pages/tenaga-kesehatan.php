<?php
/**
 * Halaman Manajemen Data Dokter dan Tenaga Kesehatan
 * CRUD Lengkap: Tambah Nakes, Edit, Hapus, Detail Spesialisasi & Jadwal Praktik
 */

$page_title = 'Data Tenaga Kesehatan';
$active_menu = 'tenaga-kesehatan';

require_once __DIR__ . '/../includes/header.php';

// 1. Tambah Dokter Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nip = trim($_POST['nip'] ?? '');
    $nama_dokter = trim($_POST['nama_dokter'] ?? '');
    $spesialisasi = trim($_POST['spesialisasi'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $jadwal_praktek = trim($_POST['jadwal_praktek'] ?? '');
    $status = $_POST['status'] ?? 'Aktif';

    if (empty($nip) || empty($nama_dokter) || empty($spesialisasi) || empty($no_telepon) || empty($jadwal_praktek)) {
        set_flash('danger', 'Semua kolom bertanda bintang (*) wajib diisi!');
    } else {
        // Cek NIP duplikat
        $check = $pdo->prepare("SELECT id FROM dokter WHERE nip = ?");
        $check->execute([$nip]);
        if ($check->fetch()) {
            set_flash('danger', 'NIP tersebut telah digunakan oleh tenaga kesehatan lain!');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO dokter (nip, nama_dokter, spesialisasi, no_telepon, email, jadwal_praktek, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nip, $nama_dokter, $spesialisasi, $no_telepon, $email, $jadwal_praktek, $status]);
            set_flash('success', "Data tenaga kesehatan <strong>{$nama_dokter}</strong> berhasil didaftarkan!");
            header('Location: ' . base_url('pages/tenaga-kesehatan.php'));
            exit;
        }
    }
}

// 2. Edit Dokter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $nip = trim($_POST['nip'] ?? '');
    $nama_dokter = trim($_POST['nama_dokter'] ?? '');
    $spesialisasi = trim($_POST['spesialisasi'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $jadwal_praktek = trim($_POST['jadwal_praktek'] ?? '');
    $status = $_POST['status'] ?? 'Aktif';

    if (empty($nip) || empty($nama_dokter) || empty($spesialisasi) || empty($no_telepon) || empty($jadwal_praktek)) {
        set_flash('danger', 'Semua kolom bertanda bintang (*) wajib diisi!');
    } else {
        $stmt = $pdo->prepare("
            UPDATE dokter 
            SET nip = ?, nama_dokter = ?, spesialisasi = ?, no_telepon = ?, email = ?, jadwal_praktek = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([$nip, $nama_dokter, $spesialisasi, $no_telepon, $email, $jadwal_praktek, $status, $id]);
        set_flash('success', "Informasi <strong>{$nama_dokter}</strong> berhasil diperbarui!");
        header('Location: ' . base_url('pages/tenaga-kesehatan.php'));
        exit;
    }
}

// 3. Hapus Dokter
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt_del = $pdo->prepare("DELETE FROM dokter WHERE id = ?");
        $stmt_del->execute([$id]);
        set_flash('success', 'Data tenaga kesehatan berhasil dihapus!');
    } catch (PDOException $e) {
        set_flash('danger', 'Tenaga kesehatan tidak dapat dihapus karena memiliki riwayat pemeriksaan atau antrean pasien!');
    }
    header('Location: ' . base_url('pages/tenaga-kesehatan.php'));
    exit;
}

// 4. Ambil Daftar Dokter
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM dokter 
        WHERE nama_dokter LIKE ? OR spesialisasi LIKE ? OR nip LIKE ?
        ORDER BY id DESC
    ");
    $param = "%{$search}%";
    $stmt->execute([$param, $param, $param]);
} else {
    $stmt = $pdo->query("SELECT * FROM dokter ORDER BY id ASC");
}
$dokter_list = $stmt->fetchAll();
?>

<!-- Header Halaman -->
<div class="page-header">
  <div>
    <h2 class="page-title">Tenaga Kesehatan & Dokter</h2>
    <p class="page-subtitle">Daftar dokter spesialis, dokter umum, dan jadwal pelayanan poliklinik.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahDokter">
      <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
      <span>Tambah Tenaga Medis</span>
    </button>
  </div>
</div>

<!-- Card Tabel Dokter -->
<div class="card-custom">
  <div class="card-header-custom flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <h3 class="card-header-title">
        <i data-lucide="stethoscope" class="text-primary" style="width:20px;height:20px;"></i>
        <span>Daftar Tim Medis Klinik</span>
      </h3>
      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
        <?= count($dokter_list); ?> Tenaga Medis
      </span>
    </div>

    <!-- Kotak Pencarian -->
    <div class="d-flex align-items-center gap-2 ms-auto">
      <form action="" method="GET" class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm" style="width: 280px;">
          <input type="text" name="q" class="form-control" placeholder="Cari Dokter, Spesialisasi, NIP..." value="<?= e($search); ?>">
          <button class="btn btn-primary" type="submit">
            <i data-lucide="search" style="width:16px;height:16px;"></i>
          </button>
        </div>
        <?php if ($search !== ''): ?>
          <a href="<?= base_url('pages/tenaga-kesehatan.php'); ?>" class="btn btn-sm btn-outline-secondary" title="Reset">
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
          <th>Tenaga Kesehatan</th>
          <th>NIP / No. Izin</th>
          <th>Spesialisasi</th>
          <th>Kontak & Email</th>
          <th>Jadwal Praktik</th>
          <th>Status</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($dokter_list)): ?>
          <?php foreach ($dokter_list as $d): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-3">
                  <div class="user-avatar" style="width:42px;height:42px;background:linear-gradient(135deg, #1d4ed8, #0ea5e9);">
                    <?= strtoupper(substr($d['nama_dokter'], 0, 1) === 'd' ? 'DR' : substr($d['nama_dokter'], 0, 1)); ?>
                  </div>
                  <div>
                    <div class="fw-bold text-dark"><?= e($d['nama_dokter']); ?></div>
                    <span class="small text-muted"><?= e($d['spesialisasi']); ?></span>
                  </div>
                </div>
              </td>
              <td>
                <span class="font-monospace text-muted small"><?= e($d['nip']); ?></span>
              </td>
              <td>
                <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1">
                  <?= e($d['spesialisasi']); ?>
                </span>
              </td>
              <td>
                <div class="small fw-semibold"><?= e($d['no_telepon']); ?></div>
                <div class="small text-muted"><?= e($d['email'] ?: '-'); ?></div>
              </td>
              <td>
                <div class="small text-dark d-flex align-items-center gap-1">
                  <i data-lucide="clock" class="text-secondary" style="width:14px;height:14px;"></i>
                  <span><?= e($d['jadwal_praktek']); ?></span>
                </div>
              </td>
              <td>
                <?php if ($d['status'] === 'Aktif'): ?>
                  <span class="badge-custom badge-success-soft">
                    <i data-lucide="check-circle" style="width:12px;height:12px;"></i> Aktif
                  </span>
                <?php elseif ($d['status'] === 'Cuti'): ?>
                  <span class="badge-custom badge-warning-soft">
                    <i data-lucide="calendar" style="width:12px;height:12px;"></i> Cuti
                  </span>
                <?php else: ?>
                  <span class="badge-custom badge-danger-soft">
                    <i data-lucide="x-circle" style="width:12px;height:12px;"></i> Nonaktif
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <button type="button" class="btn-action-icon text-warning" title="Edit Data" onclick='openEditDokterModal(<?= json_encode($d); ?>)'>
                    <i data-lucide="edit-3" style="width:16px;height:16px;"></i>
                  </button>
                  <button type="button" class="btn-action-icon danger" title="Hapus Data" onclick="confirmDelete('<?= base_url('pages/tenaga-kesehatan.php?action=hapus&id=' . $d['id']); ?>', 'Hapus Nakes', 'Apakah Anda yakin ingin menghapus data <?= addslashes(e($d['nama_dokter'])); ?>?')">
                    <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">Belum ada data tenaga kesehatan.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Dokter -->
<div class="modal fade" id="modalTambahDokter" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <i data-lucide="plus-circle" class="text-primary"></i>
          <span>Pendaftaran Tenaga Medis Baru</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="tambah">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-label-custom">NIP / Nomor Registrasi Dokter *</label>
              <input type="text" name="nip" class="form-control form-control-custom font-monospace" placeholder="Nomor Induk Pegawai / SIP" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Status Keaktifan *</label>
              <select name="status" class="form-select form-select-custom" required>
                <option value="Aktif">Aktif Bertugas</option>
                <option value="Cuti">Sedang Cuti</option>
                <option value="Nonaktif">Nonaktif</option>
              </select>
            </div>

            <div class="col-md-7">
              <label class="form-label form-label-custom">Nama Lengkap & Gelar *</label>
              <input type="text" name="nama_dokter" class="form-control form-control-custom" placeholder="Contoh: dr. Ahmad Fauzan, Sp.PD" required>
            </div>
            <div class="col-md-5">
              <label class="form-label form-label-custom">Spesialisasi / Keahlian *</label>
              <input type="text" name="spesialisasi" class="form-control form-control-custom" placeholder="Dokter Umum / Spesialis Anak / dll" required>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Nomor Telepon / WhatsApp *</label>
              <input type="text" name="no_telepon" class="form-control form-control-custom" placeholder="08xxxxxxxxxx" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Alamat Email</label>
              <input type="email" name="email" class="form-control form-control-custom" placeholder="dokter@klinik.co.id">
            </div>

            <div class="col-12">
              <label class="form-label form-label-custom">Jadwal Praktik Pelayanan *</label>
              <input type="text" name="jadwal_praktek" class="form-control form-control-custom" placeholder="Contoh: Senin - Kamis (08.00 - 14.00 WIB)" required>
            </div>
          </div>
        </div>
        <div class="modal-footer modal-footer-custom">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">
            <i data-lucide="save" style="width:16px;height:16px;"></i>
            <span>Simpan Tenaga Medis</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Dokter -->
<div class="modal fade" id="modalEditDokter" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <i data-lucide="edit-3" class="text-warning"></i>
          <span>Perbarui Data Tenaga Medis</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_dokter_id">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-label-custom">NIP / Nomor Registrasi *</label>
              <input type="text" name="nip" id="edit_dokter_nip" class="form-control form-control-custom font-monospace" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Status Keaktifan *</label>
              <select name="status" id="edit_dokter_status" class="form-select form-select-custom" required>
                <option value="Aktif">Aktif Bertugas</option>
                <option value="Cuti">Sedang Cuti</option>
                <option value="Nonaktif">Nonaktif</option>
              </select>
            </div>

            <div class="col-md-7">
              <label class="form-label form-label-custom">Nama Lengkap & Gelar *</label>
              <input type="text" name="nama_dokter" id="edit_dokter_nama" class="form-control form-control-custom" required>
            </div>
            <div class="col-md-5">
              <label class="form-label form-label-custom">Spesialisasi *</label>
              <input type="text" name="spesialisasi" id="edit_dokter_spesialisasi" class="form-control form-control-custom" required>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Nomor Telepon *</label>
              <input type="text" name="no_telepon" id="edit_dokter_telepon" class="form-control form-control-custom" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Alamat Email</label>
              <input type="email" name="email" id="edit_dokter_email" class="form-control form-control-custom">
            </div>

            <div class="col-12">
              <label class="form-label form-label-custom">Jadwal Praktik *</label>
              <input type="text" name="jadwal_praktek" id="edit_dokter_jadwal" class="form-control form-control-custom" required>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
