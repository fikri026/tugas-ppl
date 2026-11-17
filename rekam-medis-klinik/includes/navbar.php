<?php
/**
 * Top Navbar Component
 * Menampilkan toggle sidebar, tanggal/jam real-time, tombol aksi cepat, dan profil dropdown
 */
?>
<header class="app-navbar">
  <!-- Left Side: Toggle & Breadcrumb / Clinic Greeting -->
  <div class="d-flex align-items-center gap-3">
    <button type="button" class="navbar-btn-toggle" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
      <i data-lucide="menu" style="width:20px;height:20px;"></i>
    </button>
    
    <div class="d-none d-md-flex align-items-center gap-2 text-muted small">
      <i data-lucide="shield-check" class="text-primary" style="width:18px;height:18px;"></i>
      <span class="fw-semibold text-dark"><?= e($clinic['nama_klinik']); ?></span>
      <span class="text-secondary-subtle">|</span>
      <span><?= e($clinic['jam_operasional']); ?></span>
    </div>
  </div>

  <!-- Right Side: Clock Widget & User Profile -->
  <div class="d-flex align-items-center gap-3">
    <!-- Jam Digital & Tanggal Live -->
    <div class="time-widget d-none d-sm-flex">
      <i data-lucide="clock" class="text-primary" style="width:16px;height:16px;"></i>
      <span class="fw-medium text-dark" id="liveClock">--:--:-- WIB</span>
      <span class="badge bg-light text-secondary border ms-1"><?= format_date_id(date('Y-m-d')); ?></span>
    </div>

    <!-- Quick Action Button -->
    <div class="dropdown d-none d-lg-block">
      <button class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 dropdown-toggle rounded-pill px-3 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i data-lucide="plus-circle" style="width:16px;height:16px;"></i>
        <span>Aksi Cepat</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= base_url('pages/pendaftaran.php?action=tambah'); ?>">
            <i data-lucide="user-plus" class="text-primary" style="width:16px;height:16px;"></i>
            <span>Daftar Kunjungan Baru</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= base_url('pages/pasien.php?action=tambah'); ?>">
            <i data-lucide="user" class="text-success" style="width:16px;height:16px;"></i>
            <span>Tambah Pasien Baru</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= base_url('pages/pemeriksaan.php'); ?>">
            <i data-lucide="file-heart" class="text-danger" style="width:16px;height:16px;"></i>
            <span>Input Pemeriksaan Pasien</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- User Profile Dropdown -->
    <div class="dropdown">
      <button class="btn p-0 border-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="user-avatar" style="width:36px;height:36px;font-size:0.85rem;">
          <?= strtoupper(substr($user_active['nama_lengkap'] ?? 'U', 0, 1)); ?>
        </div>
        <div class="d-none d-sm-flex flex-column text-start">
          <span class="fw-semibold text-dark small" style="line-height:1.2;"><?= e($user_active['nama_lengkap'] ?? 'User'); ?></span>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.68rem;padding:2px 6px;width:fit-content;">
            <?= strtoupper(e($user_active['role'] ?? 'Petugas')); ?>
          </span>
        </div>
        <i data-lucide="chevron-down" class="text-muted" style="width:16px;height:16px;"></i>
      </button>

      <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2" style="min-width: 220px;">
        <li class="px-3 py-2 border-bottom mb-1">
          <div class="fw-bold text-dark"><?= e($user_active['nama_lengkap']); ?></div>
          <div class="small text-muted"><?= e($user_active['email'] ?? 'Akun Klinik'); ?></div>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2 py-2 rounded" href="<?= base_url('pages/pengaturan.php'); ?>">
            <i data-lucide="settings" style="width:16px;height:16px;"></i>
            <span>Pengaturan Akun</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2 py-2 rounded" href="<?= base_url('pages/pengaturan.php#ubahPassword'); ?>">
            <i data-lucide="key" style="width:16px;height:16px;"></i>
            <span>Ubah Password</span>
          </a>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger rounded" href="<?= base_url('logout.php'); ?>">
            <i data-lucide="log-out" style="width:16px;height:16px;"></i>
            <span>Keluar Sistem</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</header>
