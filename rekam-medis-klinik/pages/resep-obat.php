<?php
/**
 * Halaman Manajemen Obat & Pembuatan Resep Dokter
 * Fitur: Master Data Obat (CRUD), Form Pembuatan Resep Multi-Obat, Pengurangan Stok Otomatis, Cetak Resep
 */

$page_title = 'Resep dan Obat';
$active_menu = 'resep-obat';

require_once __DIR__ . '/../includes/header.php';

// Tab aktif (obat / resep)
$active_tab = $_GET['tab'] ?? (isset($_GET['rm_id']) ? 'resep' : 'resep');

// 1. TAMBAH OBAT BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_obat') {
    $kode_obat = trim($_POST['kode_obat'] ?? '');
    $nama_obat = trim($_POST['nama_obat'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $jenis_obat = $_POST['jenis_obat'] ?? 'Tablet';
    $stok = (int)($_POST['stok'] ?? 0);
    $satuan = trim($_POST['satuan'] ?? 'Strip');
    $harga = (float)($_POST['harga'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (empty($kode_obat) || empty($nama_obat) || empty($kategori)) {
        set_flash('danger', 'Kode obat, nama obat, dan kategori wajib diisi!');
    } else {
        $check = $pdo->prepare("SELECT id FROM obat WHERE kode_obat = ?");
        $check->execute([$kode_obat]);
        if ($check->fetch()) {
            set_flash('danger', 'Kode obat tersebut sudah terdaftar!');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO obat (kode_obat, nama_obat, kategori, jenis_obat, stok, satuan, harga, deskripsi)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$kode_obat, $nama_obat, $kategori, $jenis_obat, $stok, $satuan, $harga, $deskripsi]);
            set_flash('success', "Obat <strong>{$nama_obat}</strong> berhasil ditambahkan ke katalog!");
            header('Location: ' . base_url('pages/resep-obat.php?tab=obat'));
            exit;
        }
    }
}

// 2. EDIT OBAT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_obat') {
    $id = (int)($_POST['id'] ?? 0);
    $kode_obat = trim($_POST['kode_obat'] ?? '');
    $nama_obat = trim($_POST['nama_obat'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $jenis_obat = $_POST['jenis_obat'] ?? 'Tablet';
    $stok = (int)($_POST['stok'] ?? 0);
    $satuan = trim($_POST['satuan'] ?? 'Strip');
    $harga = (float)($_POST['harga'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE obat 
        SET kode_obat = ?, nama_obat = ?, kategori = ?, jenis_obat = ?, stok = ?, satuan = ?, harga = ?, deskripsi = ?
        WHERE id = ?
    ");
    $stmt->execute([$kode_obat, $nama_obat, $kategori, $jenis_obat, $stok, $satuan, $harga, $deskripsi, $id]);
    set_flash('success', "Informasi obat <strong>{$nama_obat}</strong> berhasil diperbarui!");
    header('Location: ' . base_url('pages/resep-obat.php?tab=obat'));
    exit;
}

// 3. HAPUS OBAT
if (isset($_GET['action']) && $_GET['action'] === 'hapus_obat' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM obat WHERE id = ?");
        $stmt->execute([$id]);
        set_flash('success', 'Data obat berhasil dihapus dari sistem!');
    } catch (PDOException $e) {
        set_flash('danger', 'Obat tidak dapat dihapus karena sudah tercatat dalam riwayat resep!');
    }
    header('Location: ' . base_url('pages/resep-obat.php?tab=obat'));
    exit;
}

// 4. BUAT RESEP OBAT BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_resep') {
    $id_rekam_medis = (int)($_POST['id_rekam_medis'] ?? 0);
    $catatan_resep = trim($_POST['catatan_resep'] ?? '');
    $obat_ids = $_POST['obat_id'] ?? [];
    $dosis_arr = $_POST['dosis'] ?? [];
    $aturan_arr = $_POST['aturan_pakai'] ?? [];
    $jumlah_arr = $_POST['jumlah'] ?? [];

    if ($id_rekam_medis <= 0 || empty($obat_ids)) {
        set_flash('danger', 'Pilih rekam medis pasien dan minimal satu item obat!');
    } else {
        // Ambil id_pasien dan id_dokter dari rekam_medis
        $rm_stmt = $pdo->prepare("SELECT id_pasien, id_dokter FROM rekam_medis WHERE id = ?");
        $rm_stmt->execute([$id_rekam_medis]);
        $rm_info = $rm_stmt->fetch();

        if ($rm_info) {
            $pdo->beginTransaction();
            try {
                // Insert header resep
                $stmt_head = $pdo->prepare("
                    INSERT INTO resep_obat (id_rekam_medis, id_pasien, id_dokter, status, catatan_resep)
                    VALUES (?, ?, ?, 'Selesai', ?)
                ");
                $stmt_head->execute([$id_rekam_medis, $rm_info['id_pasien'], $rm_info['id_dokter'], $catatan_resep]);
                $resep_id = $pdo->lastInsertId();

                // Insert detail resep & potong stok
                $stmt_detail = $pdo->prepare("
                    INSERT INTO resep_detail (id_resep, id_obat, dosis, aturan_pakai, jumlah)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_potong_stok = $pdo->prepare("
                    UPDATE obat SET stok = GREATEST(0, stok - ?) WHERE id = ?
                ");

                for ($i = 0; $i < count($obat_ids); $i++) {
                    $o_id = (int)$obat_ids[$i];
                    $dos = trim($dosis_arr[$i] ?? '');
                    $atur = trim($aturan_arr[$i] ?? '');
                    $jml = (int)($jumlah_arr[$i] ?? 1);

                    if ($o_id > 0 && $jml > 0) {
                        $stmt_detail->execute([$resep_id, $o_id, $dos, $atur, $jml]);
                        $stmt_potong_stok->execute([$jml, $o_id]);
                    }
                }

                $pdo->commit();
                set_flash('success', "Resep obat berhasil diterbitkan dan stok obat otomatis dipotong! <a href='resep-obat.php?cetak_resep={$resep_id}' class='btn btn-xs btn-outline-primary ms-2'>Cetak Resep</a>");
                header('Location: ' . base_url('pages/resep-obat.php?tab=resep'));
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('danger', 'Gagal menyimpan resep: ' . $e->getMessage());
            }
        }
    }
}

// 5. Data untuk Cetak Resep Dokter
$cetak_resep_id = (int)($_GET['cetak_resep'] ?? 0);
$resep_cetak = null;
$detail_resep_items = [];
if ($cetak_resep_id > 0) {
    $stmt_c = $pdo->prepare("
        SELECT r.*, pas.nomor_rm, pas.nama_pasien, pas.tanggal_lahir, pas.alamat, pas.alergi,
               d.nama_dokter, d.nip, d.spesialisasi, rm.diagnosis
        FROM resep_obat r
        JOIN pasien pas ON r.id_pasien = pas.id
        JOIN dokter d ON r.id_dokter = d.id
        JOIN rekam_medis rm ON r.id_rekam_medis = rm.id
        WHERE r.id = ?
    ");
    $stmt_c->execute([$cetak_resep_id]);
    $resep_cetak = $stmt_c->fetch();

    if ($resep_cetak) {
        $stmt_items = $pdo->prepare("
            SELECT rd.*, o.nama_obat, o.satuan, o.jenis_obat, o.harga
            FROM resep_detail rd
            JOIN obat o ON rd.id_obat = o.id
            WHERE rd.id_resep = ?
        ");
        $stmt_items->execute([$cetak_resep_id]);
        $detail_resep_items = $stmt_items->fetchAll();
    }
}

// 6. Master Data Obat & Resep
$obat_list = $pdo->query("SELECT * FROM obat ORDER BY nama_obat ASC")->fetchAll();
$resep_list = $pdo->query("
    SELECT r.*, pas.nomor_rm, pas.nama_pasien, d.nama_dokter, rm.diagnosis,
           (SELECT COUNT(*) FROM resep_detail WHERE id_resep = r.id) AS total_item
    FROM resep_obat r
    JOIN pasien pas ON r.id_pasien = pas.id
    JOIN dokter d ON r.id_dokter = d.id
    JOIN rekam_medis rm ON r.id_rekam_medis = rm.id
    ORDER BY r.id DESC
")->fetchAll();

// Rekam medis yang siap dibuatkan resep (15 terakhir)
$rm_options = $pdo->query("
    SELECT rm.id, rm.diagnosis, rm.tanggal_pemeriksaan, pas.nomor_rm, pas.nama_pasien, d.nama_dokter
    FROM rekam_medis rm
    JOIN pasien pas ON rm.id_pasien = pas.id
    JOIN dokter d ON rm.id_dokter = d.id
    ORDER BY rm.id DESC
    LIMIT 20
")->fetchAll();
?>

<!-- Header Halaman -->
<div class="page-header">
  <div>
    <h2 class="page-title">Resep & Manajemen Farmasi Obat</h2>
    <p class="page-subtitle">Penerbitan resep elektronik dokter, pemotongan stok obat otomatis, dan katalog obat.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahResep">
      <i data-lucide="file-plus-2" style="width:18px;height:18px;"></i>
      <span>Buat Resep Dokter</span>
    </button>
    <button type="button" class="btn btn-outline-custom" data-bs-toggle="modal" data-bs-target="#modalTambahObat">
      <i data-lucide="plus" style="width:18px;height:18px;"></i>
      <span>Tambah Stok Obat</span>
    </button>
  </div>
</div>

<!-- TAMPILAN SALINAN RESEP DOKTER RESMI (Print View) -->
<?php if ($resep_cetak): ?>
  <div class="card-custom mb-4 border-primary">
    <div class="card-header-custom bg-light">
      <div class="d-flex align-items-center gap-2">
        <i data-lucide="printer" class="text-primary" style="width:20px;height:20px;"></i>
        <h4 class="card-header-title text-primary mb-0">Lembar Resep Dokter Resmi (#RSP-<?= str_pad($resep_cetak['id'], 4, '0', STR_PAD_LEFT); ?>)</h4>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm" onclick="printDocument()">
          <i data-lucide="printer" style="width:14px;height:14px;"></i> Cetak Lembar Resep
        </button>
        <a href="<?= base_url('pages/resep-obat.php'); ?>" class="btn btn-outline-secondary btn-sm">Tutup</a>
      </div>
    </div>
    <div class="card-body-custom p-4">
      <div class="print-header text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold mb-1 text-primary"><?= e($clinic['nama_klinik']); ?></h3>
        <p class="text-muted small mb-1"><?= e($clinic['alamat_klinik']); ?> &bull; Telp: <?= e($clinic['no_telepon']); ?></p>
        <div class="fw-bold text-uppercase border-top pt-2 mt-2" style="letter-spacing:1px;">SALINAN RESEP ELEKTRONIK (R/)</div>
      </div>

      <div class="row g-3 mb-4 small">
        <div class="col-6">
          <div class="mb-1"><span class="text-muted">Dokter:</span> <strong><?= e($resep_cetak['nama_dokter']); ?></strong></div>
          <div class="mb-1"><span class="text-muted">SIP/NIP:</span> <?= e($resep_cetak['nip']); ?></div>
          <div class="mb-1"><span class="text-muted">Poliklinik:</span> <?= e($resep_cetak['spesialisasi']); ?></div>
        </div>
        <div class="col-6 text-end">
          <div class="mb-1"><span class="text-muted">Tanggal:</span> <?= format_date_id($resep_cetak['tanggal_resep'], true); ?></div>
          <div class="mb-1"><span class="text-muted">Diagnosis:</span> <strong class="text-danger"><?= e($resep_cetak['diagnosis']); ?></strong></div>
        </div>
      </div>

      <!-- Tabel Item Resep -->
      <table class="table table-bordered small mb-4">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;">No.</th>
            <th>Nama Obat & Sediaan</th>
            <th>Dosis</th>
            <th>Signa / Aturan Pakai</th>
            <th class="text-center" style="width: 100px;">Jumlah</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; foreach ($detail_resep_items as $item): ?>
            <tr>
              <td><?= $no++; ?>.</td>
              <td>
                <div class="fw-bold text-primary">R/ <?= e($item['nama_obat']); ?></div>
                <div class="text-muted small"><?= e($item['jenis_obat']); ?></div>
              </td>
              <td><?= e($item['dosis']); ?></td>
              <td class="fw-semibold"><?= e($item['aturan_pakai']); ?></td>
              <td class="text-center fw-bold">No. <?= e($item['jumlah']); ?> <?= e($item['satuan']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if (!empty($resep_cetak['catatan_resep'])): ?>
        <div class="p-2 bg-light rounded small mb-4">
          <strong>Catatan Farmasi:</strong> <?= e($resep_cetak['catatan_resep']); ?>
        </div>
      <?php endif; ?>

      <!-- Informasi Pasien di Bagian Pro: -->
      <div class="row pt-3 border-top">
        <div class="col-7 small">
          <div class="fw-bold text-dark">Pro (Pasien):</div>
          <div>Nama: <strong><?= e($resep_cetak['nama_pasien']); ?></strong> (No. RM: <?= e($resep_cetak['nomor_rm']); ?>)</div>
          <div>Alamat: <?= e($resep_cetak['alamat']); ?></div>
          <div>Alergi: <span class="text-danger fw-bold"><?= e($resep_cetak['alergi']); ?></span></div>
        </div>
        <div class="col-5 text-center small">
          <div class="mb-5">Tanda Tangan Dokter Pemeriksa,</div>
          <strong>( <?= e($resep_cetak['nama_dokter']); ?> )</strong>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Nav Tabs Antara Resep dan Katalog Obat -->
<ul class="nav nav-pills mb-4 gap-2">
  <li class="nav-item">
    <a class="nav-link <?= ($active_tab === 'resep') ? 'active' : ''; ?>" href="<?= base_url('pages/resep-obat.php?tab=resep'); ?>">
      <i data-lucide="file-text" style="width:16px;height:16px;"></i>
      <span>Riwayat Resep Dokter</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= ($active_tab === 'obat') ? 'active' : ''; ?>" href="<?= base_url('pages/resep-obat.php?tab=obat'); ?>">
      <i data-lucide="boxes" style="width:16px;height:16px;"></i>
      <span>Katalog & Stok Obat (<?= count($obat_list); ?>)</span>
    </a>
  </li>
</ul>

<?php if ($active_tab === 'resep'): ?>
  <!-- TABEL RIWAYAT RESEP OBAT -->
  <div class="card-custom">
    <div class="card-header-custom">
      <h3 class="card-header-title">
        <i data-lucide="clipboard-list" class="text-primary" style="width:20px;height:20px;"></i>
        <span>Daftar Resep Obat Elektronik Diterbitkan</span>
      </h3>
      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><?= count($resep_list); ?> Resep</span>
    </div>
    <div class="table-responsive">
      <table class="table-custom">
        <thead>
          <tr>
            <th>Kode Resep</th>
            <th>Tanggal Resep</th>
            <th>No. RM & Nama Pasien</th>
            <th>Dokter Penulis</th>
            <th>Diagnosis Terkait</th>
            <th>Jumlah Obat</th>
            <th>Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($resep_list)): ?>
            <?php foreach ($resep_list as $r): ?>
              <tr>
                <td>
                  <span class="font-monospace fw-bold text-primary bg-primary-subtle px-2 py-1 rounded">
                    #RSP-<?= str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?>
                  </span>
                </td>
                <td>
                  <span class="small text-muted"><?= format_date_id($r['tanggal_resep'], true); ?></span>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= e($r['nama_pasien']); ?></div>
                  <span class="small text-muted font-monospace"><?= e($r['nomor_rm']); ?></span>
                </td>
                <td>
                  <div class="fw-medium text-dark small"><?= e($r['nama_dokter']); ?></div>
                </td>
                <td>
                  <span class="fw-semibold text-danger small"><?= e($r['diagnosis']); ?></span>
                </td>
                <td>
                  <span class="badge bg-light text-dark border"><?= $r['total_item']; ?> Item Obat</span>
                </td>
                <td>
                  <span class="badge-custom badge-success-soft">
                    <i data-lucide="check" style="width:12px;height:12px;"></i> Selesai
                  </span>
                </td>
                <td class="text-end">
                  <a href="<?= base_url('pages/resep-obat.php?cetak_resep=' . $r['id']); ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <i data-lucide="printer" style="width:14px;height:14px;"></i>
                    <span>Cetak Lembar Resep</span>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">Belum ada riwayat resep obat yang diterbitkan.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>

  <!-- TABEL KATALOG & STOK OBAT -->
  <div class="card-custom">
    <div class="card-header-custom flex-wrap gap-3">
      <h3 class="card-header-title">
        <i data-lucide="pill" class="text-primary" style="width:20px;height:20px;"></i>
        <span>Katalog Obat & Monitoring Stok</span>
      </h3>
      <div class="d-flex align-items-center gap-2">
        <input type="text" id="cariObatInput" class="form-control form-control-sm" placeholder="Cari nama obat / kategori..." onkeyup="filterTable('cariObatInput', 'tabelObat')">
      </div>
    </div>
    <div class="table-responsive">
      <table class="table-custom" id="tabelObat">
        <thead>
          <tr>
            <th>Kode Obat</th>
            <th>Nama Obat</th>
            <th>Kategori</th>
            <th>Bentuk / Sediaan</th>
            <th>Sisa Stok</th>
            <th>Satuan</th>
            <th>Harga Satuan</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($obat_list)): ?>
            <?php foreach ($obat_list as $ob): ?>
              <tr>
                <td>
                  <span class="font-monospace fw-bold text-dark bg-light px-2 py-1 rounded border">
                    <?= e($ob['kode_obat']); ?>
                  </span>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= e($ob['nama_obat']); ?></div>
                  <div class="small text-muted text-truncate" style="max-width:240px;"><?= e($ob['deskripsi'] ?: '-'); ?></div>
                </td>
                <td>
                  <span class="badge bg-light text-primary border border-primary-subtle">
                    <?= e($ob['kategori']); ?>
                  </span>
                </td>
                <td><?= e($ob['jenis_obat']); ?></td>
                <td>
                  <?php if ($ob['stok'] <= 10): ?>
                    <span class="badge bg-danger text-white fs-6 font-monospace"><?= $ob['stok']; ?></span>
                    <span class="small text-danger d-block">Stok Menipis!</span>
                  <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 font-monospace"><?= $ob['stok']; ?></span>
                  <?php endif; ?>
                </td>
                <td><?= e($ob['satuan']); ?></td>
                <td>
                  <span class="fw-semibold text-dark"><?= format_rupiah($ob['harga']); ?></span>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <button type="button" class="btn-action-icon text-warning" title="Edit Obat" onclick='openEditObatModal(<?= json_encode($ob); ?>)'>
                      <i data-lucide="edit-3" style="width:16px;height:16px;"></i>
                    </button>
                    <button type="button" class="btn-action-icon danger" title="Hapus Obat" onclick="confirmDelete('<?= base_url('pages/resep-obat.php?action=hapus_obat&id=' . $ob['id']); ?>', 'Hapus Obat', 'Hapus <?= addslashes(e($ob['nama_obat'])); ?> dari sistem?')">
                      <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">Belum ada obat dalam database.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- MODAL TAMBAH RESEP BARU -->
<div class="modal fade" id="modalTambahResep" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <i data-lucide="file-plus-2" class="text-primary"></i>
          <span>Pembuatan Resep Dokter Pasca Pemeriksaan</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="simpan_resep">
        <div class="modal-body p-4">
          <!-- Pilih Pemeriksaan Rekam Medis -->
          <div class="mb-4">
            <label class="form-label form-label-custom">Pilih Berkas Rekam Medis Pasien *</label>
            <select name="id_rekam_medis" class="form-select form-select-custom" required>
              <option value="">-- Pilih Rekam Medis Pasien Terakhir --</option>
              <?php foreach ($rm_options as $rmo): ?>
                <option value="<?= $rmo['id']; ?>" <?= (isset($_GET['rm_id']) && $_GET['rm_id'] == $rmo['id']) ? 'selected' : ''; ?>>
                  [<?= e($rmo['nomor_rm']); ?>] <?= e($rmo['nama_pasien']); ?> &bull; Diagnosa: <?= e($rmo['diagnosis']); ?> (Dokter: <?= e($rmo['nama_dokter']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Template Select Obat Tersembunyi (digunakan oleh JavaScript app.js) -->
          <select id="templateObatSelect" class="d-none">
            <option value="">-- Pilih Obat --</option>
            <?php foreach ($obat_list as $o): ?>
              <option value="<?= $o['id']; ?>">
                <?= e($o['nama_obat']); ?> (Stok: <?= $o['stok']; ?> <?= e($o['satuan']); ?>) - <?= format_rupiah($o['harga']); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <!-- Dynamic Item Obat List -->
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1">
              <i data-lucide="pill" class="text-success" style="width:16px;height:16px;"></i>
              <span>Daftar Item Obat yang Diresepkan</span>
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" onclick="addResepRow()">
              <i data-lucide="plus" style="width:14px;height:14px;"></i> Tambah Baris Obat
            </button>
          </div>

          <div id="resepItemsContainer">
            <!-- Baris Default Pertama -->
            <div class="row g-2 align-items-center mb-3 resep-item-row p-2 bg-light rounded" id="resep_row_0">
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Pilih Obat</label>
                <select name="obat_id[]" class="form-select form-select-sm" required>
                  <option value="">-- Pilih Obat --</option>
                  <?php foreach ($obat_list as $o): ?>
                    <option value="<?= $o['id']; ?>">
                      <?= e($o['nama_obat']); ?> (Stok: <?= $o['stok']; ?> <?= e($o['satuan']); ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Dosis</label>
                <input type="text" name="dosis[]" class="form-control form-control-sm" placeholder="Contoh: 500 mg" value="500 mg" required>
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Aturan Pakai</label>
                <input type="text" name="aturan_pakai[]" class="form-control form-control-sm" placeholder="Contoh: 3x sehari 1 tablet" value="3 x sehari 1 tablet sesudah makan" required>
              </div>
              <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Jumlah</label>
                <input type="number" name="jumlah[]" class="form-control form-control-sm" value="1" min="1" required>
              </div>
              <div class="col-md-1 text-center">
                <label class="form-label small d-block mb-1">&nbsp;</label>
                <button type="button" class="btn btn-outline-secondary btn-sm disabled" title="Baris Utama">
                  <i data-lucide="lock" style="width:14px;height:14px;"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label form-label-custom">Catatan / Instruksi Khusus Farmasi</label>
            <textarea name="catatan_resep" class="form-control form-control-custom" rows="2" placeholder="Contoh: Obat antibiotik wajib dihabiskan. Paracetamol hanya bila demam..."></textarea>
          </div>
        </div>
        <div class="modal-footer modal-footer-custom">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">
            <i data-lucide="check" style="width:16px;height:16px;"></i>
            <span>Terbitkan Resep & Kurangi Stok</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL TAMBAH OBAT -->
<div class="modal fade" id="modalTambahObat" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <i data-lucide="plus" class="text-primary"></i>
          <span>Tambah Data Obat Baru</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="tambah_obat">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label form-label-custom">Kode Obat *</label>
              <input type="text" name="kode_obat" class="form-control form-control-custom font-monospace" placeholder="OBT-013" required>
            </div>
            <div class="col-md-8">
              <label class="form-label form-label-custom">Nama Obat Lengkap *</label>
              <input type="text" name="nama_obat" class="form-control form-control-custom" placeholder="Nama obat & dosis" required>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Kategori Obat *</label>
              <input type="text" name="kategori" class="form-control form-control-custom" placeholder="Antibiotik, Analgesik, Vitamin..." required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Bentuk Sediaan *</label>
              <select name="jenis_obat" class="form-select form-select-custom" required>
                <option value="Tablet">Tablet</option>
                <option value="Kapsul">Kapsul</option>
                <option value="Sirup">Sirup</option>
                <option value="Salep">Salep</option>
                <option value="Injeksi">Injeksi</option>
                <option value="Tetes">Tetes</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label form-label-custom">Jumlah Stok Masuk *</label>
              <input type="number" name="stok" class="form-control form-control-custom" value="100" min="0" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-custom">Satuan Kemasan *</label>
              <input type="text" name="satuan" class="form-control form-control-custom" placeholder="Strip / Botol / Tube" value="Strip" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-custom">Harga Satuan (Rp) *</label>
              <input type="number" step="100" name="harga" class="form-control form-control-custom" placeholder="Contoh: 15000" value="10000" required>
            </div>

            <div class="col-12">
              <label class="form-label form-label-custom">Deskripsi & Indikasi</label>
              <textarea name="deskripsi" class="form-control form-control-custom" rows="2" placeholder="Kegunaan obat dan efek samping..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer modal-footer-custom">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">Simpan Obat</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL EDIT OBAT -->
<div class="modal fade" id="modalEditObat" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <i data-lucide="edit-3" class="text-warning"></i>
          <span>Perbarui Data Obat</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="edit_obat">
        <input type="hidden" name="id" id="edit_obat_id">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label form-label-custom">Kode Obat *</label>
              <input type="text" name="kode_obat" id="edit_obat_kode" class="form-control form-control-custom font-monospace" required>
            </div>
            <div class="col-md-8">
              <label class="form-label form-label-custom">Nama Obat Lengkap *</label>
              <input type="text" name="nama_obat" id="edit_obat_nama" class="form-control form-control-custom" required>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Kategori Obat *</label>
              <input type="text" name="kategori" id="edit_obat_kategori" class="form-control form-control-custom" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Bentuk Sediaan *</label>
              <select name="jenis_obat" id="edit_obat_jenis" class="form-select form-select-custom" required>
                <option value="Tablet">Tablet</option>
                <option value="Kapsul">Kapsul</option>
                <option value="Sirup">Sirup</option>
                <option value="Salep">Salep</option>
                <option value="Injeksi">Injeksi</option>
                <option value="Tetes">Tetes</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label form-label-custom">Stok Tersedia *</label>
              <input type="number" name="stok" id="edit_obat_stok" class="form-control form-control-custom" min="0" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-custom">Satuan Kemasan *</label>
              <input type="text" name="satuan" id="edit_obat_satuan" class="form-control form-control-custom" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-custom">Harga Satuan (Rp) *</label>
              <input type="number" step="100" name="harga" id="edit_obat_harga" class="form-control form-control-custom" required>
            </div>

            <div class="col-12">
              <label class="form-label form-label-custom">Deskripsi Obat</label>
              <textarea name="deskripsi" id="edit_obat_deskripsi" class="form-control form-control-custom" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer modal-footer-custom">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (isset($_GET['rm_id'])): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const rmModal = new bootstrap.Modal(document.getElementById('modalTambahResep'));
    rmModal.show();
  });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
