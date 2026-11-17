<?php
/**
 * Halaman Dashboard Utama
 * Menampilkan kartu statistik dari database, grafik kunjungan Chart.js, dan tabel kunjungan terbaru
 */

$page_title = 'Dashboard Rekam Medis';
$active_menu = 'dashboard';

require_once __DIR__ . '/../includes/header.php';

// 1. Ambil Statistik Kartu Utama dari Database
$total_pasien = $pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();
$total_dokter = $pdo->query("SELECT COUNT(*) FROM dokter WHERE status = 'Aktif'")->fetchColumn();
$kunjungan_hari_ini = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE tanggal_kunjungan = CURDATE()")->fetchColumn();
$total_pemeriksaan = $pdo->query("SELECT COUNT(*) FROM rekam_medis")->fetchColumn();

// Kunjungan sedang menunggu & diperiksa hari ini
$antrean_aktif = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE tanggal_kunjungan = CURDATE() AND status IN ('Menunggu', 'Diperiksa')")->fetchColumn();

// Kontrol hari ini
$kontrol_hari_ini = $pdo->query("SELECT COUNT(*) FROM jadwal_kontrol WHERE tanggal_kontrol = CURDATE() AND status = 'Terjadwal'")->fetchColumn();

// 2. Data Grafik Kunjungan 7 Hari Terakhir
$grafik_labels = [];
$grafik_data = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-{$i} days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE tanggal_kunjungan = ?");
    $stmt->execute([$tgl]);
    $count = $stmt->fetchColumn();
    
    $grafik_labels[] = date('d M', strtotime($tgl));
    $grafik_data[] = (int)$count;
}

// 3. Distribusi Jenis Pembayaran Pasien
$jenis_umum = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE jenis_pasien = 'Umum'")->fetchColumn();
$jenis_bpjs = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE jenis_pasien = 'BPJS'")->fetchColumn();
$jenis_asuransi = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE jenis_pasien = 'Asuransi Swasta'")->fetchColumn();

// 4. Kunjungan Terbaru Hari Ini
$stmt_terbaru = $pdo->query("
    SELECT p.*, pas.nomor_rm, pas.nama_pasien, pas.jenis_kelamin, d.nama_dokter, d.spesialisasi
    FROM pendaftaran p
    JOIN pasien pas ON p.id_pasien = pas.id
    JOIN dokter d ON p.id_dokter = d.id
    ORDER BY p.id DESC
    LIMIT 8
");
$kunjungan_terbaru = $stmt_terbaru->fetchAll();
?>

<!-- Header Halaman Dashboard -->
<div class="page-header">
  <div>
    <h2 class="page-title">Dashboard Pelayanan Klinik</h2>
    <p class="page-subtitle">Ringkasan aktivitas operasional, kunjungan pasien, dan statistik rekam medis terkini.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a href="<?= base_url('pages/pendaftaran.php?action=tambah'); ?>" class="btn btn-primary-custom">
      <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
      <span>Pendaftaran Pasien</span>
    </a>
    <a href="<?= base_url('pages/pasien.php?action=tambah'); ?>" class="btn btn-outline-custom">
      <i data-lucide="user-plus" style="width:18px;height:18px;"></i>
      <span>Pasien Baru</span>
    </a>
  </div>
</div>

<!-- Alert Peringatan Jadwal Kontrol Hari Ini -->
<?php if ($kontrol_hari_ini > 0): ?>
  <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 rounded-3 shadow-sm mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="p-2 rounded-circle bg-warning text-white">
        <i data-lucide="bell-ring" style="width:20px;height:20px;"></i>
      </div>
      <div>
        <h6 class="mb-0 fw-bold">Pengingat Jadwal Kontrol</h6>
        <span class="small text-muted">Terdapat <strong><?= $kontrol_hari_ini; ?> pasien</strong> yang dijadwalkan kontrol ulang pada hari ini.</span>
      </div>
    </div>
    <a href="<?= base_url('pages/jadwal-kontrol.php?filter_tgl=' . date('Y-m-d')); ?>" class="btn btn-sm btn-warning text-dark fw-bold px-3">
      Lihat Jadwal
    </a>
  </div>
<?php endif; ?>

<!-- 4 Kartu Metrik Utama -->
<div class="row g-3 mb-4">
  <!-- Total Pasien -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon-wrapper stat-icon-blue">
        <i data-lucide="users"></i>
      </div>
      <div class="flex-grow-1">
        <div class="stat-value"><?= number_format($total_pasien); ?></div>
        <div class="stat-label">Total Data Pasien</div>
        <div class="stat-trend text-primary">
          <i data-lucide="shield-check" style="width:14px;height:14px;"></i>
          <span>Nomor RM Terdaftar</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Dokter -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon-wrapper stat-icon-green">
        <i data-lucide="stethoscope"></i>
      </div>
      <div class="flex-grow-1">
        <div class="stat-value"><?= number_format($total_dokter); ?></div>
        <div class="stat-label">Dokter & Nakes Aktif</div>
        <div class="stat-trend text-success">
          <i data-lucide="check-circle-2" style="width:14px;height:14px;"></i>
          <span>Siap Melayani</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Kunjungan Hari Ini -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon-wrapper stat-icon-sky">
        <i data-lucide="calendar"></i>
      </div>
      <div class="flex-grow-1">
        <div class="stat-value"><?= number_format($kunjungan_hari_ini); ?></div>
        <div class="stat-label">Kunjungan Hari Ini</div>
        <div class="stat-trend text-info">
          <i data-lucide="clock" style="width:14px;height:14px;"></i>
          <span><?= $antrean_aktif; ?> dalam antrean</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Pemeriksaan RM -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon-wrapper stat-icon-amber">
        <i data-lucide="file-heart"></i>
      </div>
      <div class="flex-grow-1">
        <div class="stat-value"><?= number_format($total_pemeriksaan); ?></div>
        <div class="stat-label">Pemeriksaan Rekam Medis</div>
        <div class="stat-trend text-warning">
          <i data-lucide="activity" style="width:14px;height:14px;"></i>
          <span>Hasil Klinis Tersimpan</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Baris Grafik & Diagram Statistik -->
<div class="row g-4 mb-4">
  <!-- Grafik Tren Kunjungan Pasien -->
  <div class="col-12 col-xl-8">
    <div class="card-custom h-100">
      <div class="card-header-custom">
        <h3 class="card-header-title">
          <i data-lucide="trending-up" class="text-primary" style="width:20px;height:20px;"></i>
          <span>Tren Kunjungan Pasien (7 Hari Terakhir)</span>
        </h3>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
          Pembaruan Real-Time
        </span>
      </div>
      <div class="card-body-custom">
        <div style="height: 280px; position: relative;">
          <canvas id="chartKunjungan"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Diagram Distribusi Pembayaran Pasien -->
  <div class="col-12 col-xl-4">
    <div class="card-custom h-100">
      <div class="card-header-custom">
        <h3 class="card-header-title">
          <i data-lucide="pie-chart" class="text-success" style="width:20px;height:20px;"></i>
          <span>Jenis Pasien</span>
        </h3>
      </div>
      <div class="card-body-custom d-flex flex-column justify-content-between">
        <div style="height: 210px; position: relative;">
          <canvas id="chartJenisPasien"></canvas>
        </div>
        <div class="mt-3 pt-3 border-top">
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span><span class="badge rounded-circle bg-primary p-1 me-1"></span> Pasien Umum:</span>
            <strong class="text-dark"><?= $jenis_umum; ?> kunjungan</strong>
          </div>
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span><span class="badge rounded-circle bg-success p-1 me-1"></span> Peserta BPJS:</span>
            <strong class="text-dark"><?= $jenis_bpjs; ?> kunjungan</strong>
          </div>
          <div class="d-flex justify-content-between small text-muted">
            <span><span class="badge rounded-circle bg-info p-1 me-1"></span> Asuransi Swasta:</span>
            <strong class="text-dark"><?= $jenis_asuransi; ?> kunjungan</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tabel Kunjungan Terbaru -->
<div class="card-custom mb-4">
  <div class="card-header-custom">
    <h3 class="card-header-title">
      <i data-lucide="list-ordered" class="text-primary" style="width:20px;height:20px;"></i>
      <span>Daftar Kunjungan & Antrean Pasien Terbaru</span>
    </h3>
    <a href="<?= base_url('pages/kunjungan.php'); ?>" class="btn btn-sm btn-outline-custom">
      <span>Lihat Semua Kunjungan</span>
      <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
    </a>
  </div>
  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No. Antrean</th>
          <th>Tanggal</th>
          <th>No. RM & Nama Pasien</th>
          <th>Dokter Pemeriksa</th>
          <th>Keluhan Awal</th>
          <th>Jenis Pasien</th>
          <th>Status</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($kunjungan_terbaru)): ?>
          <?php foreach ($kunjungan_terbaru as $k): ?>
            <tr>
              <td>
                <span class="fw-bold text-primary font-monospace bg-primary-subtle px-2 py-1 rounded">
                  <?= e($k['nomor_antrean']); ?>
                </span>
              </td>
              <td>
                <span class="small text-muted"><?= format_date_id($k['tanggal_kunjungan']); ?></span>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= e($k['nama_pasien']); ?></div>
                <div class="small text-muted font-monospace"><?= e($k['nomor_rm']); ?> &bull; <?= e($k['jenis_kelamin']); ?></div>
              </td>
              <td>
                <div class="fw-medium text-dark"><?= e($k['nama_dokter']); ?></div>
                <div class="small text-muted"><?= e($k['spesialisasi']); ?></div>
              </td>
              <td>
                <span class="small text-muted text-truncate d-inline-block" style="max-width: 220px;" title="<?= e($k['keluhan_awal']); ?>">
                  <?= e($k['keluhan_awal'] ?: '-'); ?>
                </span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  <?= e($k['jenis_pasien']); ?>
                </span>
              </td>
              <td>
                <?php if ($k['status'] === 'Selesai'): ?>
                  <span class="badge-custom badge-success-soft">
                    <i data-lucide="check" style="width:12px;height:12px;"></i> Selesai
                  </span>
                <?php elseif ($k['status'] === 'Diperiksa'): ?>
                  <span class="badge-custom badge-warning-soft">
                    <i data-lucide="activity" style="width:12px;height:12px;"></i> Sedang Diperiksa
                  </span>
                <?php elseif ($k['status'] === 'Menunggu'): ?>
                  <span class="badge-custom badge-primary-soft">
                    <i data-lucide="clock" style="width:12px;height:12px;"></i> Menunggu
                  </span>
                <?php else: ?>
                  <span class="badge-custom badge-danger-soft">
                    <i data-lucide="x" style="width:12px;height:12px;"></i> Dibatalkan
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <?php if ($k['status'] === 'Menunggu'): ?>
                    <a href="<?= base_url('pages/pemeriksaan.php?id_pendaftaran=' . $k['id']); ?>" class="btn-action-icon text-success" title="Periksa Pasien">
                      <i data-lucide="stethoscope" style="width:16px;height:16px;"></i>
                    </a>
                  <?php endif; ?>
                  <a href="<?= base_url('pages/kunjungan.php?id=' . $k['id']); ?>" class="btn-action-icon" title="Detail Kunjungan">
                    <i data-lucide="eye" style="width:16px;height:16px;"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">Belum ada data kunjungan pasien.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Chart.js Scripts Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // 1. Inisialisasi Grafik Garis Kunjungan
  const ctxKunjungan = document.getElementById('chartKunjungan');
  if (ctxKunjungan) {
    new Chart(ctxKunjungan, {
      type: 'line',
      data: {
        labels: <?= json_encode($grafik_labels); ?>,
        datasets: [{
          label: 'Jumlah Pasien',
          data: <?= json_encode($grafik_data); ?>,
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37, 99, 235, 0.08)',
          borderWidth: 3,
          pointBackgroundColor: '#1d4ed8',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7,
          tension: 0.35,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            titleFont: { size: 13 },
            bodyFont: { size: 12 },
            cornerRadius: 8
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0, color: '#64748b' },
            grid: { color: '#f1f5f9' }
          },
          x: {
            ticks: { color: '#64748b' },
            grid: { display: false }
          }
        }
      }
    });
  }

  // 2. Inisialisasi Grafik Doughnut Jenis Pasien
  const ctxJenis = document.getElementById('chartJenisPasien');
  if (ctxJenis) {
    new Chart(ctxJenis, {
      type: 'doughnut',
      data: {
        labels: ['Umum', 'BPJS', 'Asuransi Swasta'],
        datasets: [{
          data: [<?= (int)$jenis_umum; ?>, <?= (int)$jenis_bpjs; ?>, <?= (int)$jenis_asuransi; ?>],
          backgroundColor: ['#2563eb', '#10b981', '#06b6d4'],
          hoverOffset: 4,
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 12, font: { size: 12 }, color: '#475569' }
          }
        },
        cutout: '72%'
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
