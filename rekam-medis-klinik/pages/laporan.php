<?php
/**
 * Halaman Laporan Pelayanan Klinik & Cetak Laporan
 * Fitur: Laporan Kunjungan Pasien, Laporan Pemeriksaan Medis, Laporan Penggunaan Obat, Filter Tanggal, Cetak Resmi
 */

$page_title = 'Laporan Pelayanan';
$active_menu = 'laporan';

require_once __DIR__ . '/../includes/header.php';

// Parameter Filter
$jenis_laporan = $_GET['jenis'] ?? 'kunjungan'; // kunjungan, pemeriksaan, obat
$tgl_dari = $_GET['dari'] ?? date('Y-m-01');
$tgl_sampai = $_GET['sampai'] ?? date('Y-m-d');

// Data Laporan Berdasarkan Jenis
$data_laporan = [];
$ringkasan = [];

if ($jenis_laporan === 'kunjungan') {
    $stmt = $pdo->prepare("
        SELECT p.*, pas.nomor_rm, pas.nama_pasien, pas.jenis_kelamin, d.nama_dokter, d.spesialisasi
        FROM pendaftaran p
        JOIN pasien pas ON p.id_pasien = pas.id
        JOIN dokter d ON p.id_dokter = d.id
        WHERE p.tanggal_kunjungan BETWEEN ? AND ?
        ORDER BY p.tanggal_kunjungan ASC, p.id ASC
    ");
    $stmt->execute([$tgl_dari, $tgl_sampai]);
    $data_laporan = $stmt->fetchAll();

    // Ringkasan
    $ringkasan['total'] = count($data_laporan);
    $ringkasan['umum'] = 0;
    $ringkasan['bpjs'] = 0;
    $ringkasan['asuransi'] = 0;
    $ringkasan['selesai'] = 0;
    foreach ($data_laporan as $d) {
        if ($d['jenis_pasien'] === 'Umum') $ringkasan['umum']++;
        if ($d['jenis_pasien'] === 'BPJS') $ringkasan['bpjs']++;
        if ($d['jenis_pasien'] === 'Asuransi Swasta') $ringkasan['asuransi']++;
        if ($d['status'] === 'Selesai') $ringkasan['selesai']++;
    }

} elseif ($jenis_laporan === 'pemeriksaan') {
    $stmt = $pdo->prepare("
        SELECT rm.*, DATE(rm.tanggal_pemeriksaan) AS tgl_periksa,
               pas.nomor_rm, pas.nama_pasien, pas.jenis_kelamin,
               d.nama_dokter, d.spesialisasi
        FROM rekam_medis rm
        JOIN pasien pas ON rm.id_pasien = pas.id
        JOIN dokter d ON rm.id_dokter = d.id
        WHERE DATE(rm.tanggal_pemeriksaan) BETWEEN ? AND ?
        ORDER BY rm.tanggal_pemeriksaan ASC
    ");
    $stmt->execute([$tgl_dari, $tgl_sampai]);
    $data_laporan = $stmt->fetchAll();

    $ringkasan['total_rm'] = count($data_laporan);

} elseif ($jenis_laporan === 'obat') {
    $stmt = $pdo->prepare("
        SELECT rd.*, r.tanggal_resep, o.kode_obat, o.nama_obat, o.kategori, o.satuan, o.harga,
               pas.nomor_rm, pas.nama_pasien, d.nama_dokter
        FROM resep_detail rd
        JOIN resep_obat r ON rd.id_resep = r.id
        JOIN obat o ON rd.id_obat = o.id
        JOIN pasien pas ON r.id_pasien = pas.id
        JOIN dokter d ON r.id_dokter = d.id
        WHERE DATE(r.tanggal_resep) BETWEEN ? AND ?
        ORDER BY r.tanggal_resep ASC
    ");
    $stmt->execute([$tgl_dari, $tgl_sampai]);
    $data_laporan = $stmt->fetchAll();

    $ringkasan['total_resep_item'] = count($data_laporan);
    $total_qty = 0;
    $total_nilai = 0;
    foreach ($data_laporan as $o) {
        $total_qty += $o['jumlah'];
        $total_nilai += ($o['jumlah'] * $o['harga']);
    }
    $ringkasan['total_qty'] = $total_qty;
    $ringkasan['total_nilai'] = $total_nilai;
}
?>

<!-- Header Halaman (Tidak muncul di print) -->
<div class="page-header no-print">
  <div>
    <h2 class="page-title">Laporan Pelayanan Klinik</h2>
    <p class="page-subtitle">Pusat pelaporan berkala kunjungan, rekam medis klinis, dan konsumsi peresepan obat.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-primary-custom" onclick="printDocument()">
      <i data-lucide="printer" style="width:18px;height:18px;"></i>
      <span>Cetak Dokumen Laporan</span>
    </button>
  </div>
</div>

<!-- Card Filter Laporan (No Print) -->
<div class="card-custom mb-4 no-print">
  <div class="card-body-custom p-3">
    <form action="" method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label form-label-custom">Jenis Laporan Pelayanan</label>
        <select name="jenis" class="form-select form-select-custom">
          <option value="kunjungan" <?= ($jenis_laporan === 'kunjungan') ? 'selected' : ''; ?>>Laporan Kunjungan Pasien</option>
          <option value="pemeriksaan" <?= ($jenis_laporan === 'pemeriksaan') ? 'selected' : ''; ?>>Laporan Pemeriksaan Rekam Medis</option>
          <option value="obat" <?= ($jenis_laporan === 'obat') ? 'selected' : ''; ?>>Laporan Penggunaan & Resep Obat</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label form-label-custom">Dari Tanggal</label>
        <input type="date" name="dari" class="form-control form-control-custom" value="<?= e($tgl_dari); ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label form-label-custom">Sampai Tanggal</label>
        <input type="date" name="sampai" class="form-control form-control-custom" value="<?= e($tgl_sampai); ?>" required>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary-custom flex-grow-1 justify-content-center">
          <i data-lucide="search" style="width:16px;height:16px;"></i>
          <span>Tampilkan Laporan</span>
        </button>
        <a href="<?= base_url('pages/laporan.php'); ?>" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Ringkasan Statistik Laporan -->
<div class="row g-3 mb-4 no-print">
  <?php if ($jenis_laporan === 'kunjungan'): ?>
    <div class="col-sm-6 col-md-3">
      <div class="stat-card p-3">
        <div>
          <div class="stat-value fs-4 text-primary"><?= $ringkasan['total']; ?></div>
          <div class="stat-label small">Total Kunjungan</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="stat-card p-3">
        <div>
          <div class="stat-value fs-4 text-success"><?= $ringkasan['selesai']; ?></div>
          <div class="stat-label small">Kunjungan Selesai</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="stat-card p-3">
        <div>
          <div class="stat-value fs-4 text-info"><?= $ringkasan['bpjs']; ?></div>
          <div class="stat-label small">Pasien BPJS</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="stat-card p-3">
        <div>
          <div class="stat-value fs-4 text-secondary"><?= $ringkasan['umum']; ?></div>
          <div class="stat-label small">Pasien Umum</div>
        </div>
      </div>
    </div>

  <?php elseif ($jenis_laporan === 'pemeriksaan'): ?>
    <div class="col-12 col-md-4">
      <div class="stat-card p-3">
        <div>
          <div class="stat-value fs-4 text-primary"><?= $ringkasan['total_rm']; ?></div>
          <div class="stat-label small">Total Pasien Diperiksa & Didiagnosis</div>
        </div>
      </div>
    </div>

  <?php elseif ($jenis_laporan === 'obat'): ?>
    <div class="col-sm-6 col-md-4">
      <div class="stat-card p-3">
        <div>
          <div class="stat-value fs-4 text-primary"><?= $ringkasan['total_resep_item']; ?></div>
          <div class="stat-label small">Total Item Peresepan</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-4">
      <div class="stat-card p-3">
        <div>
          <div class="stat-value fs-4 text-success"><?= $ringkasan['total_qty']; ?></div>
          <div class="stat-label small">Total Kuantitas Obat Keluar</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-4">
      <div class="stat-card p-3">
        <div>
          <div class="stat-value fs-4 text-danger"><?= format_rupiah($ringkasan['total_nilai']); ?></div>
          <div class="stat-label small">Estimasi Total Nilai Obat</div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- DOKUMEN CETAK & TABEL LAPORAN RESMI -->
<div class="card-custom">
  <div class="card-body-custom p-4">
    <!-- Kop Laporan Resmi (Tampak di Print dan Tampilan) -->
    <div class="text-center border-bottom pb-3 mb-4">
      <h3 class="fw-bold mb-1 text-primary"><?= e($clinic['nama_klinik']); ?></h3>
      <p class="text-muted small mb-1"><?= e($clinic['alamat_klinik']); ?> &bull; Hotline: <?= e($clinic['no_telepon']); ?> &bull; Email: <?= e($clinic['email_klinik']); ?></p>
      <div class="h5 fw-bold text-dark text-uppercase mt-3 mb-1">
        <?php
          if ($jenis_laporan === 'kunjungan') echo "LAPORAN KUNJUNGAN PASIEN RAWAT JALAN";
          elseif ($jenis_laporan === 'pemeriksaan') echo "LAPORAN REKAM MEDIS & TINDAKAN KLINIS";
          else echo "LAPORAN PENGGUNAAN & PERESEPan OBAT";
        ?>
      </div>
      <div class="small text-muted">
        Periode Pelayanan: <strong><?= format_date_id($tgl_dari); ?></strong> s/d <strong><?= format_date_id($tgl_sampai); ?></strong>
      </div>
    </div>

    <!-- Tabel Sesuai Jenis Laporan -->
    <div class="table-responsive">
      <?php if ($jenis_laporan === 'kunjungan'): ?>
        <table class="table table-bordered table-striped small align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px;">No.</th>
              <th>Tanggal</th>
              <th>No. Antrean</th>
              <th>No. RM</th>
              <th>Nama Pasien</th>
              <th>L/P</th>
              <th>Dokter Pemeriksa</th>
              <th>Jenis Pasien</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($data_laporan)): ?>
              <?php $no = 1; foreach ($data_laporan as $row): ?>
                <tr>
                  <td><?= $no++; ?>.</td>
                  <td><?= format_date_id($row['tanggal_kunjungan']); ?></td>
                  <td class="font-monospace fw-bold"><?= e($row['nomor_antrean']); ?></td>
                  <td class="font-monospace"><?= e($row['nomor_rm']); ?></td>
                  <td><strong><?= e($row['nama_pasien']); ?></strong></td>
                  <td><?= ($row['jenis_kelamin'] === 'Laki-laki') ? 'L' : 'P'; ?></td>
                  <td><?= e($row['nama_dokter']); ?></td>
                  <td><?= e($row['jenis_pasien']); ?></td>
                  <td><?= e($row['status']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data kunjungan pada periode ini.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>

      <?php elseif ($jenis_laporan === 'pemeriksaan'): ?>
        <table class="table table-bordered table-striped small align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px;">No.</th>
              <th>Tanggal</th>
              <th>No. RM</th>
              <th>Nama Pasien</th>
              <th>Tanda Vital (TD / Suhu)</th>
              <th>Diagnosis Klinis</th>
              <th>Tindakan / Terapi</th>
              <th>Dokter</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($data_laporan)): ?>
              <?php $no = 1; foreach ($data_laporan as $row): ?>
                <tr>
                  <td><?= $no++; ?>.</td>
                  <td><?= format_date_id($row['tgl_periksa']); ?></td>
                  <td class="font-monospace"><?= e($row['nomor_rm']); ?></td>
                  <td><strong><?= e($row['nama_pasien']); ?></strong></td>
                  <td><?= e($row['tekanan_darah']); ?> mmHg &bull; <?= e($row['suhu_tubuh']); ?>&deg;C</td>
                  <td class="fw-bold text-danger"><?= e($row['diagnosis']); ?></td>
                  <td><?= e($row['tindakan']); ?></td>
                  <td><?= e($row['nama_dokter']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data pemeriksaan rekam medis pada periode ini.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>

      <?php elseif ($jenis_laporan === 'obat'): ?>
        <table class="table table-bordered table-striped small align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px;">No.</th>
              <th>Tanggal</th>
              <th>Kode & Nama Obat</th>
              <th>Kategori</th>
              <th>Pasien & No. RM</th>
              <th>Signa / Aturan Pakai</th>
              <th class="text-center">Jumlah</th>
              <th class="text-end">Harga Satuan</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($data_laporan)): ?>
              <?php $no = 1; foreach ($data_laporan as $row): ?>
                <?php $subtotal = $row['jumlah'] * $row['harga']; ?>
                <tr>
                  <td><?= $no++; ?>.</td>
                  <td><?= format_date_id($row['tanggal_resep']); ?></td>
                  <td>
                    <strong><?= e($row['nama_obat']); ?></strong>
                    <div class="text-muted font-monospace small"><?= e($row['kode_obat']); ?></div>
                  </td>
                  <td><?= e($row['kategori']); ?></td>
                  <td><?= e($row['nama_pasien']); ?> (<?= e($row['nomor_rm']); ?>)</td>
                  <td><?= e($row['aturan_pakai']); ?></td>
                  <td class="text-center fw-bold"><?= $row['jumlah']; ?> <?= e($row['satuan']); ?></td>
                  <td class="text-end"><?= format_rupiah($row['harga']); ?></td>
                  <td class="text-end fw-bold"><?= format_rupiah($subtotal); ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="table-light fw-bold">
                <td colspan="8" class="text-end">TOTAL NILAI OBAT:</td>
                <td class="text-end text-primary"><?= format_rupiah($ringkasan['total_nilai']); ?></td>
              </tr>
            <?php else: ?>
              <tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data peresepan obat pada periode ini.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Tanda Tangan Pengesahan Laporan Resmi -->
    <div class="row pt-5 mt-4">
      <div class="col-6">
        <div class="small text-muted mb-5">Dicetak oleh: <?= e($user_active['nama_lengkap']); ?> (<?= strtoupper(e($user_active['role'])); ?>)</div>
        <div class="small text-muted">Waktu Cetak: <?= date('d/m/Y H:i:s'); ?> WIB</div>
      </div>
      <div class="col-6 text-end">
        <div class="small text-muted mb-5">
          Jakarta, <?= format_date_id(date('Y-m-d')); ?><br>
          Pimpinan / Penanggung Jawab Klinik,
        </div>
        <strong><u><?= e($clinic['pimpinan_klinik']); ?></u></strong>
        <div class="small text-muted">Kepala Manajemen Pelayanan Medis</div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
