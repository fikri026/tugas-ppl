<?php
/**
 * Sidebar Navigation Component
 * Menampilkan 10 menu navigasi utama dengan ikon Lucide dan status active
 */
?>
<aside class="app-sidebar" id="appSidebar">
  <!-- Brand / Clinic Logo Header -->
  <div class="sidebar-header">
    <div class="sidebar-brand-icon">
      <i data-lucide="activity" style="width:24px;height:24px;"></i>
    </div>
    <div class="d-flex flex-column">
      <span class="sidebar-brand-title"><?= e($clinic['nama_klinik']); ?></span>
      <span class="sidebar-brand-subtitle">Sistem Rekam Medis</span>
    </div>
  </div>

  <!-- Navigation Menu List -->
  <div class="sidebar-menu-wrapper">
    <!-- Group: Utama -->
    <div class="menu-heading">Menu Utama</div>
    <a href="<?= base_url('pages/dashboard.php'); ?>" class="nav-link-custom <?= ($active_menu === 'dashboard') ? 'active' : ''; ?>">
      <i data-lucide="layout-dashboard"></i>
      <span>Dashboard</span>
    </a>

    <!-- Group: Pelayanan Pasien -->
    <div class="menu-heading mt-3">Pelayanan Pasien</div>
    <a href="<?= base_url('pages/pasien.php'); ?>" class="nav-link-custom <?= ($active_menu === 'pasien') ? 'active' : ''; ?>">
      <i data-lucide="users"></i>
      <span>Data Pasien</span>
    </a>
    <a href="<?= base_url('pages/tenaga-kesehatan.php'); ?>" class="nav-link-custom <?= ($active_menu === 'tenaga-kesehatan') ? 'active' : ''; ?>">
      <i data-lucide="stethoscope"></i>
      <span>Tenaga Kesehatan</span>
    </a>
    <a href="<?= base_url('pages/pendaftaran.php'); ?>" class="nav-link-custom <?= ($active_menu === 'pendaftaran') ? 'active' : ''; ?>">
      <i data-lucide="clipboard-list"></i>
      <span>Pendaftaran Pasien</span>
    </a>
    <a href="<?= base_url('pages/kunjungan.php'); ?>" class="nav-link-custom <?= ($active_menu === 'kunjungan') ? 'active' : ''; ?>">
      <i data-lucide="history"></i>
      <span>Riwayat Kunjungan</span>
    </a>

    <!-- Group: Tindakan & Farmasi -->
    <div class="menu-heading mt-3">Tindakan & Farmasi</div>
    <a href="<?= base_url('pages/pemeriksaan.php'); ?>" class="nav-link-custom <?= ($active_menu === 'pemeriksaan') ? 'active' : ''; ?>">
      <i data-lucide="file-heart"></i>
      <span>Pemeriksaan Rekam Medis</span>
    </a>
    <a href="<?= base_url('pages/resep-obat.php'); ?>" class="nav-link-custom <?= ($active_menu === 'resep-obat') ? 'active' : ''; ?>">
      <i data-lucide="pill"></i>
      <span>Resep dan Obat</span>
    </a>
    <a href="<?= base_url('pages/jadwal-kontrol.php'); ?>" class="nav-link-custom <?= ($active_menu === 'jadwal-kontrol') ? 'active' : ''; ?>">
      <i data-lucide="calendar-check"></i>
      <span>Jadwal Kontrol</span>
    </a>

    <!-- Group: Laporan & Sistem -->
    <div class="menu-heading mt-3">Laporan & Pengaturan</div>
    <a href="<?= base_url('pages/laporan.php'); ?>" class="nav-link-custom <?= ($active_menu === 'laporan') ? 'active' : ''; ?>">
      <i data-lucide="bar-chart-3"></i>
      <span>Laporan Pelayanan</span>
    </a>
    <a href="<?= base_url('pages/pengaturan.php'); ?>" class="nav-link-custom <?= ($active_menu === 'pengaturan') ? 'active' : ''; ?>">
      <i data-lucide="settings"></i>
      <span>Pengaturan</span>
    </a>
  </div>

  <!-- Sidebar Bottom User Profile -->
  <div class="sidebar-footer">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2 overflow-hidden">
        <div class="user-avatar">
          <?= strtoupper(substr($user_active['nama_lengkap'] ?? 'U', 0, 1)); ?>
        </div>
        <div class="d-flex flex-column text-truncate" style="max-width: 140px;">
          <span class="text-light small fw-bold text-truncate"><?= e($user_active['nama_lengkap'] ?? 'User'); ?></span>
          <span class="text-white-50 text-capitalize" style="font-size:0.725rem;"><?= e($user_active['role'] ?? 'Petugas'); ?></span>
        </div>
      </div>
      <a href="<?= base_url('logout.php'); ?>" class="btn btn-sm btn-outline-danger p-1 d-flex align-items-center justify-content-center" style="width:30px;height:30px;" title="Keluar / Logout">
        <i data-lucide="log-out" style="width:16px;height:16px;"></i>
      </a>
    </div>
  </div>
</aside>
